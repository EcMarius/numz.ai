<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DomainTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'domain_id',
        'user_id',
        'from_registrar_id',
        'to_registrar_id',
        'transfer_type',
        'status',
        'epp_code',
        'transfer_fee',
        'currency',
        'initiated_at',
        'completed_at',
        'cancelled_at',
        'failure_reason',
        'transfer_data',
    ];

    protected $casts = [
        'initiated_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'transfer_data' => 'array',
    ];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fromRegistrar(): BelongsTo
    {
        return $this->belongsTo(DomainRegistrar::class, 'from_registrar_id');
    }

    public function toRegistrar(): BelongsTo
    {
        return $this->belongsTo(DomainRegistrar::class, 'to_registrar_id');
    }
}
