<?php

namespace app\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use app\Models\Order;
use app\Models\Payment;
use app\Models\ClientPackage;

class MigrationOrderPaymentsController extends Controller
{
    
    public function synchronizeOrderPayments()
    {
        DB::table('orders')->where("completed", 0)->orderBy('created_at')->chunk(500, function ($orders) {
            if($orders->count() > 0) {
                foreach($orders as $order) {
                    $payments = Payment::where("purchase_id", $order->id)->where("purchase_type", Order::$type)->get();
                    if($payments->count() > 0) {
                        $totalPayment = 0;
                        foreach($payments as $payment) {
                            if($payment->confirmed == 1) $totalPayment = $totalPayment + $payment->amount;
                        }
                        if($order->amount_payed != $totalPayment) {
                            DB::beginTransaction();
                            try{
                                $order->amount_payed = $totalPayment;
                                $order->balance = $order->amount_payable - $order->amount_payed;
                                if($order->balance < 0) $order->balance = 0;
                                if($order->balance == 0) {
                                    $order->completed = 1;
                                    $order->payment_status_id = 1;

                                    $asset = ClientPackage::where("purchase_id", $order->id)->where("purchase_type", Order::$type)->first();
                                    $asset->purchase_complete = 1;
                                    $asset->purchase_completed_at = now();
                                    $asset->update();
                                }
                                $order->update();

                                DB::commit();
                            } catch(\Exception $e) {
                                DB::rollBack();
                            }
                            
                        }
                    }
                }
            }
        });
    }
}
