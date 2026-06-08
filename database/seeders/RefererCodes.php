<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use app\Models\User;

class RefererCodes extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        foreach($users as $user) {
            if($user->referer_code) {
                $prefix = "ADBSTF-";
                $suffix = "";
                if(str_starts_with($user->referer_code, $prefix)) {
                    // strip out the prefix 
                    $suffix = substr($user->referer_code, strlen($prefix));
                //    dd($suffix);
                }else{
                    $suffix = trim($user->referer_code);
                    // Add the prefix to it, if it doesn't have it
                    // $user->referer_code = $prefix.$user->referer_code;
                }

                 // assign the stripped out remainder to staff_referer_code
                if(!$user->staff_referer_code) {
                    $user->staff_referer_code = $suffix;
                    // dd($prefix.$suffix);
                }
                $user->save();
            }
                
        }
    }
}
