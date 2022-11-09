<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;


class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user =User::create([
            "name" => "admin",
            "user_name" => "admin",
            'phone_number'=>'00000000000',
            'activation'=> 0,
            "password" => bcrypt("admin@1234"),
        ]);
        $store = Store::create([
            'name'=> 'admin',
            'user_id'=> $user->id,
        ]);
    }
}
