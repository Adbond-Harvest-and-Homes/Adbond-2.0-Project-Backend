<?php

namespace app\Services;

use Illuminate\Support\Facades\Mail;

use app\Models\Order;
use app\Models\Discount;
use app\Models\OrderDiscount;
use app\Models\ProjectType;

use app\Enums\OrderDiscountType;
use app\Enums\FilePurpose;
use app\Enums\ClientPackageOrigin;
use app\Enums\FileTypes;
use app\Enums\PackageType;
use app\Enums\OrderType;

use app\Helpers;
use app\Utilities;

use app\Services\ClientPackageService;
use app\Services\ClientInvestmentService;
use app\Services\FileService;
use app\Services\CommissionService;
use app\Services\DiscountService;

/**
 * Order service class
 */
class OrderService
{

    public function order($id, $with=[])
    {
        return Order::with($with)->where("id", $id)->first();
    }

    public function getPayable($data, $promos, $promoCodeDiscount=null)
    {
        $appliedDiscounts = [];
        $discountedAmount = $data['amount'];
        if($data['packageType']==PackageType::NON_INVESTMENT->value && !$data['isInstallment']) {
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
        if($data['packageType']==PackageType::NON_INVESTMENT->value && $data['isInstallment']){
            $discountService = new DiscountService;
            $installment = $discountService->getInstallmentDuration($data['duration']);
            $discountArr = Utilities::getDiscount($discountedAmount, $installment->discount);
            $discountedAmount = $discountArr['amount'];
            $appliedDiscounts[] = [
                "name" => $data['duration']."Months Installment Payment Discount", 
                "type"=>OrderDiscountType::INSTALLMENT_PAYMENT->value, 
                "discount"=>$installment->discount,
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
                $discountArr = Utilities::getDiscount($discountedAmount, $promo->discount);
                $discountedAmount = $discountArr['amount'];
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
        $order = new Order;
        $order->client_id = $data['clientId'];
        $order->package_id = $data['packageId'];
        $order->units = $data['units'];
        if(isset($data['balance']) && isset($data['balance'])) {
            $order->amount_payed = $data['amountPayed'];
            $order->balance = $data['balance'];
        }
        $order->amount_payable = $data['amountPayable'];
        $order->unit_price = $data['unitPrice'];
        if(isset($data['promoCodeId'])) $order->promo_code_id = $data['promoCodeId'];
        $order->is_installment = $data['isInstallment'];
        if($data['isInstallment']) {
            $order->installment_count = $data['installmentCount'];
            $order->amount_per_installment = round($data['amountPayable']/$data['installmentCount']);
        }
        if(isset($data['isInstallment'])) $order->installments_payed = 1;
        $order->payment_status_id = $data['paymentStatusId'];
        $order->order_date = $data['orderDate'];
        if(isset($data['paymentDueDate'])) $order->payment_due_date = $data['paymentDueDate'];
        if(isset($data['gracePeriodEndDate'])) $order->grace_period_end_date = $data['gracePeriodEndDate'];
        if(isset($data['paymentPeriodStatusId'])) $order->payment_period_status_id = $data['paymentPeriodStatusId'];

        $order->save();

        return $order;
    }

    public function updateInstallmentCount($order, $count)
    {
        $order->installment_count = $count;
        $order->amount_per_installment = round($order->balance/$count);
        $order->update();
        return $order;
    }

    public function update($data, $order, $payment=null)
    {
        // dd($data);
        if(isset($data['installmentsPayed'])) $order->installments_payed = $data['installmentsPayed'];
        if(isset($data['paymentStatusId'])) $order->payment_status_id = $data['paymentStatusId'];
        if(isset($data['amountPayed'])) {
            $order->amount_payed += $data['amountPayed'];
            // $order->balance = $data['balance'];
        }
        $amountPaid = (isset($data['amountPayed']) ? $data['amountPayed'] : $order->amount_payed);
        // dd($amountPaid);
        $balance = $order->balance - $amountPaid;
        if($order->is_installment == 1) {
            $installmentsRemaining = $order->installment_count - $order->installments_payed;

            if($balance > 0 && $installmentsRemaining == 0) {
                $order->installment_count = $order->installment_count + 1;
                $installmentsRemaining = 1;
            }
        }
        if(isset($data['updateInstallment']) && $balance > 0) {
            // if($payment && $payment->amount < $order->amount_per_installment) {
            //     // CLient made lower than expected payment
            // }
            $order->amount_per_installment = round($balance/$installmentsRemaining, 2);
        }
        // dd($order);
        
        $order->update();


        return $order;
    }

    public function saveAmountPaid($order, $amount)
    {
        $data['amountPayed'] = $amount;
        $data['balance'] = ($order->is_installment==1) ? $order->amount_payable - ($order->amount_payed + $amount) : 0;
        $order = $this->update($data, $order);

        return $order;
    }

    public function saveOrderDiscounts($order, $discounts)
    {
        foreach($discounts as $discount) {
            $orderDiscount = new OrderDiscount;
            $orderDiscount->order_id = $order->id;
            $orderDiscount->type = $discount['type'];
            $orderDiscount->discount = $discount['discount'];
            $orderDiscount->amount = $discount['discountedAmount'];
            $orderDiscount->description = $discount['name'];
            $orderDiscount->save();
        }
    }


    public function completeOrder($order, $payment, $clientInvestment=null)
    {
        $contractFileId = null;
        $contractFileObj = null;
        $letterOfHappinessFileId = null;
        $letterOfHappinessFileObj = null;
        $fileService = new FileService;
        $clientInvestmentService = new ClientInvestmentService;
        $clientPackageService = new ClientPackageService;

        // mark the order as complete
        $order->completed = true;
        $order->update();


        $files = [];
        $clientPackage = null;
        if($order->package->type==PackageType::NON_INVESTMENT->value) {
            $clientPackageService->uploadContract = ($payment->confirmed == 1);
            $clientPackage = $clientPackageService->saveClientPackageOrder($order);
        }

        if($order->package->type==PackageType::INVESTMENT->value) {

            //Upload MOU and send it as email
            $clientInvestmentService->uploadMOU($order, $clientInvestment);

            $clientPackage = $clientPackageService->saveClientPackageInvestment($clientInvestment);

            // $fileMeta = ["belongsId"=>$clientPackage->id, "belongsType"=>"app\Models\ClientInvestment"];
            // if($memorandumFileObj) $fileService->updateFileObj($fileMeta, $memorandumFileObj);

            // Start the investment
            $clientInvestmentService->start($clientInvestment);
        }

        // $fileMeta = ["belongsId"=>$clientPackage->id, "belongsType"=>"app\Models\ClientPackage"];
        // if($contractFileObj) $fileService->updateFileObj($fileMeta, $contractFileObj);
        // if($letterOfHappinessFileObj) $fileService->updateFileObj($fileMeta, $letterOfHappinessFileObj);

        //if its an upgrade order, 
        if($order->type == OrderType::UPGRADE->value) {
            $order->upgrade->complete = true;
            $order->upgrade->update();

            $order->upgrade->asset->upgraded = true;
            $order->upgrade->asset->update();
        }

        return $clientPackage;
    }

    public function completeDowngradeOrder($order, $clientPackage)
    {
        $contractFileId = null;
        $contractFileObj = null;
        $letterOfHappinessFileId = null;
        $letterOfHappinessFileObj = null;
        $fileService = new FileService;
        try{
            // generate and save contract
            Helpers::generateContract($order);
            // dd('generate receipt');
            $uploadedContract = "files/contract_{$order->id}.pdf";
            
            $response = Helpers::moveUploadedFileToCloud($uploadedContract, FileTypes::PDF->value, $order->client->id, 
            FilePurpose::CONTRACT->value, "app\Models\Client", "client-contracts");
            
            if($response['success']) {
                $contractFileId = $response['upload']['file']->id;
                $contractFileObj = $response['upload']['file'];
            }
            
        }catch(\Exception $e) {
            Utilities::logStuff("Error Occurred while attempting to generate and upload contract..".$e);
        }
        // generate and save letter of happiness
        try{
            // generate and save contract
            ($order->package->project->project_type_id == ProjectType::land()->id) ? 
                Helpers::generateLetterOfHappiness($clientPackage, false) : Helpers::generateHomesLetterOfHappiness($clientPackage);
            // dd('generate receipt');
            $uploadedLetter = "files/letter_of_happiness_{$order->id}.pdf";
            
            $response = Helpers::moveUploadedFileToCloud($uploadedLetter, FileTypes::PDF->value, $order->client->id, 
            FilePurpose::LETTER_OF_HAPPINESS->value, "app\Models\Client", "client-letter_of_happiness");
            if($response['success']) {
                $letterOfHappinessFileId = $response['upload']['file']->id;
                $letterOfHappinessFileObj = $response['upload']['file'];
            }
            
        }catch(\Exception $e) {
            Utilities::logStuff("Error Occurred while attempting to generate and upload letter of happiness..".$e);
        }

        // mark the order as complete
        $order->completed = true;
        $order->update();


        // save the clientPackage and return it
        $clientPackageService = new ClientPackageService;
        $files = [];
        if($contractFileId) $files['contractFileId'] = $contractFileId;
        if($letterOfHappinessFileId) $files['happinessLetterFileId'] = $letterOfHappinessFileId;
        // dd($files);
        $clientPackage = $clientPackageService->saveClientPackageOrder($order, $files, $clientPackage);


        $fileMeta = ["belongsId"=>$clientPackage->id, "belongsType"=>"app\Models\ClientPackage"];
        if($contractFileObj) $fileService->updateFileObj($fileMeta, $contractFileObj);
        if($letterOfHappinessFileObj) $fileService->updateFileObj($fileMeta, $letterOfHappinessFileObj);

        return $clientPackage;
    }

}
