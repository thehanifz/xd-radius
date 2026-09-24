<?php

namespace App\Services\Payments\Doku;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class DokuClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $clientId,
        private readonly string $secretKey,
        private readonly ?string $privateKeyPath,
        private readonly int $timeout = 15,
    ) {}

    public static function fromConfig(): self
    {
        $config = config('doku');

        if (! $config['enabled']) {
            throw new DokuException('Integrasi DOKU belum diaktifkan. Set DOKU_ENABLED=true.');
        }

        foreach (['client_id', 'secret_key'] as $key) {
            if (! filled($config[$key])) {
                throw new DokuException('Konfigurasi DOKU belum lengkap: ' . strtoupper($key));
            }
        }

        return new self(
            rtrim($config['base_url'], '/'),
            $config['client_id'],
            $config['secret_key'],
            $config['private_key_path'],
            $config['timeout'],
        );
    }

    public function postSnap(string $path, array $payload, string $channelId = 'H2H'): array
    {
        $token = $this->accessToken();
        $timestamp = now()->format('Y-m-d\\TH:i:sP');
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $endpointUrl = $this->baseUrl . $path;
        $signature = DokuSigner::snapRequestSignature(
            'POST',
            $path,
            $token,
            $body,
            $timestamp,
            $this->secretKey,
        );

        return $this->send('POST', $path, $body, [
            'X-PARTNER-ID' => $this->clientId,
            'X-EXTERNAL-ID' => DokuSigner::externalId(),
            'X-TIMESTAMP' => $timestamp,
            'X-SIGNATURE' => $signature,
            'CHANNEL-ID' => $channelId,
            'Authorization' => 'Bearer ' . $token,
        ]);
    }

    public function accessToken(): string
    {
        $cacheKey = 'doku:b2b-token:' . sha1($this->clientId . '|' . $this->baseUrl);
        $cached = Cache::get($cacheKey);
        if (filled($cached)) {
            return $cached;
        }

        if (! $this->privateKeyPath || ! is_readable($this->privateKeyPath)) {
            throw new DokuException('DOKU merchant private key belum tersedia. Set DOKU_PRIVATE_KEY_PATH.');
        }

        $privateKey = file_get_contents($this->privateKeyPath);
        if ($privateKey === false) {
            throw new DokuException('DOKU merchant private key tidak dapat dibaca.');
        }

        $timestamp = now()->utc()->format('Y-m-d\\TH:i:s\\Z');
        $signature = DokuSigner::snapTokenSignature(
            $this->clientId,
            $timestamp,
            $privateKey,
            config('doku.private_key_passphrase'),
        );
        $path = '/authorization/v1/access-token/b2b';
        $body = json_encode(['grantType' => 'client_credentials']);

        $response = Http::timeout($this->timeout)
            ->acceptJson()
            ->withHeaders([
                'X-SIGNATURE' => $signature,
                'X-TIMESTAMP' => $timestamp,
                'X-CLIENT-KEY' => $this->clientId,
            ])
            ->withBody($body, 'application/json')
            ->post($this->baseUrl . $path);

        if (! $response->successful()) {
            throw new DokuException('DOKU token request gagal.', $response->json(), $response->status());
        }

        $json = $response->json();
        $token = data_get($json, 'accessToken');
        $expiresIn = (int) data_get($json, 'expiresIn', 900);

        if (! filled($token)) {
            throw new DokuException('DOKU token response tidak berisi accessToken.', $json, $response->status());
        }

        Cache::put($cacheKey, $token, max(60, $expiresIn - 60));
        return $token;
    }

    private function send(string $method, string $path, string $body, array $headers): array
    {
        $response = Http::timeout($this->timeout)
            ->acceptJson()
            ->withHeaders(array_merge(['Content-Type' => 'application/json'], $headers))
            ->send($method, $this->baseUrl . $path, ['body' => $body]);

        $json = $response->json();
        if (! $response->successful()) {
            $responseData = is_array($json) ? $json : null;
            $responseCode = data_get($responseData, 'responseCode');
            $responseMessage = data_get($responseData, 'responseMessage');
            $detail = collect([$responseCode, $responseMessage])
                ->filter(fn ($value) => filled($value))
                ->implode(' - ');

            throw new DokuException(
                'DOKU API request gagal.' . ($detail !== '' ? ' ' . $detail : ''),
                $responseData,
                $response->status(),
            );
        }

        return is_array($json) ? $json : ['raw' => $response->body()];
    }
}
