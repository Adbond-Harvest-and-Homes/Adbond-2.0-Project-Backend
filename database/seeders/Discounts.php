<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use app\Models\Discount;
use app\Enums\DiscountType;

class Discounts extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $discounts = [
            ["type" => DiscountType::FULL_PAYMENT->value, "discount" => 5],
            ["type" => DiscountType::LOYALTY->value, "discount" => 5],
            ["type" => DiscountType::BOND->value, "discount" => 5],
            ["type" => DiscountType::BOND_INSTALLMENT->value, "discount" => 3]
        ];

        foreach($discounts as $discount) {
            Discount::firstOrCreate(
                ["type" => $discount['type']],
                ["discount" => $discount['discount']]
            );
        }
    }
}
