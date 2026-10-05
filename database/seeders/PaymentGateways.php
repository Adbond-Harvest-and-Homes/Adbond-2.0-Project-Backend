<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use app\Models\PaymentGateway;

use app\Enums\PaymentGateway as PaymentGatewayEnum;

class PaymentGateways extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $gateways = [PaymentGatewayEnum::PAYSTACK->value, PaymentGatewayEnum::OPAY->value];

        foreach($gateways as $gateway) {
            PaymentGateway::firstOrCreate(["name" => $gateway]);
        }
    }
}
