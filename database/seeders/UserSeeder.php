<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         User::factory()->create([
           'name' => 'Admin2',
           'email' => 'admin2@example.com',
           'password' => 'admin',
           'is_admin' => true,
        ]);
       // User::factory(10)->create();
    }
}
