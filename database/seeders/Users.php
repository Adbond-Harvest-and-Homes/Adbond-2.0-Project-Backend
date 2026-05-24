<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use app\Models\User;
use app\Helpers;
use app\Utilities;

use app\Models\StaffType;
use app\Models\Role;

class Users extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                "firstname" => "Super",
                "lastname" => "Admin",
                "email" => "adbondharvest@gmail.com",
                "password" =>  bcrypt("#Adbond02"),
                "role_id" => Role::SuperAdmin()->id,
                "referer_code" => Utilities::generateRandomString(5),
                "phone_number" => "08035033581",
                "activated" => 1,
            ],
            [
                "firstname" => "Development",
                "lastname" => "Admin",
                "email" => "adbond-dev@gmail.com",
                "password" =>  bcrypt("#dev02"),
                "role_id" => Role::SuperAdmin()->id,
                "referer_code" => Utilities::generateRandomString(5),
                "phone_number" => "08035033581",
                "activated" => 1,
            ],
        ];

        foreach($users as $user) { //dd($user['name']);
            $userObj = User::where("email", $user['email'])->first();
            if(!$userObj) {
                $userObj = new User;
                $userObj->firstname = $user['firstname'];
                $userObj->lastname = $user['lastname'];
                $userObj->email = $user['email'];
                $userObj->password = $user['password'];
                $userObj->role_id = $user['role_id'];
                $userObj->referer_code = $user['referer_code'];
                $userObj->phone_number = $user['phone_number'];
                $userObj->activated = $user['activated'];
                $userObj->staff_type_id = StaffType::FullStaff()?->id;
                $userObj->email_verified_at = now();
                $userObj->save();
            }
        }
    }
}
