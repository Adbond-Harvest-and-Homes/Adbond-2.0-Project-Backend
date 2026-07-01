<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use app\Models\JobBenefit;

class JobBenefits extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $benefits = [
            "Competitive salary and comprehensive benefits package",
            "Opportunities for professional growth and career advancement",
            "Collaborative and innovative work environment",
            "Access to Cutting-edge tools and technologies",
            "Flexibile working hours and remote work options",
            "Health and wellness programs to support your well-being",
            "Employee recognition and reward programs",
            "Continous learning and development opportunities"
        ];

        foreach ($benefits as $benefit) {
            JobBenefit::create([
                "name" => $benefit
            ]);
        }
    }
}
