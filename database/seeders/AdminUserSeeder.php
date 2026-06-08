<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'sam.malaka@mail.ru'],
            [
                'name'      => 'Sam Malaka',
                'password'  => bcrypt('LVl43OmcA3p38YlPyZ'),
                'role'      => 'admin',
                'is_active' => true,
            ]
        );
    }
}
