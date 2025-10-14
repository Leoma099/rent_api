<?php

namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
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
                'username' => 'sysadmin',
                'password' => bcrypt('secret'),
                'status' => 1,
                'role' => 1,
                'created_at' => now(),
            ],
        ]);

        DB::table('accounts')->insert([
            [
                'user_id' => 1,
                'full_name' => 'Administrator',
                'email' => 'systemadmin@gmail.com',
                'mobile_number' => '09991234567',
                'created_at' => now(),
            ],
        ]);
    }
}
