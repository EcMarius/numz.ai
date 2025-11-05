<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use App\Models\User;
use Wave\Setting;
use Filament\Notifications\Notification;

class BYOAPIManagement extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationLabel = 'API Key Management';

    protected static ?int $navigationSort = 105;

    public $byoapiRequired = false;
    public $stats = [];

    public function getView(): string
    {
        return 'filament.pages.byoapi-management';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'System';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public function mount(): void
    {
        // Load current setting value
        $this->byoapiRequired = (bool) setting('site.bring_your_api_key_required', false);

        // Load statistics
        $this->loadStats();
    }

    public function loadStats(): void
    {
        $totalUsers = User::count();

        // Users with custom Reddit API
        $redditApiUsers = User::where('reddit_use_custom_api', true)->count();
        $redditApiPercentage = $totalUsers > 0 ? round(($redditApiUsers / $totalUsers) * 100, 1) : 0;

        // Users with custom X API
        $xApiUsers = User::where('x_use_custom_api', true)->count();
        $xApiPercentage = $totalUsers > 0 ? round(($xApiUsers / $totalUsers) * 100, 1) : 0;

        // Users with any custom API
        $anyCustomApiUsers = User::where('reddit_use_custom_api', true)
            ->orWhere('x_use_custom_api', true)
            ->count();
        $anyCustomApiPercentage = $totalUsers > 0 ? round(($anyCustomApiUsers / $totalUsers) * 100, 1) : 0;

        // Users with connected platforms but missing API keys (if enforcement is ON)
        $usersWithoutRequiredKeys = 0;
        if ($this->byoapiRequired) {
            $usersWithConnections = \Wave\Plugins\EvenLeads\Models\PlatformConnection::distinct('user_id')
                ->whereIn('platform', ['reddit', 'x'])
                ->pluck('user_id');

            foreach ($usersWithConnections as $userId) {
                $user = User::find($userId);
                if (!$user) continue;

                $userConnections = \Wave\Plugins\EvenLeads\Models\PlatformConnection::where('user_id', $userId)
                    ->whereIn('platform', ['reddit', 'x'])
                    ->pluck('platform')
                    ->unique()
                    ->toArray();

                $needsApiKey = false;
                if (in_array('reddit', $userConnections) && !$user->reddit_use_custom_api) {
                    $needsApiKey = true;
                }
                if (in_array('x', $userConnections) && !$user->x_use_custom_api) {
                    $needsApiKey = true;
                }

                if ($needsApiKey) {
                    $usersWithoutRequiredKeys++;
                }
            }
        }

        $this->stats = [
            'total_users' => $totalUsers,
            'reddit_api_users' => $redditApiUsers,
            'reddit_api_percentage' => $redditApiPercentage,
            'x_api_users' => $xApiUsers,
            'x_api_percentage' => $xApiPercentage,
            'any_custom_api_users' => $anyCustomApiUsers,
            'any_custom_api_percentage' => $anyCustomApiPercentage,
            'users_without_required_keys' => $usersWithoutRequiredKeys,
        ];
    }

    public function toggleBYOAPIRequirement(): void
    {
        $this->byoapiRequired = !$this->byoapiRequired;

        Setting::updateOrCreate(
            ['key' => 'site.bring_your_api_key_required'],
            ['value' => $this->byoapiRequired ? '1' : '0']
        );

        $this->loadStats();

        Notification::make()
            ->title('Setting Updated')
            ->body('BYOAPI requirement has been ' . ($this->byoapiRequired ? 'enabled' : 'disabled') . '.')
            ->success()
            ->send();
    }

    public function getUsersWithApiKeys()
    {
        return User::select(['id', 'name', 'email', 'reddit_use_custom_api', 'x_use_custom_api'])
            ->where(function($query) {
                $query->where('reddit_use_custom_api', true)
                      ->orWhere('x_use_custom_api', true);
            })
            ->orderBy('name')
            ->get();
    }

    public function getAllUsersWithConnections()
    {
        $usersWithConnections = \Wave\Plugins\EvenLeads\Models\PlatformConnection::distinct('user_id')
            ->whereIn('platform', ['reddit', 'x'])
            ->pluck('user_id');

        return User::whereIn('id', $usersWithConnections)
            ->select(['id', 'name', 'email', 'reddit_use_custom_api', 'x_use_custom_api'])
            ->orderBy('name')
            ->get();
    }
}
