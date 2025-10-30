<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use app\Models\Order;

class UpdateOrderInstallments extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $orders = Order::where("is_installment", 1)->get();
        if($orders->count() > 0) {
            foreach($orders as $order) {
                if($order->installment_count) {
                    $order->amount_per_installment = round($order->amount_payable/$order->installment_count, 2);
                    $order->save();
                }
            }
        }
    }
}
