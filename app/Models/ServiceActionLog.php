<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceActionLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'entity_type', 'entity_id', 'action',
        'previous_status', 'new_status',
        'performed_by', 'performed_at', 'notes',
    ];

    protected $casts = [
        'performed_at' => 'datetime',
    ];

    public static function record(
        string $entityType,
        int $entityId,
        string $action,
        ?string $prev,
        string $next,
        ?int $userId = null,
        ?string $notes = null
    ): self {
        return static::create([
            'entity_type'     => $entityType,
            'entity_id'       => $entityId,
            'action'          => $action,
            'previous_status' => $prev,
            'new_status'      => $next,
            'performed_by'    => $userId,
            'performed_at'    => now(),
            'notes'           => $notes,
        ]);
    }
}
