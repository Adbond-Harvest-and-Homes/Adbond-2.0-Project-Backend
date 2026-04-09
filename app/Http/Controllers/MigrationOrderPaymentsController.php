<?php

namespace app\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use app\Jobs\GenerateReceipt;

use app\Models\Order;
use app\Models\Payment;
use app\Models\ClientPackage;

use app\Services\PaymentService;
use app\Services\FileService;
use app\Services\ReceiptService;

use app\Enums\FilePurpose;

use app\Utilities;

class MigrationOrderPaymentsController extends Controller
{

    public function synchronizePaymentReceipts()
    {
        $purpose = FilePurpose::PAYMENT_RECEIPT->value;
        // get all payments
        Payment::where("confirmed", 1)->orderBy('created_at', "DESC")->chunk(500, function ($payments) use($purpose) {
            if($payments->count() > 0) {
                foreach($payments as $payment) {
                    $fileIsOk = false;
                    // check if the payment has receipt_id, 
                    $file = null;
                    if(!$payment->receipt_file_id) {
                        // check if the file exists
                        Utilities::logStuff("receipt file id not found for payment Id: ".$payment->id);
                        $file = app(FileService::class)->getSpecificFile(Payment::$type, $payment->id, $purpose);
                    }else{
                        // check if the receipt file is a receipt file
                        $receiptFile = $payment->paymentReceipt;
                        if($receiptFile->purpose != $purpose) {
                            $file = app(FileService::class)->getSpecificFile(Payment::$type, $payment->id, $purpose);
                        }else{
                            Utilities::logStuff("receipt file for payment Id: ".$payment->id." is okay");
                            $fileIsOk = true;
                        }
                    }
                    if(!$fileIsOk) {
                        if($file) {
                            Utilities::logStuff("Found receipt file for payment Id: ".$payment->id);
                            $payment->receipt_file_id = $file->id;
                            $payment->docs_uploaded = 1;
                            $payment->save();
                        }else{
                            Utilities::logStuff("Generate receipt file for payment Id: ".$payment->id);
                            // dispatch job to create the receipt
                            GenerateReceipt::dispatch($payment->id, false);
                        }
                    }
                }
            }
        });

            
    }
    
    public function synchronizeOrderPayments()
    {
        // Order::where("completed", 0)->orderBy('created_at')->chunk(500, function ($orders) {
        //     if($orders->count() > 0) {
        //         foreach($orders as $order) {
        //             // $payments = Payment::where("purchase_id", $order->id)->where("purchase_type", Order::$type)->get();
        //             $totalPayment = Payment::where("purchase_id", $order->id)
        //             ->where("purchase_type", Order::$type) // Use constant instead of static property
        //             ->where("confirmed", 1)
        //             ->sum('amount');
        //             // if($payments->count() > 0) {
        //             //     $totalPayment = 0;
        //             //     foreach($payments as $payment) {
        //             //         if($payment->confirmed == 1) $totalPayment = $totalPayment + $payment->amount;
        //             //     }
        //             if($order->amount_payed != $totalPayment) {
        //                 DB::beginTransaction();
        //                 try{
        //                     $order->amount_payed = $totalPayment;
        //                     $order->balance = $order->amount_payable - $order->amount_payed;
        //                     if($order->balance < 0) $order->balance = 0;
        //                     if($order->balance == 0) {
        //                         $order->completed = 1;
        //                         $order->payment_status_id = 1;

        //                         $asset = ClientPackage::where("purchase_id", $order->id)->where("purchase_type", Order::$type)->first();
        //                         if($asset) {
        //                             $asset->purchase_complete = 1;
        //                             $asset->purchase_completed_at = now();
        //                             $asset->update();
        //                         }
        //                     }
        //                     $order->update();

        //                     DB::commit();
        //                 } catch(\Exception $e) {
        //                     DB::rollBack();
        //                     Utilities::error($e);
        //                 }
                        
        //             }
        //             // }
        //         }
        //     }
        // });

        Order::where("completed", 1)->orderBy('created_at')->chunk(500, function ($orders) {
            if($orders->count() > 0) {
                foreach($orders as $order) {
                    if($order->balance > 0) {
                        $totalPayment = Payment::where("purchase_id", $order->id)
                        ->where("purchase_type", Order::$type) // Use constant instead of static property
                        ->where("confirmed", 1)
                        ->sum('amount');

                        DB::beginTransaction();
                        try{

                            if($order->amount_payed != $totalPayment) $order->amount_payed = $totalPayment;
                            $balance = max(0, $order->amount_payable - $totalPayment);
                            if($order->balance != $balance) $order->balance = $balance;

                            // if($order->id == 4278)

                            if($balance > 0) {
                                $order->completed = 0;

                                // $order->update();

                                $asset = ClientPackage::where("purchase_id", $order->id)->where("purchase_type", Order::$type)->first();
                                if($asset) {
                                    $asset->purchase_complete = 0;
                                    $asset->purchase_completed_at = null;
                                    $asset->save();
                                }
                            }
                            $order->save();
                            DB::commit();
                        } catch(\Exception $e) {
                            DB::rollBack();
                            Utilities::error($e);
                        }
                    }

                }
            }
        });
    }
}
