<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LandlordUserSeeder extends Seeder
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
                'username' => 'vonreg',
                'password' => bcrypt('password123'),
                'status' => 1,
                'role' => 2,
                'created_at' => now(),
            ],
        ]);

        DB::table('accounts')->insert([
            [
                'user_id' => 2,
                'full_name' => 'Von Regelado',
                'email' => 'vonreg@gmail.com',
                'mobile_number' => '09991234567',
                'created_at' => now(),
            ],
        ]);
    }
}
