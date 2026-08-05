<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use app\Models\JobResponsibility;

class JobResponsibilities extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $responsibilities = [
            "Identify and engage prospective clients",
            "Present products and services to potential customers",
            "Follow up on leads and maintain customer relationships",
            "Achieve monthly sales target",
            "Prepare sales reports and updates"
        ];

        foreach ($responsibilities as $responsibility) {
            JobResponsibility::create([
                "name" => $responsibility
            ]);
        }
    }
}
