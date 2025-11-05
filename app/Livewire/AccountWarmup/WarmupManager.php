<?php

namespace App\Livewire\AccountWarmup;

use Livewire\Component;
use App\Models\AccountWarmup;
use App\Services\AccountWarmupService;
use Wave\Plugins\SocialAuth\Models\SocialAccount;
use Illuminate\Support\Facades\Auth;

class WarmupManager extends Component
{
    public $accounts = [];
    public $warmups = [];
    public $showConfigModal = false;
    public $selectedAccountId;
    public $warmupSettings = [
        'scheduled_days' => 14,
        'targets' => '',
        'comment_templates' => '',
        'post_templates' => '',
    ];

    protected $listeners = ['warmupCreated' => '$refresh'];

    public function mount()
    {
        $this->loadData();
    }

    protected function loadData()
    {
        $userId = Auth::id();

        // Load all social accounts (only platforms that support warmup)
        $this->accounts = SocialAccount::where('user_id', $userId)
            ->whereIn('provider', ['reddit', 'facebook', 'twitter', 'x', 'linkedin'])
            ->get()
            ->map(function ($account) {
                $warmup = AccountWarmup::where('social_account_id', $account->id)
                    ->whereIn('status', ['pending', 'active', 'paused'])
                    ->first();

                return [
                    'id' => $account->id,
                    'platform' => $account->provider,
                    'name' => $account->getDisplayName(),
                    'is_primary' => $account->is_primary,
                    'has_warmup' => !is_null($warmup),
                    'warmup_id' => $warmup?->id,
                    'warmup' => $warmup ? [
                        'id' => $warmup->id,
                        'status' => $warmup->status,
                        'current_day' => $warmup->current_day,
                        'total_days' => $warmup->scheduled_days,
                        'progress' => $warmup->getProgressPercentage(),
                        'phase' => $warmup->current_phase,
                        'stats' => $warmup->stats ?? [],
                    ] : null,
                ];
            })->toArray();
    }

    public function openConfigModal($accountId)
    {
        $this->selectedAccountId = $accountId;
        $this->showConfigModal = true;
    }

    public function startWarmup()
    {
        $this->validate([
            'selectedAccountId' => 'required|exists:social_accounts,id',
            'warmupSettings.scheduled_days' => 'required|integer|min:7|max:30',
        ]);

        try {
            $service = app(AccountWarmupService::class);

            // Parse targets (comma or newline separated)
            $targets = array_filter(
                array_map('trim', preg_split('/[,\n]+/', $this->warmupSettings['targets'])),
                fn($t) => !empty($t)
            );

            if (empty($targets)) {
                $targets = ['AskReddit', 'CasualConversation']; // Defaults for Reddit
            }

            $warmup = $service->createWarmup(Auth::id(), $this->selectedAccountId, [
                'scheduled_days' => $this->warmupSettings['scheduled_days'],
                'targets' => $targets,
                'comment_templates' => [], // Could parse from textarea
                'post_templates' => [],
            ]);

            // Start it immediately
            $service->startWarmup($warmup->id);

            session()->flash('success', 'Account warmup started successfully!');
            $this->showConfigModal = false;
            $this->resetSettings();
            $this->loadData();

        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function pauseWarmup($warmupId)
    {
        try {
            $service = app(AccountWarmupService::class);
            $service->pauseWarmup($warmupId);
            session()->flash('success', 'Warmup paused');
            $this->loadData();
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function resumeWarmup($warmupId)
    {
        try {
            $service = app(AccountWarmupService::class);
            $service->resumeWarmup($warmupId);
            session()->flash('success', 'Warmup resumed');
            $this->loadData();
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function deleteWarmup($warmupId)
    {
        try {
            $service = app(AccountWarmupService::class);
            $service->deleteWarmup($warmupId);
            session()->flash('success', 'Warmup deleted successfully');
            $this->loadData();
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    protected function resetSettings()
    {
        $this->warmupSettings = [
            'scheduled_days' => 14,
            'targets' => '',
            'comment_templates' => '',
            'post_templates' => '',
        ];
        $this->selectedAccountId = null;
    }

    public function render()
    {
        return view('livewire.account-warmup.warmup-manager');
    }
}
