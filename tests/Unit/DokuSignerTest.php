<?php

namespace Tests\Unit;

use App\Services\Payments\Doku\DokuSigner;
use Tests\TestCase;

class DokuSignerTest extends TestCase
{
    public function test_snap_request_signature_matches_deterministic_vector(): void
    {
        $body = '{"partnerReferenceNo":"INV-1","amount":{"value":"1000.00","currency":"IDR"}}';

        $signature = DokuSigner::snapRequestSignature(
            'POST',
            '/virtual-accounts/bi-snap-va/v1.1/transfer-va/create-va',
            'access-token',
            $body,
            '2026-09-23T06:00:00+07:00',
            'secret',
        );

        $this->assertSame(
            'bea4dbafc3e1743af35be500c344fdf0e4e2b6294286b421ce38d046e012f0e2c83df375a120696bbed39576c896fb4e740cfbb44043256f7edeeefdd508aadd',
            $signature,
        );
    }
}
