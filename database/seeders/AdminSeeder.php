<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        Admin::updateOrCreate(
            ['email' => env('ADMIN_SEED_EMAIL', 'chilandosongwe@gmail.com')],
            [
                'name' => env('ADMIN_SEED_NAME', 'Wamundila'),
                'password' => env('ADMIN_SEED_PASSWORD', 'Xpat360!'),
                'role' => env('ADMIN_SEED_ROLE', 'interactive_admin'),
                'status' => 'active',
            ]
        );
    }
}
