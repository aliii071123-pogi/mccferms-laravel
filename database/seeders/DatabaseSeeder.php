<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $email = config('ferms.super_admin_email');
        if ($email && str_ends_with($email, '@mcc.edu.ph')) {
            User::updateOrCreate(
                ['email' => $email],
                ['name' => 'FERMS Super Administrator', 'password' => Str::random(64), 'role' => 'super_admin'],
            );
        }
    }
}
