<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use app\Models\Country;

class CountriesUpdate extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Correct path
        $jsonPath = __DIR__ . '/data/country.json';
        
        $this->command->info("Looking for file at: " . $jsonPath);
        
        if (!file_exists($jsonPath)) {
            $this->command->error("File not found: " . $jsonPath);
            return;
        }
        
        $jsonString = file_get_contents($jsonPath);
        
        if ($jsonString === false) {
            $this->command->error("Failed to read file: " . $jsonPath);
            return;
        }
        
        $this->command->info("File read successfully. Size: " . strlen($jsonString) . " bytes");
        
        $countries = json_decode($jsonString, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->command->error("JSON Error: " . json_last_error_msg());
            return;
        }
        
        if (!$countries) {
            $this->command->error("No countries found or invalid JSON structure");
            return;
        }
        
        $this->command->info("Found " . count($countries) . " countries in JSON file");
        
        $updated = 0;
        $notFound = 0;
        $doubleCode = [];
        
        foreach ($countries as $country) {
            // Make sure we have the required fields
            if (!isset($country['name']) || !isset($country['flag']) || !isset($country['currency'])) {
                $this->command->warn("Skipping entry - missing required fields: " . json_encode($country));
                continue;
            }
            
            // $countryObj = Country::where("name", $country['name'])->first();
            $countryObj = Country::where("name", $country['name'])->orWhere("code", $country['isoCode'])->first();
            
            if ($countryObj) {
                $codeCount = Country::where("code", $country['isoCode'])->count();
                if($codeCount == 1) {
                    $countryObj->flag = $country['flag'];
                    $countryObj->currency = $country['currency'];
                    $countryObj->save();
                    $updated++;
                    
                    if ($updated % 50 == 0) {
                        $this->command->info("Updated $updated countries...");
                    }
                }else{
                    $doubleCode[] = $country['isoCode'];
                }
            } else {
                $countryObj = new Country;
                $countryObj->name = $country['name'];
                $countryObj->code = $country['isoCode'];

                $phonecode = $country['phonecode'];
                if (!str_starts_with($phonecode, '+'))  $phonecode = '+' . $phonecode;
                
                $countryObj->phone_code = $phonecode;
                $countryObj->flag = $country['flag'];
                $countryObj->currency = $country['currency'];
                $countryObj->save();
                $notFound++;
                if ($notFound <= 10) { // Show first 10 not found
                    $this->command->warn("Country not found in database: " . $country['name']);
                }
            }
        }
        
        $this->command->info("\n=== Seeder Completed ===");
        $this->command->info("Updated: $updated countries");
        $this->command->info("Not found: $notFound countries");
        $this->command->info("Double Code Countries: ".count($doubleCode));
        if(count($doubleCode) > 0) foreach($doubleCode as $code) $this->command->info($code);

    }
}