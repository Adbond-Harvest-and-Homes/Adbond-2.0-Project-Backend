<?php 

namespace app\Services;

use PDF;

use app\Jobs\SendPaymentEmail;

use app\Models\Payment;

use app\Services\OrderService;
use app\Services\FileService;
use app\Services\PaymentService;

use app\Enums\FileTypes;
use app\Enums\FilePurpose;
use app\Enums\UserType;

use app\Helpers;
use app\Utilities;

class ReceiptService 
{
    public function generateReceiptNo($purchaseId, $clientId, $processingId=null)
    {
        $i = 0;
        do{
            if(!$processingId || $i > 0) $processingId = Utilities::generateRandomNumber(5);
            $receiptNo = $purchaseId.$processingId.$clientId;
            $exists = Payment::where("receipt_no", $receiptNo)->first();
            $i++;
        }while($exists);
        return $receiptNo;
    }

    public function generateReceipt($payment)
    {
        $file = 'files/receipt'.$payment->receipt_no.'.pdf';
        $publicFile = public_path('files/receipt'.$payment->receipt_no.'.pdf');

        if (file_exists($publicFile)) {
            return $file;
        }

        // if(file_exists($file)) return $file;

        $address1 = '';
        $address2 = '';
        $address3 = '';
        $addressArr = Helpers::formatAddress($payment?->client?->address);
        if(count($addressArr) > 0) {
            if(isset($addressArr[0])) $address1 = $addressArr[0];
            if(isset($addressArr[1])) $address2 = $addressArr[1];
            if(isset($addressArr[2])) $address3 = $addressArr[2];
        }
        $discount = app(OrderService::class)->getTotalDiscount($payment?->purchase);
        
        $unitSize = $payment?->purchase?->package?->size;
        $size = ($unitSize != null && $payment?->purchase?->units != null && $payment?->purchase?->units > 0) ? $unitSize * $payment?->purchase?->units : $unitSize;
        $purchaseBalance = $payment?->purchase?->balance ?? 0;
        $amount = $payment?->amount ?? 0;
        $totalPayed = $payment?->purchase?->amount_payed + $amount;
        $balance = $purchaseBalance - $amount;
        $balance = ($balance >= 0) ? $balance : 0;
        $pdfData = [
            'image' => 'logo.png', 
            'name' => ucfirst($payment?->client?->full_name),
            'clientState' => $payment?->client?->state?->name ?? '',
            'clientCountry' => $payment?->client?->country?->name ?? '',
            'receiptNo' => $payment->receipt_no,
            'address1' => $address1,
            'address2' => $address2,
            'address3' => $address3,
            'date' => date('jS F, Y'),
            'package' => $payment?->purchase?->package?->name,
            'project' => $payment?->purchase?->package?->project?->name,
            'paymentMethod' => ucfirst($payment?->paymentMode?->name),
            'price' => $payment?->purchase?->package?->amount,
            'amount' => $payment->purchase->amount_payable,
            'currentAmount' => $payment->amount,
            'amountPaid' => $totalPayed,
            'units' => $payment?->purchase?->units,
            'size' => $size,
            'discount' => $discount,
            'balance' => $balance,
            'project_name_state' => $payment?->purchase?->package?->name . " " . $payment?->purchase?->package?->stateModel?->name
        ];

        // dd($pdfData);
        $pdf = PDF::loadView('pdf/receipt', $pdfData);

        $pdf->setOptions(array('isRemoteEnabled' => true));
        // return $pdf->stream('receipt.pdf');
        $pdf->save($publicFile);
        return $file;
        // dd('done');
    }

    public function uploadReceipt($payment, $filePath=null)
    {
        $user = $payment->client;
        $uploadedReceipt = $filePath ?? 'files/receipt'.$payment->receipt_no.'.pdf';
        $publicFile = public_path($uploadedReceipt);
            // dd('time to move..'.$uploadedReceipt);
            // dd($publicFile);
        if(file_exists($publicFile)) {
            $response = Helpers::moveUploadedFileToCloud($uploadedReceipt, FileTypes::PDF->value, $user->id, 
                                FilePurpose::PAYMENT_RECEIPT->value, UserType::CLIENT->value, "client-receipts");
            Utilities::logStuff("uploaded to cloudinary");
            if($response['success']) {
                $file = $response['upload']['file'];
                $fileService = new FileService;
                $fileMeta = ["belongsId"=>$payment->id, "belongsType"=>"app\Models\Payment"];
                $fileService->updateFileObj($fileMeta, $file);

                //update payment with the uploaded receipt;
                app(PaymentService::class)->update(['receiptFileId' => $file->id], $payment);
                Utilities::logStuff("saved file to db");
                // $payment->markDocUploaded();
            }else{
                Utilities::logStuff("was not successful uploading to cloudinary");
                Utilities::logStuff($response);
            }
                
            // dd("got here");
            return $uploadedReceipt;
        }else{
            dd("not found..".$publicFile);
            Utilities::logStuff("Receipt file " . 'files/receipt'.$payment->receipt_no.'.pdf does not exist... Error in ReceiptService');
        }
    }
}