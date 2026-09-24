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
            '/snap-adapter/b2b/v1.0/qr/qr-mpm-generate',
            $body,
            '2026-09-23T06:00:00+07:00',
            'secret',
        );

        $this->assertSame(
            'r13vPCT0Urd+2ml6/zdHyP8lQEJsjI8uuy7PqjPKMPSwYbcTS3vg+8MkIeVUolwVmJ2DgmgSK3IqWUbjb94w8w==',
            $signature,
        );
    }
}
