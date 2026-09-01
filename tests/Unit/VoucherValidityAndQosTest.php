<?php

namespace Tests\Unit;

use App\Models\Plan;
use App\Services\QosService;
use App\Services\VoucherValidityService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class VoucherValidityAndQosTest extends TestCase
{
    public function test_hour_validity_is_calculated_from_first_login(): void
    {
        $plan = new Plan([
            'duration_value' => 3,
            'duration_unit' => 'hours',
        ]);

        $start = Carbon::parse('2026-09-01 14:30:00');
        $expiry = (new VoucherValidityService())->expiryAt($plan, $start);

        $this->assertSame('2026-09-01 17:30:00', $expiry->format('Y-m-d H:i:s'));
    }

    public function test_qos_builds_routeros_rate_limit_with_limit_at_and_burst(): void
    {
        $plan = new Plan([
            'download_speed_kbps' => 10000,
            'upload_speed_kbps' => 5000,
            'qos_limit_at_down_kbps' => 2000,
            'qos_limit_at_up_kbps' => 1000,
            'qos_burst_limit_down_kbps' => 20000,
            'qos_burst_limit_up_kbps' => 10000,
            'qos_burst_threshold_down_kbps' => 5000,
            'qos_burst_threshold_up_kbps' => 2000,
            'qos_burst_time_down_sec' => 10,
            'qos_burst_time_up_sec' => 10,
            'qos_priority' => 8,
        ]);

        $this->assertSame(
            '5000k/10000k 10000k/20000k 2000k/5000k 10s/10s 8 1000k/2000k',
            (new QosService())->rateLimit($plan)
        );
    }
}
