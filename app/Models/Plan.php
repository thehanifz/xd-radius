<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Plan extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'plans';

    protected $fillable = [
        'name',
        'type',
        'price',
        'download_speed_kbps',
        'upload_speed_kbps',
        'duration_days',
        'data_quota_mb',
        'radius_group_name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'price'               => 'integer',
        'download_speed_kbps' => 'integer',
        'upload_speed_kbps'   => 'integer',
        'duration_days'       => 'integer',
        'data_quota_mb'       => 'integer',
        'is_active'           => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->setDescriptionForEvent(
            fn(string $eventName) => "Paket '{$this->name}' {$eventName}"
        );
    }

    // --- Accessors ---

    public function getDownloadLabelAttribute(): string
    {
        return $this->download_speed_kbps >= 1000
            ? ($this->download_speed_kbps / 1000) . ' Mbps'
            : $this->download_speed_kbps . ' Kbps';
    }

    public function getUploadLabelAttribute(): string
    {
        return $this->upload_speed_kbps >= 1000
            ? ($this->upload_speed_kbps / 1000) . ' Mbps'
            : $this->upload_speed_kbps . ' Kbps';
    }

    public function getPriceLabelAttribute(): string
    {
        return 'Rp ' . number_format($this->price, 0, ',', '.');
    }

    public function getQuotaLabelAttribute(): ?string
    {
        if (!$this->data_quota_mb) return null;
        return $this->data_quota_mb >= 1024
            ? ($this->data_quota_mb / 1024) . ' GB'
            : $this->data_quota_mb . ' MB';
    }

    // --- Scopes ---
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVoucher($query)
    {
        return $query->where('type', 'voucher');
    }

    public function scopeMember($query)
    {
        return $query->where('type', 'member');
    }
}
