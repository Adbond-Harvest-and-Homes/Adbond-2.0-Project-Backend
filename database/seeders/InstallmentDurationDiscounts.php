<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use app\Models\InstallmentDiscount;

class InstallmentDurationDiscounts extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $discounts = [
            2 => 5,
            4 => 5,
            6 => 5,
            8 => 5,
            10 => 5,
            12 => 5,
            24 => 0,
        ];

        foreach($discounts as $duration=>$discount) {
            InstallmentDiscount::firstOrCreate([
                "duration" => $duration,
                "discount" => $discount
            ]);
        }
    }
}
