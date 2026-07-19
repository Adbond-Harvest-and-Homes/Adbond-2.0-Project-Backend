<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use app\Models\JobRequirement;

class JobRequirements extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $requirements = [
            "Bachelor's degree or equivalent experience",
            "Excellent communication and negotiation skills",
            "Previous sales experience is an advantage",
            "Ability to work independently and as part of a team",
            "Basic computer proficiency"
        ];

        foreach ($requirements as $requirement) {
            JobRequirement::create([
                "name" => $requirement
            ]);
        }
    }
}
