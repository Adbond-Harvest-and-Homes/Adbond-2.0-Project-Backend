<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use app\Models\Package;
use app\Models\State;

class LoadPackageStates extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $packages = Package::all();

        if($packages->count() > 0) {
            foreach($packages as $package) {
                $state = State::where("name", $package->state)->first();
                if($state){
                    $package->state_id = $state->id;
                    $package->country_id = $state->country->id;
                    $package->update();
                }
            }
        }
    }
}
