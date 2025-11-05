<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Wave\Plugins\EvenLeads\Models\Setting;

class NgrokService
{
    protected $apiKey;
    protected $tunnelUrl = null;
    protected $processId = null;

    public function __construct()
    {
        try {
            $this->apiKey = Setting::getValue('ngrok.api_key');
        } catch (\Exception $e) {
            // Database might not be ready during boot
            $this->apiKey = null;
        }
    }

    /**
     * Get API key (reloads from database)
     */
    protected function getApiKey(): ?string
    {
        try {
            return Setting::getValue('ngrok.api_key');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if ngrok is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Check if ngrok tunnel is active
     */
    public function isRunning(): bool
    {
        try {
            $response = Http::timeout(2)->get('http://127.0.0.1:4040/api/tunnels');

            if ($response->successful()) {
                $data = $response->json();
                $tunnels = $data['tunnels'] ?? [];

                // Store tunnel URL if found
                foreach ($tunnels as $tunnel) {
                    if (isset($tunnel['public_url']) && str_starts_with($tunnel['public_url'], 'https://')) {
                        $this->tunnelUrl = $tunnel['public_url'];
                        return true;
                    }
                }
            }
        } catch (\Exception $e) {
            // Ngrok API not accessible
        }

        return false;
    }

    /**
     * Get the public tunnel URL
     */
    public function getTunnelUrl(): ?string
    {
        if ($this->isRunning()) {
            return $this->tunnelUrl;
        }
        return null;
    }

    /**
     * Alias for getTunnelUrl()
     */
    public function getPublicUrl(): ?string
    {
        return $this->getTunnelUrl();
    }

    /**
     * Start ngrok tunnel
     */
    public function start(int $port = 8000): array
    {
        // Check if already running
        if ($this->isRunning()) {
            return [
                'success' => true,
                'message' => 'Ngrok is already running',
                'url' => $this->tunnelUrl,
            ];
        }

        // Check if ngrok command exists
        if (!$this->ngrokExists()) {
            return [
                'success' => false,
                'message' => 'Ngrok is not installed. Install it from: https://ngrok.com/download',
            ];
        }

        try {
            // Kill any existing ngrok processes
            exec('pkill -f ngrok');
            sleep(1);

            // Build ngrok command
            $command = "ngrok http {$port}";

            // Get fresh API key from database
            $apiKey = $this->getApiKey();

            if (!empty($apiKey)) {
                // Set authtoken first
                exec("ngrok config add-authtoken {$apiKey} 2>&1", $output, $returnCode);
            }

            // Start ngrok in background
            exec("{$command} > /dev/null 2>&1 &", $output, $returnCode);

            // Wait for ngrok to start
            sleep(3);

            // Check if it's running
            if ($this->isRunning()) {
                return [
                    'success' => true,
                    'message' => 'Ngrok started successfully',
                    'url' => $this->tunnelUrl,
                ];
            }

            return [
                'success' => false,
                'message' => 'Ngrok failed to start. Check if port ' . $port . ' is in use.',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to start ngrok: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Stop ngrok tunnel
     */
    public function stop(): array
    {
        try {
            exec('pkill -f ngrok', $output, $returnCode);
            sleep(1);

            if (!$this->isRunning()) {
                $this->tunnelUrl = null;
                return [
                    'success' => true,
                    'message' => 'Ngrok stopped successfully',
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to stop ngrok',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error stopping ngrok: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check if ngrok command exists
     */
    protected function ngrokExists(): bool
    {
        exec('which ngrok', $output, $returnCode);
        return $returnCode === 0;
    }

    /**
     * Get ngrok status information
     */
    public function getStatus(): array
    {
        $isRunning = $this->isRunning();

        return [
            'configured' => $this->isConfigured(),
            'installed' => $this->ngrokExists(),
            'running' => $isRunning,
            'url' => $isRunning ? $this->tunnelUrl : null,
        ];
    }

    /**
     * Check if current APP_URL is localhost
     */
    public static function isLocalhost(?string $url = null): bool
    {
        $url = $url ?? config('app.url');

        return str_contains($url, 'localhost') ||
               str_contains($url, '127.0.0.1') ||
               str_contains($url, '::1');
    }

    /**
     * Auto-start ngrok if on localhost and API key is configured
     */
    public function autoStart(): void
    {
        // Only auto-start if on localhost
        if (!self::isLocalhost()) {
            return;
        }

        // Only auto-start if API key is configured
        if (!$this->isConfigured()) {
            Log::info('Ngrok auto-start skipped: No API key configured');
            return;
        }

        // Check if already running
        if ($this->isRunning()) {
            Log::info('Ngrok auto-start skipped: Already running at ' . $this->tunnelUrl);
            return;
        }

        // Detect the port from APP_URL
        $appUrl = config('app.url');
        $port = 8000; // Default port

        if (preg_match('/:(\d+)/', $appUrl, $matches)) {
            $port = (int) $matches[1];
        }

        // Start ngrok
        Log::info("Ngrok auto-starting on port {$port}...");
        $result = $this->start($port);

        if ($result['success']) {
            Log::info("Ngrok auto-started successfully: {$result['url']}");

            // Update Stripe webhook if configured
            $this->updateStripeWebhook($result['url']);
        } else {
            Log::warning("Ngrok auto-start failed: {$result['message']}");
        }
    }

    /**
     * Update Stripe webhook with new ngrok URL
     */
    protected function updateStripeWebhook(string $ngrokUrl): void
    {
        try {
            // Check if Stripe is configured
            $stripeKey = null;
            try {
                $stripeKey = Setting::getValue('stripe.test.secret_key') ?? config('wave.stripe.secret_key');
            } catch (\Exception $e) {
                // Settings table might not be available yet
                return;
            }

            if (empty($stripeKey)) {
                return;
            }

            $stripe = new \Stripe\StripeClient($stripeKey);
            $webhooks = $stripe->webhookEndpoints->all(['limit' => 10]);

            $newWebhookUrl = rtrim($ngrokUrl, '/') . '/stripe/webhook';

            // Find and update existing ngrok webhook
            foreach ($webhooks->data as $webhook) {
                if (str_contains($webhook->url, 'ngrok-free.app') || str_contains($webhook->url, 'ngrok.io')) {
                    $stripe->webhookEndpoints->update($webhook->id, [
                        'url' => $newWebhookUrl
                    ]);
                    Log::info("Stripe webhook updated: {$newWebhookUrl}");
                    return;
                }
            }

            Log::info('No ngrok webhook found to update');
        } catch (\Exception $e) {
            Log::warning('Failed to update Stripe webhook: ' . $e->getMessage());
        }
    }
}
