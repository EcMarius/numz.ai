<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Wave\Plugins\EvenLeads\Models\Campaign;
use Wave\Plugins\EvenLeads\Models\Lead;
use Wave\Plugins\EvenLeads\Models\AIGeneration;
use Wave\Plugins\EvenLeads\Models\SyncHistory;
use Carbon\Carbon;

class UserSubscriptionDetailsWidget extends Widget
{
    public ?\Illuminate\Database\Eloquent\Model $record = null;

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 1;

    protected static bool $isDiscovered = false;

    public function mount(): void
    {
        $this->view = $this->getView();
    }

    public function getView(): string
    {
        return 'filament.widgets.user-subscription-details';
    }

    public function getUserData(): array
    {
        if (!$this->record) {
            return [
                'subscription' => null,
                'plan' => null,
                'trial' => null,
                'billing' => null,
                'usage' => null,
                'activity' => null,
            ];
        }

        $subscription = $this->record->subscriptions()->with('plan')->first();

        $data = [
            'subscription' => null,
            'plan' => null,
            'trial' => null,
            'billing' => null,
            'usage' => null,
            'activity' => null,
        ];

        // Subscription Details
        if ($subscription) {
            $data['subscription'] = [
                'status' => ucfirst($subscription->status),
                'vendor' => ucfirst($subscription->vendor_slug ?? 'N/A'),
                'vendor_customer_id' => $subscription->vendor_customer_id ?? 'N/A',
                'vendor_subscription_id' => $subscription->vendor_subscription_id ?? 'N/A',
                'seats' => $subscription->seats ?? 1,
                'cancel_url' => $subscription->cancel_url,
                'update_url' => $subscription->update_url,
                'cancelled_at' => $subscription->cancelled_at ? Carbon::parse($subscription->cancelled_at)->format('M d, Y H:i') : null,
                'cancellation_reason' => $subscription->cancellation_reason ?? null,
                'cancellation_details' => $subscription->cancellation_details ?? null,
                'days_since_cancelled' => $subscription->cancelled_at ? Carbon::parse($subscription->cancelled_at)->diffInDays(now()) : null,
            ];

            // Plan Details
            if ($subscription->plan) {
                $plan = $subscription->plan;
                $data['plan'] = [
                    'name' => $plan->name,
                    'description' => $plan->description ?? 'N/A',
                    'price' => isset($plan->price) ? '$' . number_format($plan->price / 100, 2) : 'N/A',
                    'cycle' => $subscription->cycle ?? 'N/A',
                    'features' => $plan->features ?? [],
                ];
            }

            // Trial Information
            if ($subscription->trial_ends_at) {
                $trialEnds = Carbon::parse($subscription->trial_ends_at);
                $data['trial'] = [
                    'status' => $trialEnds->isFuture() ? 'Active' : 'Ended',
                    'ends_at' => $trialEnds->format('M d, Y H:i'),
                    'days_remaining' => $trialEnds->isFuture() ? $trialEnds->diffInDays(now()) : 0,
                    'is_active' => $trialEnds->isFuture(),
                ];
            }

            // Billing Information
            $data['billing'] = [
                'last_payment' => $subscription->last_payment_at ? Carbon::parse($subscription->last_payment_at)->format('M d, Y H:i') : 'N/A',
                'next_payment' => $subscription->next_payment_at ? Carbon::parse($subscription->next_payment_at)->format('M d, Y H:i') : 'N/A',
                'days_until_next' => $subscription->next_payment_at ? Carbon::parse($subscription->next_payment_at)->diffInDays(now()) : null,
            ];
        }

        // EvenLeads Usage Stats
        $startOfMonth = Carbon::now()->startOfMonth();

        $data['usage'] = [
            'campaigns_total' => Campaign::where('user_id', $this->record->id)->count(),
            'campaigns_active' => Campaign::where('user_id', $this->record->id)->where('status', 'active')->count(),
            'leads_total' => Lead::where('user_id', $this->record->id)->count(),
            'leads_this_month' => Lead::where('user_id', $this->record->id)->where('created_at', '>=', $startOfMonth)->count(),
            'leads_strong' => Lead::where('user_id', $this->record->id)->where('confidence_score', '>=', 8)->count(),
            'leads_new' => Lead::where('user_id', $this->record->id)->where('status', 'new')->count(),
            'leads_contacted' => Lead::where('user_id', $this->record->id)->where('status', 'contacted')->count(),
            'leads_closed' => Lead::where('user_id', $this->record->id)->where('status', 'closed')->count(),
            'manual_syncs_this_month' => SyncHistory::where('user_id', $this->record->id)->where('sync_type', 'manual')->where('created_at', '>=', $startOfMonth)->count(),
            'ai_generations_this_month' => AIGeneration::where('user_id', $this->record->id)->where('created_at', '>=', $startOfMonth)->count(),
        ];

        // Activity Timeline
        $lastCampaign = Campaign::where('user_id', $this->record->id)->latest()->first();
        $lastLead = Lead::where('user_id', $this->record->id)->latest()->first();
        $lastSync = SyncHistory::where('user_id', $this->record->id)->latest()->first();

        $data['activity'] = [
            'last_login' => $this->record->last_login_at ? Carbon::parse($this->record->last_login_at)->diffForHumans() : 'Never',
            'last_campaign' => $lastCampaign ? $lastCampaign->created_at->diffForHumans() : 'No campaigns',
            'last_lead' => $lastLead ? $lastLead->created_at->diffForHumans() : 'No leads',
            'last_sync' => $lastSync ? $lastSync->created_at->diffForHumans() : 'Never synced',
        ];

        return $data;
    }
}
