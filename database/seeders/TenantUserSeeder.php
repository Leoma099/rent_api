<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TenantUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            [
                'username' => 'janlyssa',
                'password' => bcrypt('password123'),
                'status' => 1,
                'role' => 3,
                'created_at' => now(),
            ],
        ]);

        DB::table('accounts')->insert([
            [
                'user_id' => 3,
                'full_name' => 'Jan Alyssa',
                'email' => 'janlyssa@gmail.com',
                'mobile_number' => '09991234567',
                'created_at' => now(),
            ],
        ]);
    }
}
