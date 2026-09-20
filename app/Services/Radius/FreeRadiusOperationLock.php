<?php

namespace App\Services\Radius;

use Illuminate\Cache\Lock;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class FreeRadiusOperationLock
{
    public function acquire(int $seconds = 30): Lock
    {
        $lock = Cache::lock('xd-radius:freeradius-operation', $seconds);
        try {
            $lock->block(30);
        } catch (\Throwable $e) {
            throw new RuntimeException('Operasi FreeRADIUS lain sedang berjalan. Perubahan belum dapat memperoleh lock.', 0, $e);
        }
        return $lock;
    }
}
