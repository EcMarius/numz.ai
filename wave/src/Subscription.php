<?php

namespace Wave;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'billable_type',
        'billable_id',
        'plan_id',
        'vendor_slug',
        'vendor_product_id',
        'vendor_transaction_id',
        'vendor_customer_id',
        'vendor_subscription_id',
        'cycle',
        'status',
        'seats',
        'trial_ends_at',
        'ends_at',
        'last_payment_at',
        'next_payment_at',
        'current_period_start',
        'current_period_end',
        'cancel_url',
        'update_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cancelled_at' => 'datetime',
            'last_payment_at' => 'datetime',
            'next_payment_at' => 'datetime',
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
        ];
    }

    /**
     * The user that owns the subscription.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('wave.user_model', User::class), 'billable_id');
    }

    public function cancel()
    {
        $this->status = 'cancelled';

        // IMPORTANT: Clear any scheduled downgrade data
        // This prevents orphaned data and ensures clean cancellation
        $this->scheduled_plan_id = null;
        $this->scheduled_plan_date = null;
        $this->scheduled_plan_limits = null;

        $this->save();

        // DO NOT change user roles - roles are managed separately from subscriptions
        // Clear user cache after cancellation
        $this->user->clearUserCache();
    }

    /**
     * The plan that belongs to the subscription.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }
}
