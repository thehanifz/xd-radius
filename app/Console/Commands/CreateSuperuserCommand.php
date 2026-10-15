<?php

namespace App\Console\Commands;

use App\Models\AppUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CreateSuperuserCommand extends Command
{
    protected $signature = 'app:create-superuser';

    protected $description = 'Buat akun superuser pertama dari terminal.';

    public function handle(): int
    {
        if (AppUser::where('role', AppUser::ROLE_SUPERUSER)->exists()) {
            $this->info('Akun superuser sudah tersedia. Tidak ada perubahan.');

            return self::SUCCESS;
        }

        $email = trim((string) $this->ask('Email / username superadmin'));
        $name = trim((string) (str_contains($email, '@') ? strstr($email, '@', true) : $email));
        $password = (string) $this->secret('Password (minimal 8 karakter)');
        $passwordConfirmation = (string) $this->secret('Ulangi password');

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ], [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'same:password_confirmation'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        if (AppUser::withTrashed()->where('email', $email)->exists()) {
            $this->error('Email tersebut sudah pernah digunakan.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($name, $email, $password): void {
            AppUser::create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'role' => AppUser::ROLE_SUPERUSER,
                'is_active' => true,
            ]);
        });

        $this->info('Akun superuser berhasil dibuat. Silakan login melalui halaman login.');

        return self::SUCCESS;
    }
}
