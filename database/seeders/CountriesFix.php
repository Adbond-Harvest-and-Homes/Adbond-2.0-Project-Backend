<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use app\Models\Country;

class CountriesFix extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $countries = Country::whereNull("flag")->orWhereNull("currency")->get();

        if($countries->count() > 0) {
            foreach($countries as $country) {
                $correctCountry = Country::where("code", $country->code)->where(function($query) {
                    $query->whereNotNull("flag")->orWhereNotNull("currency");
                })->first();
                if($correctCountry) {
                    $country->flag = $correctCountry->flag;
                    $country->currency = $correctCountry->currency;
                    $country->save();

                    $correctCountry->delete();
                    $this->command->info("correct Country: ".$correctCountry->name);
                    $this->command->info("country Id: ".$country->id);
                    $this->command->info("Correct country Id: ".$correctCountry->id);
                    $this->command->info("states count: ".$country->states->count());
                    $this->command->info("Correct Country states count: ".$correctCountry->states->count());
                    // $country->delete();
                }

            }
        }
    }
}
