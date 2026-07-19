<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use app\Models\Role;
use app\Enums\Roles as RoleEnum;

class Roles extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = array(
            RoleEnum::SUPER_ADMIN->value,
            RoleEnum::ADMIN->value,
            RoleEnum::HUMAN_RESOURCE->value,
            RoleEnum::LOGISTICS->value,
            RoleEnum::OPERATION_ACCOUNTING->value,
            RoleEnum::CUSTOMER_RELATION->value,
            RoleEnum::CONTENT_MANAGEMENT->value,
            RoleEnum::SALES->value
        );
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }
    }
}
