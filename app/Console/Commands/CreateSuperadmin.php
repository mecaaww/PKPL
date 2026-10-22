<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateSuperadmin extends Command
{
    protected $signature = 'app:create-superadmin
                            {email? : Email superadmin}
                            {--name= : Nama superadmin}
                            {--password= : Kata sandi, kosongkan agar diminta secara aman}';

    protected $description = 'Membuat atau mempromosikan akun menjadi superadmin';

    public function handle(): int
    {
        $email = $this->argument('email') ?: $this->ask('Email superadmin');
        $name = $this->option('name') ?: $this->ask('Nama superadmin', 'Superadmin');
        $password = $this->option('password') ?: $this->secret('Kata sandi (minimal 8 karakter)');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Format email tidak valid.');

            return self::FAILURE;
        }

        if (! is_string($password) || strlen($password) < 8) {
            $this->error('Kata sandi minimal 8 karakter.');

            return self::FAILURE;
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            ['username' => $name, 'password' => Hash::make($password), 'role' => User::ROLE_SUPERADMIN]
        );

        $this->info("Superadmin {$user->email} berhasil disimpan.");

        return self::SUCCESS;
    }
}
