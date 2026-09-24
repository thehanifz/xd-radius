<?php

namespace Tests\Unit;

use App\Services\Payments\Doku\DokuSigner;
use PHPUnit\Framework\TestCase;

class DokuSignerTest extends TestCase
{
    public function test_snap_request_signature_matches_deterministic_vector(): void
    {
        $body = json_encode(['foo' => 'bar']);

        $signature = DokuSigner::snapRequestSignature(
            'POST',
            '/virtual-accounts/bi-snap-va/v1.1/transfer-va/create-va',
            'access-token',
            $body,
            '2026-09-23T06:00:00+07:00',
            'secret',
        );

        $this->assertSame(
            'bJwtWxxF6BevW97OBZ2xWM9+A6KxUOXMYGZrho4qx/YDG+XhQwLWVOVfMykyMF2FSVEPF7N7ip6LetoYmGQtQA==',
            $signature,
        );
    }
}
