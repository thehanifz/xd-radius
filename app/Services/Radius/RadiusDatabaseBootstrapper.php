<?php

namespace App\Services\Radius;

use PDO;
use RuntimeException;
use Throwable;

class RadiusDatabaseBootstrapper
{
    public function ensureDatabase(): array
    {
        $database = env('RADIUS_DB_DATABASE', env('DB_DATABASE', 'laravel'));
        $connection = env('RADIUS_DB_CONNECTION', env('DB_CONNECTION', 'pgsql'));
        if ($connection !== 'pgsql') return ['created' => false, 'exists' => true];

        try {
            $pdo = new PDO($this->dsn('postgres'), env('RADIUS_DB_USERNAME', env('DB_USERNAME', 'root')), env('RADIUS_DB_PASSWORD', env('DB_PASSWORD', '')), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $stmt = $pdo->prepare('SELECT 1 FROM pg_database WHERE datname = :name');
            $stmt->execute(['name' => $database]);
            if ($stmt->fetchColumn()) return ['created' => false, 'exists' => true];
            if (! $pdo->query('SELECT rolcreatedb FROM pg_roles WHERE rolname = current_user')->fetchColumn()) throw new RuntimeException('Akun PostgreSQL tidak memiliki privilege CREATEDB.');
            $pdo->exec('CREATE DATABASE ' . $this->quoteIdentifier($database));
            return ['created' => true, 'exists' => true];
        } catch (Throwable $e) {
            throw new RuntimeException('RADIUS database belum tersedia dan akun PostgreSQL tidak memiliki privilege CREATEDB atau koneksi admin gagal: ' . $e->getMessage(), 0, $e);
        }
    }

    private function dsn(string $database): string
    {
        return sprintf('pgsql:host=%s;port=%s;dbname=%s', env('RADIUS_DB_HOST', env('DB_HOST', '127.0.0.1')), env('RADIUS_DB_PORT', env('DB_PORT', '5432')), $database);
    }

    private function quoteIdentifier(string $value): string
    {
        if (! preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $value)) throw new RuntimeException('Nama database RADIUS tidak valid.');
        return '"' . $value . '"';
    }
}
