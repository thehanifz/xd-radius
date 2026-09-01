<?php

namespace Tests\Unit;

use App\Models\Plan;
use App\Services\QosService;
use Tests\TestCase;

class QosServiceTest extends TestCase
{
    public function test_routeros_rate_limit_maps_upload_to_rx_and_download_to_tx(): void
    {
        $plan = new Plan([
            'upload_speed_kbps' => 5120,
            'download_speed_kbps' => 10240,
            'qos_burst_limit_up_kbps' => 10240,
            'qos_burst_limit_down_kbps' => 20480,
            'qos_burst_threshold_up_kbps' => 5120,
            'qos_burst_threshold_down_kbps' => 10240,
            'qos_burst_time_up_sec' => 10,
            'qos_burst_time_down_sec' => 10,
            'qos_priority' => 8,
            'qos_limit_at_up_kbps' => 1024,
            'qos_limit_at_down_kbps' => 2048,
        ]);

        $value = app(QosService::class)->rateLimit($plan);

        $this->assertSame('5120k/10240k 10240k/20480k 5120k/10240k 10s/10s 8 1024k/2048k', $value);
    }
}
