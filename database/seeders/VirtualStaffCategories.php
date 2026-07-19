<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use app\Models\VirtualStaffCategory;

class VirtualStaffCategories extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = ["virtual staff", "Virtual Staff Executive", "Ambassador", "Influencer", "Virtual Graduate Intern"];

        foreach ($categories as $category) {
            VirtualStaffCategory::create(
                ["name" => $category]
            );
        }
    }
}
