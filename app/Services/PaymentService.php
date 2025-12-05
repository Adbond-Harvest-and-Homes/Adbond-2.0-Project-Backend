<?php

namespace app\Services;

use Illuminate\Support\Facades\Mail;

use app\Models\Order;
use app\Models\Offer;
use app\Models\Payment;
use app\Models\Discount;
use app\Models\PaymentMode;
use app\Models\PaymentGateway;
use app\Models\File;

use app\Mail\NewPayment;

use app\Enums\OrderDiscountType;
use app\Enums\UserType;
use app\Enums\FileTypes;use app\Enums\FilePurpose;


use app\Services\FileService;

use app\Helpers;
use app\Utilities;

/**
 * Order service class
 */
class PaymentService
{
    public $count = false;
    public $filters = [];

    public function getPayment($id, $with=[])
    {
        return Payment::with($with)->where("id", $id)->first();
    }

    public function getPurchasePayment($purchaseId, $purchaseType)
    {
        return Payment::where("purchase_id", $purchaseId)->where("purchase_type", $purchaseType)->first();
    }

    public function getPayments()
    {
        return Payment::all();
    }

    public function getClientPayments($clientId)
    {
        return Payment::where("client_id", $clientId)->get();
    }

    public function offerPayments($with=[], $offset=0, $perPage=null)
    {
        $query = Payment::with($with)->where("purchase_type", Offer::$type);
        $filter = $this->filters;

        if(array_key_exists('status', $filter)) {
            ($filter['status'] === null) ? $query->whereNull("confirmed") : $query->where("confirmed", $filter['status'])
            // $query->whereHas("paymentStatus", function($paymentQuery) use($filter) {
            //     $paymentQuery->where("name", $filter['status']);
            // })
            ->whereHas("purchase", function($purchaseQuery) {
                $purchaseQuery->where("completed", 0);
            });
        }

        if($this->count) return $query->count();

        $query = $query->orderBy("created_at", "DESC");
        if($perPage) $query = $query->limit($perPage);
        return $query->offset($offset)->orderBy("created_at", "Desc")->get();
    }

    public function getPayable($data, $promos, $promoCodeDiscount=null)
    {
        $appliedDiscounts = [];
        $discountedAmount = $data['amount'];
        if(!$data['isInstallment']) {
            $fullPaymentDiscount = Discount::fullPayment()->discount;
            $discountArr = Utilities::getDiscount($discountedAmount, $fullPaymentDiscount);
            $discountedAmount = $discountArr['amount'];
            $appliedDiscounts[] = [
                "name" => "Full Payment Discount", 
                "type"=>OrderDiscountType::FULL_PAYMENT->value, 
                "discount"=>$fullPaymentDiscount,
                "amount"=>$discountArr['amount'],
                "discountedAmount" => $discountArr['discountedAmount']
            ];
        }
        if($promoCodeDiscount) {
            $discountArr = Utilities::getDiscount($discountedAmount, $promoCodeDiscount);
            $discountedAmount = $discountArr['amount'];
            $appliedDiscounts[] = [
                "name" => "Promo Code Discount", 
                "type"=>OrderDiscountType::PROMO->value, 
                "discount"=>$promoCodeDiscount,
                "amount"=>$discountArr['amount'],
                "discountedAmount" => $discountArr['discountedAmount']
            ];
        }
        if(count($promos) > 0) {
            foreach($promos as $promo) {
                $discountedAmount = Utilities::getDiscount($discountedAmount, $promo->discount);
                $appliedDiscounts[] = [
                    "name" => $promo->title." Promo", 
                    "type"=>OrderDiscountType::PROMO->value, 
                    "discount"=>$promo->discount,
                    "amount"=>$discountArr['amount'],
                "discountedAmount" => $discountArr['discountedAmount']
                ];
            }
        }
        return ["appliedDiscounts" => $appliedDiscounts, "amount" => $discountedAmount];
    }

    public function save($data)
    {
        // dd($data);
        $payment = new Payment;
        $payment->purchase_id = $data['purchaseId'];
        $payment->purchase_type = $data['purchaseType'];
        $payment->client_id = $data['clientId'];
        $payment->amount = $data['amount'];
        $payment->payment_mode_id = $data['paymentModeId'];
        if(isset($data['confirmed'])) $payment->confirmed = $data['confirmed'];
        
        if(isset($data['evidenceFileId'])) $payment->evidence_file_id = $data['evidenceFileId'];
        if(isset($data['paymentGatewayId'])) $payment->payment_gateway_id = $data['paymentGatewayId'];
        if(isset($data['reference'])) $payment->reference = $data['reference'];
        if(isset($data['success'])) $payment->success = $data['success'];
        if(isset($data['failureMessage'])) $payment->failure_message = $data['failureMessage'];
        if(isset($data['flag'])) $payment->flag = $data['flag'];
        if(isset($data['flagMessage'])) $payment->flag_message = $data['flagMessage'];
        if(isset($data['bankAccountId'])) $payment->bank_account_id = $data['bankAccountId'];
        if(isset($data['paymentDate'])) $payment->payment_date = $data['paymentDate'];
        if(isset($data['receiptFileId'])) $payment->receipt_file_id = $data['receiptFileId'];
        if(isset($data['receiptNumber'])) $payment->receipt_no = $data['receiptNumber'];
        if(isset($data['docsUploaded'])) $payment->docs_uploaded = 1;
        $payment->purpose = $data['purpose'];
        if(isset($data['userId'])) $payment->user_id = $data['userId'];
        $payment->save();
        return $payment;
    }

    public function update($data, $payment)
    {
        if(isset($data['paymentModeId'])) $payment->payment_mode_id = $data['paymentModeId'];
        if(isset($data['evidenceFileId'])) $payment->evidence_file_id = $data['evidenceFileId'];
        if(isset($data['paymentGatewayId'])) $payment->payment_gateway_id = $data['paymentGatewayId'];
        if(isset($data['reference'])) $payment->reference = $data['reference'];
        if(isset($data['success'])) $payment->success = $data['success'];
        if(isset($data['failureMessage'])) $payment->failure_message = $data['failureMessage'];
        if(isset($data['flag'])) $payment->flag = $data['flag'];
        if(isset($data['flagMessage'])) $payment->flag_message = $data['flagMessage'];
        if(isset($data['bankAccountId'])) $payment->bank_account_id = $data['bankAccountId'];
        if(isset($data['paymentDate'])) $payment->payment_date = $data['paymentDate'];
        if(isset($data['receiptFileId'])) $payment->receipt_file_id = $data['receiptFileId'];
        if(isset($data['receiptNumber'])) $payment->receipt_no = $data['receiptNumber'];
        if(isset($data['userId'])) $payment->user_id = $data['userId'];
    }

    public function implementCardPayment($data)
    {
        $data['payment_gateway_id'] = PaymentGateway::paystack()->id;
        $data['payment_mode_id'] = PaymentMode::cardPayment()->id;
        $package = $data['package'];
    }

    public function paystackInit($client, $amount)
    {
        $url = 'https://api.paystack.co/transaction/initialize';
        $headers = [
            "accept" => "application/json",
            "Authorization" => "Bearer ".env('PAYSTACK_SECRET_KEY'),
            "Cache-Control" => "no-cache"
        ];
        $post = ["email" => $client->email, "amount" => ceil($amount), "callback_url"=> "https://yourdomain.com/payment/callback"];
        // {
        //     "email": "customer@example.com",
        //     "amount": 500000, // Amount in kobo
        //     "callback_url": "https://yourdomain.com/payment/callback"
        //   }
          
        $res = ['success' => false];
        $response = Helpers::request($url, $headers, $post);
        if($response['status'] && $response['status'] == true) {
            $res['success'] = true;
            $res['data'] = $response['data'];
        }else{
            $res['message'] = $response['message'];
        }
        return $res;
    }


    public function paystackVerify($reference, $amount)
    {
        $url = 'https://api.paystack.co/transaction/verify/'.$reference;
        $headers = [
            "accept" => "application/json",
            "Authorization" => "Bearer ".env('PAYSTACK_SECRET_KEY'),
            "Cache-Control" => "no-cache"
        ];
        $message = "";
        $res = ['success' => false, 'paymentError' => false];
        $data = Helpers::request($url, $headers);
        // dd($data);
        $gatewayResponse = (isset($data['data']['gateway_response'])) ? $data['data']['gateway_response'] : '';
        if(($data['status'] && $data['status'] == true) && ($data['data'] && $data['data']['status'] == "success")) {
            // dd($data);
            $res["success"] = true;
            $resData = $data['data'];
            $payedAmount = $resData['amount']/100;
            $commission = $resData['fees'];
            // dd('payed Amount: '.$payedAmount .' - Commission'. $commission);
            // $productAmount = $payedAmount - $commission; // Get the product amount by deducting paystack commission
            if(($payedAmount == $amount) || abs($payedAmount - $amount) < 1) {// The payed amount is equal to what should be paid or difference not up to 1
                $res["amount"] = $payedAmount;
            }else{
                // dd($payedAmount .' == '. $amount . ' || ' . abs($payedAmount - $amount) . ' < 1');
                $res["amount"] = $payedAmount;
                $res['paymentError'] = true;
                $res['message'] = "The amount to be paid(".$amount.") doesn't match the amount that was paid(".$payedAmount.")";
            }
        }else{
            $res['message'] = $data['message'].', '.$gatewayResponse;
            $res['paymentError'] = true;
        }
        return $res;
    }

    public function uploadReceipt($payment, $user)
    {
        // generate receipt if the card payment was successful
        // dd($payment);
        try{
            // dd('got here');
            $fileService = new FileService;
            Helpers::generateReceipt($payment->load('paymentMode'));
            // dd('generate receipt');
            $uploadedReceipt = 'files/receipt'.$payment->receipt_no.'.pdf';
            // dd('time to move..'.$uploadedReceipt);
            $response = Helpers::moveUploadedFileToCloud($uploadedReceipt, FileTypes::PDF->value, $user->id, 
                                FilePurpose::PAYMENT_RECEIPT->value, UserType::CLIENT->value, "client-receipts");
            if($response['success']) {
                $fileMeta = ["belongsId"=>$payment->id, "belongsType"=>"app\Models\Payment"];
                $fileService->updateFileObj($fileMeta, $response['upload']['file']);

                
                // dd("got here");
                try{
                    // Send Payment Mail
                    Mail::to($payment->client->email)->send(new NewPayment($payment, $uploadedReceipt));
                    unlink($response['path']);
                }catch(\Exception $e) {
                    Utilities::logStuff("Error Occurred while attempting to send Payment Email..".$e);
                }
            }
            return $response['upload']['file'];
        }catch(\Exception $e) {
            Utilities::logStuff("Error Occurred while attempting to generate and upload receipt..".$e);
        }
    }

    public function confirm($payment)
    {
        $payment->confirmed = true;
        $payment->success = true;
        $payment->update();

        return $payment;
    }

    public function reject($payment, $message)
    {
        $payment->confirmed = false;
        $payment->success = false;
        $payment->rejected_reason = $message;
        $payment->update();

        return $payment;
    }

    public function flag($payment, $message)
    {
        $payment->flag = true;
        $payment->flag_message = $message;
        $payment->update();

        return $payment->update();
    }


    //update missing receipt_file_id
    public function addReceiptFileIds()
    {
        Payment::whereNull("receipt_file_id")->orderBy('created_at', 'DESC')->chunk(500, function ($records) {
            foreach ($records as $payment) {
                $receiptFile = File::where("belongs_id", $payment->id)->where("belongs_type", Payment::$type)->first();
                if($receiptFile) {
                    $payment->receipt_file_id = $receiptFile->id;
                    $payment->update();
                    Utilities::logStuff("updated payment receipt. payment Id: ".$payment->id);
                }
            }
        });
    }

}
