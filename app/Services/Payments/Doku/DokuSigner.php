<?php

namespace App\Services\Payments\Doku;

use Illuminate\Support\Str;

class DokuSigner
{
    public static function snapTokenSignature(string $clientId, string $timestamp, string $privateKeyPem, ?string $passphrase = null): string
    {
        $privateKey = openssl_pkey_get_private($privateKeyPem, $passphrase ?? '');
        if ($privateKey === false) {
            throw new DokuException('DOKU merchant private key tidak valid.');
        }

        $signature = '';
        $ok = openssl_sign($clientId . '|' . $timestamp, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        if (! $ok) {
            throw new DokuException('Gagal membuat signature token DOKU.');
        }

        return base64_encode($signature);
    }

    public static function snapRequestSignature(
        string $method,
        string $path,
        string $accessToken,
        string $body,
        string $timestamp,
        string $secretKey,
    ): string {
        $hash = hash('sha256', self::minifyJson($body));
        $stringToSign = strtoupper($method) . ':' . $path . ':' . $accessToken . ':' . strtolower($hash) . ':' . $timestamp;

        // DOKU SNAP symmetric signature = Base64(HMAC_SHA512(stringToSign, clientSecret)).
        // Do NOT return the raw hex digest here — DOKU always expects Base64.
        return base64_encode(hash_hmac('sha512', $stringToSign, $secretKey, true));
    }

    public static function requestId(): string
    {
        return (string) Str::uuid();
    }

    public static function externalId(): string
    {
        return (string) now()->format('YmdHis') . random_int(100000, 999999);
    }

    public static function minifyJson(string $body): string
    {
        $decoded = json_decode($body, true);
        if ($decoded === null && trim($body) !== 'null') {
            return $body;
        }

        return json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
