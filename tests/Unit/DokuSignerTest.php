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

    public function test_snap_notification_signature_matches_request_notification_formula(): void
    {
        $body = '{"trxId":"MEM-2-euurddglqy","paidAmount":{"value":"150000.00","currency":"IDR"}}';
        $timestamp = '2026-09-27T07:00:00+07:00';
        $token = 'access-token';
        $secret = 'secret';

        $signature = DokuSigner::snapNotificationSignature(
            'POST',
            '/payments/notifications',
            $token,
            $body,
            $timestamp,
            $secret,
        );

        $hash = hash('sha256', DokuSigner::minifyJson($body));
        $stringToSign = 'POST:/payments/notifications:' . $token . ':' . strtolower($hash) . ':' . $timestamp;
        $expected = base64_encode(hash_hmac('sha512', $stringToSign, $secret, true));

        $this->assertSame($expected, $signature);
    }
    public function test_notification_headers_can_be_scalarized_from_framework_header_arrays(): void
    {
        $headers = [
            'X-EXTERNAL-ID' => ['external-123'],
            'X-SIGNATURE' => ['signature-456'],
            'Authorization' => ['Bearer access-token'],
        ];

        $normalized = [];
        foreach ($headers as $key => $value) {
            $normalized[strtolower($key)] = is_array($value)
                ? (string) ($value[0] ?? '')
                : (string) $value;
        }

        $this->assertSame('external-123', $normalized['x-external-id']);
        $this->assertSame('signature-456', $normalized['x-signature']);
        $this->assertSame('Bearer access-token', $normalized['authorization']);
    }

}
