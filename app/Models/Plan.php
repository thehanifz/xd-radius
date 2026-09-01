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
        'duration_value',
        'duration_unit',
        'qos_limit_at_down_kbps',
        'qos_limit_at_up_kbps',
        'qos_burst_limit_down_kbps',
        'qos_burst_limit_up_kbps',
        'qos_burst_threshold_down_kbps',
        'qos_burst_threshold_up_kbps',
        'qos_burst_time_down_sec',
        'qos_burst_time_up_sec',
        'qos_priority',
        'qos_queue_type',
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
        'duration_value'     => 'integer',
        'qos_limit_at_down_kbps' => 'integer',
        'qos_limit_at_up_kbps' => 'integer',
        'qos_burst_limit_down_kbps' => 'integer',
        'qos_burst_limit_up_kbps' => 'integer',
        'qos_burst_threshold_down_kbps' => 'integer',
        'qos_burst_threshold_up_kbps' => 'integer',
        'qos_burst_time_down_sec' => 'integer',
        'qos_burst_time_up_sec' => 'integer',
        'qos_priority' => 'integer',
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


    public function getDurationLabelAttribute(): string
    {
        if ($this->duration_value === null) {
            return ($this->duration_days ?? 0) . ' hari';
        }

        return $this->duration_value . ' ' . match ($this->duration_unit) {
            'minutes' => 'menit',
            'hours' => 'jam',
            'days' => 'hari',
            default => $this->duration_unit,
        };
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
