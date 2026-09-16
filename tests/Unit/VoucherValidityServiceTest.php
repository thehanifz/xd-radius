<?php

namespace Tests\Unit;

use App\Models\Plan;
use App\Services\VoucherValidityService;
use Carbon\Carbon;
use Tests\TestCase;

class VoucherValidityServiceTest extends TestCase
{
    public function test_hours_validity_is_calculated_from_first_login(): void
    {
        $plan = new Plan(['duration_value' => 3, 'duration_unit' => 'hours']);
        $start = Carbon::parse('2026-09-01 14:30:00');

        $this->assertSame('2026-09-01 17:30:00', app(VoucherValidityService::class)->expiryAt($plan, $start)->format('Y-m-d H:i:s'));
    }

    public function test_minutes_validity_is_calculated_from_first_login(): void
    {
        $plan = new Plan(['duration_value' => 90, 'duration_unit' => 'minutes']);
        $start = Carbon::parse('2026-09-01 14:30:00');

        $this->assertSame('2026-09-01 16:00:00', app(VoucherValidityService::class)->expiryAt($plan, $start)->format('Y-m-d H:i:s'));
    }

    public function test_member_style_month_duration_uses_calendar_month(): void
    {
        $plan = new Plan(['duration_value' => 1, 'duration_unit' => 'months']);
        $start = Carbon::parse('2026-01-31 00:00:00');

        $this->assertSame('2026-02-28 00:00:00', $plan->addDurationTo($start)->format('Y-m-d H:i:s'));
    }
}
