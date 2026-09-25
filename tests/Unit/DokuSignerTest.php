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

    public function test_http_notification_signature_matches_doku_format(): void
    {
        $body = '{"trxId":"MEM-2-euurddglqy","paidAmount":{"value":"150000.00","currency":"IDR"}}';

        $signature = DokuSigner::httpNotificationSignature(
            'MCH-0001-10791114622547',
            'cc682442-6c22-493e-8121-b9ef6b3fa728',
            '2020-08-11T08:45:42Z',
            '/doku-virtual-account/v2/payment-code',
            $body,
            'secret',
        );

        $digest = base64_encode(hash('sha256', $body, true));
        $components = implode("\n", [
            'Client-Id:MCH-0001-10791114622547',
            'Request-Id:cc682442-6c22-493e-8121-b9ef6b3fa728',
            'Request-Timestamp:2020-08-11T08:45:42Z',
            'Request-Target:/doku-virtual-account/v2/payment-code',
            'Digest:' . $digest,
        ]);
        $expected = 'HMACSHA256=' . base64_encode(hash_hmac('sha256', $components, 'secret', true));

        $this->assertSame($expected, $signature);
        $this->assertStringStartsWith('HMACSHA256=', $signature);
    }

}
