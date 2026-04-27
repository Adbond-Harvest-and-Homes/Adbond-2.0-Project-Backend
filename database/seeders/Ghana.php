<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use app\Models\Country;
use app\Models\State;

class Ghana extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ghanaRegions = array(
            "Ahafo",
            "Ashanti",
            "Bono East",
            "Brong Ahafo",
            "Central",
            "Eastern",
            "Greater Accra",
            "North East",
            "Northern",
            "Oti",
            "Savannah",
            "Upper East",
            "Upper West",
            "Volta",
            "Western",
            "Western North"
        );
        $ghana = Country::where("name", "Ghana")->first();

        if($ghana && count($ghanaRegions) > 0) {
            foreach($ghanaRegions as $region) {
                State::firstOrCreate(["name" => $region, "country_id" => $ghana->id]);
            }
        }
    }
}
