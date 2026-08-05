<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use app\Services\EmploymentTypeService;

class EmploymentTypes extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ["name" => "Full Time"],
            ["name" => "Hybrid"],
            ["name" => "Remote"],
            ["name" => "Virtual"],
            ["name" => "Contract"],
            ["name" => "Part Time"],
        ];

        foreach ($types as $type) {
            app(EmploymentTypeService::class)->save($type);
        }
    }
}
