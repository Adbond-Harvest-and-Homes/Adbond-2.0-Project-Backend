<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use app\Models\StaffType;
use app\Enums\StaffTypes as Type;

class StaffTypes extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $staffTypes = array(
            Type::PHYSICAL_STAFF->value, Type::HYBRID_STAFF->value, Type::VIRTUAL_STAFF->value
        );
        foreach($staffTypes as $type) {
            StaffType::firstOrCreate(['name' => $type]);
        }
    }
}
