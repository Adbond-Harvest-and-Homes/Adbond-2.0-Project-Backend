<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use app\Models\State;
use app\Models\Country;

class States extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // $states = [
        //     "Abia",
        //     "Adamawa",
        //     "Akwa Ibom",
        //     "Anambra",
        //     "Bauchi",
        //     "Bayelsa",
        //     "Benue",
        //     "Borno",
        //     "Cross River",
        //     "Delta",
        //     "Ebonyi",
        //     "Edo",
        //     "Ekiti",
        //     "Enugu",
        //     "FCT - Abuja",
        //     "Gombe",
        //     "Imo",
        //     "Jigawa",
        //     "Kaduna",
        //     "Kano",
        //     "Katsina",
        //     "Kebbi",
        //     "Kogi",
        //     "Kwara",
        //     "Lagos",
        //     "Nasarawa",
        //     "Niger",
        //     "Ogun",
        //     "Ondo",
        //     "Osun",
        //     "Oyo",
        //     "Plateau",
        //     "Rivers",
        //     "Sokoto",
        //     "Taraba",
        //     "Yobe",
        //     "Zamfara"
        // ];

        // Correct path
        $jsonPath = __DIR__ . '/data/state.json';
        
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
        
        $states = json_decode($jsonString, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->command->error("JSON Error: " . json_last_error_msg());
            return;
        }
        
        if (!$states) {
            $this->command->error("No states found or invalid JSON structure");
            return;
        }
        
        $this->command->info("Found " . count($states) . " states in JSON file");
        
        $updated = 0;
        $notFound = 0;
        foreach($states as $state) {
            if($state['countryCode'] != 'NG') {
                $country = Country::where("code", $state['countryCode'])->first();
                if($country) {
                    if (!isset($state['name'])) {
                        $this->command->warn("Skipping entry - missing required fields: " . json_encode($state));
                        continue;
                    }
                    
                    $stateObj = State::where("name", $state['name'])->first();
                    // if($stateObj){
                    //     $stateObj->country_id = $country->id;
                    //     $stateObj->save();
                    //     $updated++;

                    //     if ($updated % 50 == 0) {
                    //         $this->command->info("Updated $updated states...");
                    //     }
                    // }else{
                    if(!$stateObj){
                        $stateObj = new State;
                        $stateObj->name = $state['name'];
                        $stateObj->country_id = $country->id;
                        $stateObj->save();

                        $notFound++;
                        // if ($notFound <= 10) { // Show first 10 not found
                        //     $this->command->info("Country not found in database: " . $state['name']);
                        // }
                    }
                }else{
                    $this->command->error("Country with Code: ".$state['countryCode']." no found");
                }
            }
        }
        $this->command->info("\n=== Seeder Completed ===");
        $this->command->info("Updated: $updated states");
        $this->command->info("Not found: $notFound states");
    }
}
