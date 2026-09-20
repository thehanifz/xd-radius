<?php

namespace App\Services\Radius;

use Illuminate\Support\Facades\Log;

class FreeRadiusAuditService
{
    public function record(string $operation, string $result, array $context = []): void
    {
        $safe = $this->sanitize($context);
        activity('radius-management')->withProperties(array_merge(['operation' => $operation, 'result' => $result], $safe))->log($operation);
        Log::info('xd-radius management operation', array_merge(['operation' => $operation, 'result' => $result], $safe));
    }

    private function sanitize(array $data): array
    {
        foreach (['secret', 'password', 'radius_secret', 'api_secret'] as $key) unset($data[$key]);
        return $data;
    }
}
