<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Admin;
class DatabaseSeeder extends Seeder

{
    /**
     * Seed the application's database.
     */
     
    public function run(): void
    {
        
        User:: firstOrCreate(  
         ['email' => 'superadmin@example.com'],
         [
             'name' => 'Super Admin',
             'password' => Hash::make('superpassword234'),
             'role' => 'Superadmin',
             ]
             );
        // User::factory(10)->create();

        User::firstOrCreate(
    ['email' => 'test@example.com'],
    [
        'name' => 'Test User',
        'password' => Hash::make('password123'), 
        'role' => 'user',
    ]
);
User::factory(10)->create();
    }
}
