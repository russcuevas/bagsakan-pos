<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'before_values',
        'after_values',
        'reason',
        'ip_address',
    ];

    protected $casts = [
        'before_values' => 'array',
        'after_values' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function log(string $action, ?string $modelType = null, ?int $modelId = null, ?array $before = null, ?array $after = null, ?string $reason = null): void
    {
        try {
            self::create([
                'user_id' => auth()->id(),
                'action' => $action,
                'model_type' => $modelType,
                'model_id' => $modelId,
                'before_values' => $before,
                'after_values' => $after,
                'reason' => $reason,
                'ip_address' => request()->ip(),
            ]);
        } catch (\Exception $e) {
            // Log silently
        }
    }
}
