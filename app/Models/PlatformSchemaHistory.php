<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformSchemaHistory extends Model
{
    protected $table = 'platform_schema_history';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'platform_schema_id',
        'action',
        'old_data',
        'new_data',
        'version',
        'change_description',
        'changed_by_user_id',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Get the platform schema that owns this history record
     */
    public function platformSchema(): BelongsTo
    {
        return $this->belongsTo(PlatformSchema::class);
    }

    /**
     * Get the user who made the change
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }

    /**
     * Log a schema change
     */
    public static function logChange(
        int $schemaId,
        string $action,
        ?array $oldData = null,
        ?array $newData = null,
        string $version = '1.0.0',
        ?string $description = null,
        ?int $userId = null
    ): void {
        self::create([
            'platform_schema_id' => $schemaId,
            'action' => $action,
            'old_data' => $oldData,
            'new_data' => $newData,
            'version' => $version,
            'change_description' => $description,
            'changed_by_user_id' => $userId,
            'created_at' => now(),
        ]);
    }
}
