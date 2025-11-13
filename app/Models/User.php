<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Wave\Traits\HasProfileKeyValues;
use Wave\User as WaveUser;

class User extends WaveUser implements MustVerifyEmail
{
    use HasApiTokens, HasProfileKeyValues, Notifiable, HasRoles;

    public $guard_name = 'web';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'username',
        'avatar',
        'password',
        'role_id',
        'verification_code',
        'verified',
        'has_smart_search',
        'bypass_post_sync_limit',
        'bypass_campaign_sync_limit',
        'bypass_ai_reply_limit',
        'trial_ends_at',
        'occupation',
        'company_name',
        'company_size',
        'industry',
        'referral_source',
        'onboarding_completed',
        'country',
        'email_verified_at',
        'accepted_terms_at',
        'organization_id',
        'team_role',
        'growth_hack_prospect_id',
        'trial_activated_at',
        'is_growth_hack_account',
        'email_on_leads_found',
        'reddit_client_id',
        'reddit_client_secret',
        'reddit_use_custom_api',
        'x_client_id',
        'x_client_secret',
        'x_use_custom_api',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'reddit_client_id',
        'reddit_client_secret',
        'x_client_id',
        'x_client_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'accepted_terms_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'trial_activated_at' => 'datetime',
        'onboarding_completed' => 'boolean',
        'email_on_leads_found' => 'boolean',
        'has_smart_search' => 'boolean',
        'bypass_post_sync_limit' => 'boolean',
        'bypass_campaign_sync_limit' => 'boolean',
        'bypass_ai_reply_limit' => 'boolean',
        'is_growth_hack_account' => 'boolean',
        'reddit_use_custom_api' => 'boolean',
        'reddit_client_id' => 'encrypted',
        'reddit_client_secret' => 'encrypted',
        'x_use_custom_api' => 'boolean',
        'x_client_id' => 'encrypted',
        'x_client_secret' => 'encrypted',
    ];

    protected static function boot()
    {
        parent::boot();

        // Listen for the creating event of the model
        static::creating(function ($user) {
            // Check if the username attribute is empty
            if (empty($user->username)) {
                // Use the name to generate a slugified username
                $username = Str::slug($user->name, '');
                $i = 1;
                while (self::where('username', $username)->exists()) {
                    $username = Str::slug($user->name, '').$i;
                    $i++;
                }
                $user->username = $username;
            }
        });

        // Listen for the created event of the model
        static::created(function ($user) {
            // Remove all roles
            $user->syncRoles([]);
            // Assign the default role
            $user->assignRole(config('wave.default_user_role', 'registered'));
        });
    }

    /**
     * Determine if the user has verified their email address.
     * Checks both email_verified_at (Laravel) and verified (Wave) columns
     */
    public function hasVerifiedEmail()
    {
        return ! is_null($this->email_verified_at) || $this->verified == 1;
    }

    /**
     * Mark the given user's email as verified.
     * Sets both email_verified_at (Laravel) and verified (Wave) columns
     */
    public function markEmailAsVerified()
    {
        $this->forceFill([
            'email_verified_at' => $this->freshTimestamp(),
            'verified' => 1,
        ])->save();

        return true;
    }

    /**
     * Send the email verification notification.
     * Override to use our custom notification without signed URLs
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new \Wave\Notifications\VerifyEmail($this));
    }

    /**
     * Get the user's avatar URL with fallback to default profile photo setting
     */
    public function avatar()
    {
        // Check if user has a custom avatar (not null, not empty string, not the old default value)
        if (!empty($this->avatar) &&
            $this->avatar !== '' &&
            trim($this->avatar) !== '' &&
            $this->avatar !== 'demo/default.png') {
            return \Storage::url($this->avatar);
        }

        // Use setting for default profile photo with fallback
        $defaultPhoto = setting('site.default_profile_photo', '/storage/demo/default.png');

        // Defensive handling for malformed paths
        // If path doesn't start with / or http, assume it's relative to storage
        if (!str_starts_with($defaultPhoto, '/') && !str_starts_with($defaultPhoto, 'http')) {
            // If it says "storage/demo/default.png", prepend /
            $defaultPhoto = '/' . $defaultPhoto;
        }

        // If path has malformed prefix like "campaigns/", fix it
        if (str_contains($defaultPhoto, '/campaigns/')) {
            $defaultPhoto = str_replace('/campaigns/', '/storage/', $defaultPhoto);
        }

        return url($defaultPhoto);
    }

    /**
     * Get the organization the user belongs to
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the organization the user owns
     */
    public function ownedOrganization(): HasOne
    {
        return $this->hasOne(Organization::class, 'owner_id');
    }

    /**
     * Check if user is an organization owner
     */
    public function isOrganizationOwner(): bool
    {
        return $this->team_role === 'owner' && $this->ownedOrganization()->exists();
    }

    /**
     * Check if user is a team member
     */
    public function isTeamMember(): bool
    {
        return $this->team_role === 'member' && $this->organization_id !== null;
    }

    /**
     * Get the growth hacking prospect this user was created from
     */
    public function growthHackProspect(): BelongsTo
    {
        return $this->belongsTo(\App\Models\GrowthHackingProspect::class, 'growth_hack_prospect_id');
    }

    /**
     * Get all campaigns for this user
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany(\Wave\Plugins\EvenLeads\Models\Campaign::class);
    }

    /**
     * Get all sync history records for this user
     */
    public function sync_history(): HasMany
    {
        return $this->hasMany(\Wave\Plugins\EvenLeads\Models\SyncHistory::class);
    }

    /**
     * Get all data deletion requests for this user
     */
    public function dataDeletionRequests(): HasMany
    {
        return $this->hasMany(\App\Models\DataDeletionRequest::class);
    }

    /**
     * Get the pending data deletion request for this user
     */
    public function pendingDeletionRequest()
    {
        return $this->dataDeletionRequests()
            ->where('status', 'pending')
            ->latest()
            ->first();
    }

    /**
     * Check if user has a pending data deletion request
     */
    public function hasPendingDeletionRequest(): bool
    {
        return $this->dataDeletionRequests()
            ->where('status', 'pending')
            ->exists();
    }

    /**
     * Check if user has a seated plan subscription
     */
    public function hasSeatedPlan(): bool
    {
        $subscription = \Wave\Subscription::where('billable_id', $this->id)
            ->where('billable_type', 'user')
            ->where('status', 'active')
            ->with('plan')
            ->first();

        if (!$subscription || !$subscription->plan) {
            return false;
        }

        return $subscription->plan->is_seated_plan ?? false;
    }

    /**
     * Check if user needs to setup organization
     */
    public function needsOrganizationSetup(): bool
    {
        try {
            // Team members never need to setup organization
            if ($this->isTeamMember()) {
                return false;
            }

            // If user already has an organization (check both field and relationship)
            // Check organization_id field first to avoid relationship loading issues
            if ($this->organization_id !== null) {
                return false;
            }

            // Also check if they own an organization
            if ($this->ownedOrganization()->exists()) {
                return false;
            }

            // Check if user has seated plan subscription that requires organization
            $subscription = \Wave\Subscription::where('billable_id', $this->id)
                ->where('billable_type', 'user')
                ->where('status', 'active')
                ->with('plan')
                ->first();

            // No active subscription = no org setup needed
            if (!$subscription) {
                return false;
            }

            // No plan or not a seated plan = no org setup needed
            if (!$subscription->plan || !$subscription->plan->is_seated_plan) {
                return false;
            }

            // Check requires_organization flag (defaults to true for new subscriptions)
            // Existing users have this set to false (grandfathered in)
            $requiresOrg = $subscription->requires_organization ?? true;

            return $requiresOrg;

        } catch (\Exception $e) {
            \Log::error('Error in needsOrganizationSetup', [
                'user_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            // Default to false on error to prevent locking out users
            return false;
        }
    }

    /**
     * Get all activity logs for this user
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(\App\Models\ActivityLog::class);
    }

    /**
     * Get all audit logs for this user
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(\App\Models\AuditLog::class);
    }

    /**
     * Get all consent logs for this user
     */
    public function consentLogs(): HasMany
    {
        return $this->hasMany(\App\Models\ConsentLog::class);
    }

    /**
     * Get the user's active sessions
     */
    public function sessions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\Illuminate\Support\Facades\DB::table('sessions')->getModel());
    }
}
