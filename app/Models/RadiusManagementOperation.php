<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RadiusManagementOperation extends Model
{
    protected $fillable = ['operation_id', 'type', 'status', 'target_type', 'target_id', 'message', 'details', 'started_at', 'finished_at'];
    protected $casts = ['details' => 'array', 'started_at' => 'datetime', 'finished_at' => 'datetime'];
    protected static function booted(): void
    {
        static::creating(function (self $operation) { $operation->operation_id ??= (string) Str::uuid(); });
    }
}
