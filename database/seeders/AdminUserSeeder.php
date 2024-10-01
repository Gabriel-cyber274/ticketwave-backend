<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        User::factory()->create([
            'fullname' => 'Admin boss',
            'first_name' => 'Admin',
            'last_name' => 'boss',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'admin' => true
        ]);
    }
}
