<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        DB::table('users')->insert([
            'name' => 'Ganza ',
            'email' => 'admin@ganza.com',
            'password' => Hash::make('password123'),
            'role_id' => 1, // Admin role
            'created_at' => now()
        ]);
    }
}
