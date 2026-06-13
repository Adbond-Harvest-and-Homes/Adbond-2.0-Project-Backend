<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use app\Services\DepartmentService;

class Departments extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            ["name" => "Finance department"],
            ["name" => "Legal Department"],
            ["name" => "Media and communications department"],
            ["name" => "Human resource department"],
            ["name" => "Admin department"],
            ["name" => "IT department"],
            ["name" => "Sales and Marketing department"],
            ["name" => "Tele Sales Department"],
            ["name" => "Logistics Department"],
            ["name" => "Agriculture department"],
            ["name" => "Operations department"],
            ["name" => "Customer care department"],
            ["name" => "Virtual community department"],
            ["name" => "Built an environment planning department"],
            ["name" => "Experience centers"],
        ];

        foreach ($departments as $department) {
            app(DepartmentService::class)->save($department);
        }
    }
}
