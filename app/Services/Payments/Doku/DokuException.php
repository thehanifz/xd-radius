<?php

namespace App\Services\Payments\Doku;

use RuntimeException;

class DokuException extends RuntimeException
{
    public function __construct(string $message, public readonly ?array $response = null, public readonly int $status = 0)
    {
        parent::__construct($message, $status);
    }
}
