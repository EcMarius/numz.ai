<?php

namespace App\Services\GrowthHacking;

use App\Models\GrowthHackingProspect;
use Wave\Plugins\EvenLeads\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmailContentGeneratorService
{
    /**
     * Generate personalized, non-spammy email for a prospect
     */
    public function generateEmail(GrowthHackingProspect $prospect): array
    {
        try {
            $apiKey = Setting::getValue('openai_api_key');

            if (!$apiKey) {
                throw new \Exception('OpenAI API key not configured');
            }

            // Get top lead to mention in email
            $topLead = $prospect->leads()->orderBy('confidence_score', 'desc')->first();

            $prompt = $this->buildEmailPrompt($prospect, $topLead);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a helpful colleague sending a personalized, non-salesy email about a genuine opportunity. Write naturally and conversationally, never sound like marketing automation. Return only valid JSON.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.7, // Higher temperature for more human-like writing
                'max_tokens' => 400,
            ]);

            if (!$response->successful()) {
                throw new \Exception('OpenAI API request failed');
            }

            $result = $response->json();
            $content = $result['choices'][0]['message']['content'] ?? null;

            if (!$content) {
                throw new \Exception('Empty response from OpenAI');
            }

            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON response from AI');
            }

            return [
                'success' => true,
                'subject' => $data['subject'] ?? '',
                'body' => $data['body'] ?? '',
            ];

        } catch (\Exception $e) {
            Log::error("Email generation failed for prospect {$prospect->id}", [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Build email prompt (CRITICAL - must not sound spammy)
     */
    protected function buildEmailPrompt(GrowthHackingProspect $prospect, $topLead = null): string
    {
        $businessName = $prospect->business_name ?? 'your business';
        $contactName = $prospect->display_name;
        $industry = $prospect->industry;
        $description = $prospect->business_description;
        $leadsCount = $prospect->leads_found;

        $leadExample = '';
        if ($topLead) {
            $leadExample = "One specific lead: \"" . substr($topLead->title, 0, 100) . "...\"";
        }

        return <<<PROMPT
Write a professional, personalized email to {$contactName} at {$businessName}.

**Context:**
- Their business: {$description}
- Industry: {$industry}
- We found {$leadsCount} potential clients for them on Reddit
- {$leadExample}

**CRITICAL REQUIREMENTS:**
✅ Sound 100% human-written, NOT AI or marketing automation
✅ Personalize to their specific business
✅ Be genuinely helpful, not salesy
✅ Mention 1 specific lead we found FOR THEM (make it concrete and relevant)
✅ Tone: Helpful colleague sharing a discovery, not sales pitch
✅ Keep under 120 words
✅ Use their name: {$contactName}

❌ NO buzzwords like "amazing opportunity", "revolutionary", "game-changer"
❌ NO "I hope this email finds you well" or generic openers
❌ NO hard sell or pressure
❌ NO multiple exclamation marks
❌ NO all caps words

**Subject line requirements:**
- Personal, specific, not clickbait
- Examples: "Found some leads for {$businessName}", "{$businessName}: {$leadsCount} potential clients on Reddit"
- NOT: "Amazing opportunity!", "You won't believe this"

Return ONLY a JSON object:
{
  "subject": "Email subject line",
  "body": "Email body in plain text with \\n for line breaks"
}

The email should feel like a friend reaching out with something genuinely useful, not a company blasting emails.
PROMPT;
    }

    /**
     * Convert plain text email to HTML with proper formatting
     */
    public function convertToHTML(string $plainText, string $setupUrl, string $unsubscribeUrl): string
    {
        // Convert line breaks to HTML
        $html = nl2br(htmlspecialchars($plainText));

        // Wrap in clean black/white email template
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: #FAFAFA;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #FAFAFA; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #FFFFFF; border: 1px solid #E4E4E7; border-radius: 8px;">
                    <!-- Header -->
                    <tr>
                        <td style="padding: 32px 40px; background-color: #18181B; border-radius: 8px 8px 0 0;">
                            <h1 style="margin: 0; color: #FFFFFF; font-size: 20px; font-weight: 600;">{$this->getAppName()}</h1>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding: 40px; color: #18181B; font-size: 15px; line-height: 1.6;">
                            {$html}

                            <div style="margin-top: 32px;">
                                <a href="{$setupUrl}" style="display: inline-block; padding: 14px 32px; background-color: #18181B; color: #FFFFFF; text-decoration: none; border-radius: 6px; font-weight: 500;">View Leads & Set Password</a>
                            </div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 24px 40px; border-top: 1px solid #E4E4E7; background-color: #FAFAFA;">
                            <p style="margin: 0; color: #71717A; font-size: 13px; line-height: 1.5;">
                                Not interested? <a href="{$unsubscribeUrl}" style="color: #18181B; text-decoration: underline;">Unsubscribe</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * Get app name from config
     */
    protected function getAppName(): string
    {
        return config('app.name', 'EvenLeads');
    }
}
