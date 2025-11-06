<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DomainRegistration extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'domain', 'registrar', 'status', 'registration_date',
        'expiry_date', 'next_due_date', 'renewal_price', 'auto_renew',
        'nameserver1', 'nameserver2', 'nameserver3', 'nameserver4', 'epp_code'
    ];

    protected $casts = [
        'renewal_price' => 'decimal:2',
        'auto_renew' => 'boolean',
        'registration_date' => 'date',
        'expiry_date' => 'date',
        'next_due_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isExpired(): bool
    {
        return $this->expiry_date < now();
    }
}
