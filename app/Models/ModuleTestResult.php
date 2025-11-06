<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ModuleTestResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_configuration_id',
        'tested_by',
        'test_type',
        'success',
        'message',
        'details',
        'response_time',
    ];

    protected $casts = [
        'success' => 'boolean',
        'details' => 'array',
        'response_time' => 'float',
    ];

    public function moduleConfiguration(): BelongsTo
    {
        return $this->belongsTo(ModuleConfiguration::class);
    }

    public function tester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tested_by');
    }
}
