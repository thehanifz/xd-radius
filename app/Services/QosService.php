<?php

namespace App\Services;

use App\Models\Plan;

class QosService
{
    /**
     * Build RouterOS MikroTik-Rate-Limit value.
     * RouterOS order: rx/tx, burst rx/tx, threshold rx/tx,
     * burst-time rx/tx, priority, minimum(rx/tx).
     * From the router point of view rx=client upload and tx=client download.
     */
    public function rateLimit(Plan $plan): string
    {
        if ($plan->mikrotik_rate_limit !== null && trim($plan->mikrotik_rate_limit) !== '') {
            return trim($plan->mikrotik_rate_limit);
        }

        // Legacy fallback for plans created before the simplified field.
        $minimum = $this->pair($plan->qos_limit_at_up_kbps, $plan->qos_limit_at_down_kbps);
        $priority = $plan->qos_priority;
        if ($minimum !== null && $priority === null) {
            $priority = 8;
        }
        $parts = [
            $this->pair($plan->upload_speed_kbps, $plan->download_speed_kbps, true),
            $this->pair($plan->qos_burst_limit_up_kbps, $plan->qos_burst_limit_down_kbps),
            $this->pair($plan->qos_burst_threshold_up_kbps, $plan->qos_burst_threshold_down_kbps),
            $this->pair($plan->qos_burst_time_up_sec, $plan->qos_burst_time_down_sec, false, 's'),
            $priority,
            $minimum,
        ];
        while ($parts && end($parts) === null) { array_pop($parts); }
        return implode(' ', array_map(static fn ($part) => (string) $part, $parts));
    }

    private function pair($rx, $tx, bool $required = false, string $suffix = 'k'): ?string
    {
        if ($rx === null && $tx === null) {
            return $required ? '0k/0k' : null;
        }

        $rx = $rx !== null ? ((int) $rx) . $suffix : '';
        $tx = $tx !== null ? ((int) $tx) . $suffix : '';

        return $rx . '/' . $tx;
    }
}
