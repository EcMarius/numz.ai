<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DomainRenewal extends Model
{
    use HasFactory;

    protected $fillable = [
        'domain_id',
        'user_id',
        'invoice_id',
        'years_renewed',
        'previous_expiry_date',
        'new_expiry_date',
        'amount',
        'currency',
        'status',
        'notes',
    ];

    protected $casts = [
        'previous_expiry_date' => 'date',
        'new_expiry_date' => 'date',
    ];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
