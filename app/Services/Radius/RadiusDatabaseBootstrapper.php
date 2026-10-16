<?php

namespace App\Services\Radius;

use PDO;
use RuntimeException;
use Throwable;

class RadiusDatabaseBootstrapper
{
    public function ensureDatabase(): array
    {
        $connection = config('database.connections.radius.driver', config('database.default', 'pgsql'));
        if ($connection !== 'pgsql') return ['created' => false, 'exists' => true];

        $database = config('database.connections.radius.database', config('database.connections.pgsql.database', 'xd_radius'));
        $username = config('database.connections.radius.username', config('database.connections.pgsql.username', 'root'));
        $password = config('database.connections.radius.password', config('database.connections.pgsql.password', ''));

        try {
            $pdo = new PDO($this->dsn($database), $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            return ['created' => false, 'exists' => true];
        } catch (Throwable) {
            try {
                $pdo = new PDO($this->dsn('postgres'), $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
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
    }

    private function dsn(string $database): string
    {
        $host = config('database.connections.radius.host', config('database.connections.pgsql.host', '127.0.0.1'));
        $port = config('database.connections.radius.port', config('database.connections.pgsql.port', '5432'));
        return sprintf('pgsql:host=%s;port=%s;dbname=%s', $host, $port, $database);
    }

    private function quoteIdentifier(string $value): string
    {
        if (! preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $value)) throw new RuntimeException('Nama database RADIUS tidak valid.');
        return '"' . $value . '"';
    }
}
