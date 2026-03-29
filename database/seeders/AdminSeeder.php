<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        Admin::updateOrCreate(
            ['email' => env('ADMIN_SEED_EMAIL', 'admin@interactive.local')],
            [
                'name' => env('ADMIN_SEED_NAME', 'TNBO Admin'),
                'password' => env('ADMIN_SEED_PASSWORD', 'password123'),
                'role' => env('ADMIN_SEED_ROLE', 'interactive_admin'),
                'status' => 'active',
            ]
        );
    }
}
