<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use Wave\Plugins\EvenLeads\Models\PlatformConnection;
use Illuminate\Support\Facades\Auth;

class ConnectedPlatforms extends Component
{
    public $platforms = [];
    public $maxAccountsPerPlatform = 20;

    public function mount()
    {
        $this->loadPlatforms();
        $this->loadMaxAccounts();
    }

    protected function loadMaxAccounts()
    {
        $user = Auth::user();
        $subscription = $user->subscription('default');

        if ($subscription && $subscription->plan) {
            $this->maxAccountsPerPlatform = $subscription->plan->max_accounts_per_platform ?? 20;
        }
    }

    protected function loadPlatforms()
    {
        $user = Auth::user();
        $accounts = \Wave\Plugins\EvenLeads\Models\PlatformConnection::where('user_id', $user->id)
            ->active()
            ->orderBy('platform')
            ->orderBy('created_at', 'desc')
            ->get();

        // Group accounts by platform
        $grouped = $accounts->groupBy('platform');

        $this->platforms = $grouped->map(function ($platformAccounts, $provider) {
            return [
                'name' => $provider,
                'display_name' => ucfirst($provider),
                'accounts' => $platformAccounts->map(function ($account) {
                    $metadata = $account->metadata ?? [];
                    $accountName = $metadata['username'] ?? $metadata['name'] ?? $account->account_name ?? 'Account';

                    return [
                        'id' => $account->id,
                        'name' => $accountName,
                        'email' => $metadata['email'] ?? null,
                        'avatar' => $metadata['avatar'] ?? null,
                        'is_primary' => false,
                        'connected_at' => $account->created_at->diffForHumans(),
                        'token_expired' => $account->isExpired(),
                    ];
                })->toArray(),
                'count' => $platformAccounts->count(),
            ];
        })->toArray();
    }

    public function setPrimary($accountId)
    {
        // Primary functionality not implemented for PlatformConnection yet
        // Can be added later if needed
        $this->dispatch('notify', [
            'type' => 'info',
            'message' => 'Primary account feature coming soon.'
        ]);
    }

    public function disconnect($accountId)
    {
        $account = PlatformConnection::where('id', $accountId)
            ->where('user_id', Auth::id())
            ->first();

        if (!$account) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Account not found.'
            ]);
            return;
        }

        $account->delete();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Account disconnected successfully.'
        ]);

        $this->loadPlatforms();
    }

    public function connectAnother($platform)
    {
        // Check if user has reached the limit
        $currentCount = \Wave\Plugins\EvenLeads\Models\PlatformConnection::where('user_id', Auth::id())
            ->where('platform', $platform)
            ->count();

        if ($currentCount >= $this->maxAccountsPerPlatform) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => "You have reached the maximum number of accounts for {$platform} ({$this->maxAccountsPerPlatform})."
            ]);
            return;
        }

        // Redirect to EvenLeads OAuth (not Socialite)
        $currentUrl = request()->fullUrl();
        return redirect('/oauth/' . $platform . '?return_to=' . urlencode($currentUrl));
    }

    public function render()
    {
        return view('livewire.settings.connected-platforms');
    }
}
