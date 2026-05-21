<?php

namespace Database\Seeders;

use App\Models\AppUser;
use Illuminate\Database\Seeder;

class SuperUserSeeder extends Seeder
{
    public function run(): void
    {
        // Cek apakah sudah ada superuser, jika belum buat satu
        if (AppUser::where('role', AppUser::ROLE_SUPERUSER)->exists()) {
            $this->command->info('Super user sudah ada, skip.');
            return;
        }

        $user = AppUser::create([
            'name'     => 'Super Administrator',
            'email'    => 'admin@radius.local',
            'password' => 'RadiusAdmin@2026',
            'role'     => AppUser::ROLE_SUPERUSER,
            'is_active' => true,
        ]);

        $this->command->info("Super user dibuat:");
        $this->command->table(
            ['Name', 'Email', 'Password', 'Role'],
            [[$user->name, $user->email, 'RadiusAdmin@2026', $user->role]]
        );
        $this->command->warn('⚠️  Segera ganti password setelah login pertama!');
    }
}
