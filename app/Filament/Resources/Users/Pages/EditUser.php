<?php

namespace App\Filament\Resources\Users\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Wave\Plugins\EvenLeads\Models\Setting;
use Wave\Plugins\EvenLeads\Models\Campaign;
use Wave\Plugins\EvenLeads\Models\Lead;
use App\Models\User;
use App\Models\Organization;
use Carbon\Carbon;
use Wave\Subscription;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('resetTrial')
                ->label('Reset Trial')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Reset Trial Period')
                ->modalDescription(function () {
                    $trialDays = (int) Setting::getValue('trial_days', 7);
                    return "This will reset the user's trial period to {$trialDays} days from today. The user will be able to use trial features again.";
                })
                ->action(function () {
                    $trialDays = (int) Setting::getValue('trial_days', 7);
                    $trialPlanId = (int) Setting::getValue('trial_plan_id');

                    // Update or create subscription with new trial
                    $subscription = $this->record->subscriptions()->first();

                    if ($subscription) {
                        // Reset existing subscription trial
                        $subscription->update([
                            'trial_ends_at' => Carbon::now()->addDays($trialDays),
                            'status' => 'trialing',
                        ]);
                    } else if ($trialPlanId) {
                        // Create new trial subscription
                        $this->record->subscriptions()->create([
                            'billable_type' => User::class,
                            'billable_id' => $this->record->id,
                            'plan_id' => $trialPlanId,
                            'trial_ends_at' => Carbon::now()->addDays($trialDays),
                            'status' => 'trialing',
                        ]);
                    }

                    Notification::make()
                        ->success()
                        ->title('Trial Reset Successfully')
                        ->body("Trial period has been reset to {$trialDays} days for {$this->record->name}.")
                        ->send();
                }),
            Action::make('deleteAllCampaigns')
                ->label('Delete All Campaigns')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Delete All Campaigns')
                ->modalDescription('This will permanently delete all campaigns and their associated leads for this user. This action cannot be undone.')
                ->action(function () {
                    $campaignsCount = Campaign::where('user_id', $this->record->id)->count();
                    Campaign::where('user_id', $this->record->id)->delete();

                    Notification::make()
                        ->success()
                        ->title('Campaigns Deleted')
                        ->body("{$campaignsCount} campaign(s) and all associated leads have been deleted for {$this->record->name}.")
                        ->send();
                }),
            Action::make('deleteAllLeads')
                ->label('Delete All Leads')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Delete All Leads')
                ->modalDescription('This will permanently delete all leads for this user. Campaigns will remain intact. This action cannot be undone.')
                ->action(function () {
                    $leadsCount = Lead::whereHas('campaign', function($query) {
                        $query->where('user_id', $this->record->id);
                    })->count();

                    Lead::whereHas('campaign', function($query) {
                        $query->where('user_id', $this->record->id);
                    })->delete();

                    Notification::make()
                        ->success()
                        ->title('Leads Deleted')
                        ->body("{$leadsCount} lead(s) have been deleted for {$this->record->name}.")
                        ->send();
                }),
            Action::make('cancelSubscription')
                ->label('Cancel Subscription')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Cancel User Subscription')
                ->modalDescription(function () {
                    $subscription = $this->record->subscriptions()->where('status', 'active')->first();
                    if (!$subscription) {
                        return 'This user has no active subscription to cancel.';
                    }
                    $planName = $subscription->plan ? $subscription->plan->name : 'Unknown Plan';
                    return "This will immediately cancel the user's {$planName} subscription and delete any organization/team members if applicable. This action cannot be undone.";
                })
                ->visible(fn () => $this->record->subscriptions()->where('status', 'active')->exists())
                ->action(function () {
                    $subscription = $this->record->subscriptions()->where('status', 'active')->first();

                    if (!$subscription) {
                        Notification::make()
                            ->warning()
                            ->title('No Active Subscription')
                            ->body('This user has no active subscription to cancel.')
                            ->send();
                        return;
                    }

                    $planName = $subscription->plan ? $subscription->plan->name : 'Unknown Plan';

                    // If seated plan, handle organization cleanup
                    if ($subscription->plan && $subscription->plan->is_seated_plan) {
                        $organization = $this->record->ownedOrganization;
                        if ($organization) {
                            // Remove team members from organization (don't delete users, just unlink them)
                            $members = $organization->members;
                            $memberCount = $members->count();

                            foreach ($members as $member) {
                                // Just clear organization_id, team_role has NOT NULL constraint
                                $member->organization_id = null;
                                $member->save();
                            }

                            // Clear owner's organization reference
                            // Just clear organization_id, team_role has NOT NULL constraint
                            $this->record->organization_id = null;
                            $this->record->save();

                            // Now safe to delete organization (no FK constraints)
                            try {
                                $organization->delete();
                            } catch (\Exception $e) {
                                // If deletion fails due to FK constraints, just log it
                                // Organization will be orphaned but that's better than failing
                                \Log::warning('Could not delete organization, orphaned', [
                                    'organization_id' => $organization->id,
                                    'error' => $e->getMessage()
                                ]);
                            }

                            \Log::info('Organization cleanup completed via admin action', [
                                'admin_user' => auth()->id(),
                                'user_id' => $this->record->id,
                                'organization_id' => $organization->id,
                                'members_unlinked' => $memberCount
                            ]);
                        }
                    }

                    // Cancel subscription - manually set status if cancel() method doesn't do it
                    $subscription->status = 'cancelled';
                    $subscription->cancelled_at = now();
                    $subscription->save();

                    // Also call the cancel() method in case it does additional processing
                    try {
                        $subscription->cancel();
                    } catch (\Exception $e) {
                        // If cancel() fails, we've already set status manually above
                    }

                    // Clear user cache to ensure subscription changes are reflected
                    if (method_exists($this->record, 'clearUserCache')) {
                        $this->record->clearUserCache();
                    }

                    // DO NOT remove user roles - roles are managed separately from subscriptions

                    Notification::make()
                        ->success()
                        ->title('Subscription Cancelled')
                        ->body("The {$planName} subscription has been cancelled for {$this->record->name}. Organization and team members have been removed if applicable.")
                        ->send();

                    // Force full page reload to show updated data
                    $this->js('window.location.reload()');
                    return;
                }),
            DeleteAction::make(),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            \App\Filament\Widgets\UserStatsOverviewWidget::class,
            \App\Filament\Widgets\UserSubscriptionDetailsWidget::class,
            \App\Filament\Widgets\UserCampaignsWidget::class,
            \App\Filament\Widgets\UserLeadsWidget::class,
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\UserStatsOverviewWidget::class,
        ];
    }
}
