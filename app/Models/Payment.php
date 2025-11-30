<?php

namespace app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

use app\Models\PaymentMode;
use app\Models\Order;

use app\Services\FileService;
use app\Services\ClientPackageService;
use app\Services\PaymentService;

use app\Enums\ClientPackageOrigin;

use app\Helpers;

class Payment extends Model
{
    use HasFactory;

    public static $type = "app\Models\Payment";

    /**
     * Get the parent purchase model (Order or Offer).
     */
    public function purchase(): MorphTo
    {
        return $this->morphTo();
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function paymentMode()
    {
        return $this->belongsTo(PaymentMode::class);
    }

    public function paymentGateway()
    {
        return $this->belongsTo(PaymentGateway::class);
    }

    public function paymentStatus()
    {
        return $this->belongsTo(PaymentStatus::class);
    }

    public function paymentPeriodStatus()
    {
        return $this->belongsTo(PaymentPeriodStatus::class);
    }

    public function paymentEvidence()
    {
        return $this->belongsTo(File::class, "evidence_file_id", "id");
    }

    public function paymentReceipt()
    {
        return $this->belongsTo(File::class, "receipt_file_id", "id");
    }

    private static function uploadReceipt($payment)
    {
        $res = [];
        $clientPackageService = new ClientPackageService;
        $paymentService = new PaymentService;
        // if($payment->purchase_type == Order::$type && $payment->purchase->clientPackage->origin != ClientPackageOrigin::INVESTMENT->value && 
        //     Helpers::kycCompleted($payment->client)) {
        //     $purchase = $payment->purchase; 
        //     $isOffer = ($payment->purchase->clientPackage->origin == ClientPackageOrigin::OFFER->value);
        //     $clientPackageService->uploadContract($purchase, $payment->purchase->clientPackage, $isOffer);
        //     // dd("uploaded contract");
        // }
        if(Helpers::kycCompleted($payment->client)) {
            $res['receiptFile'] =  $paymentService->uploadReceipt($payment, $payment->client); 
            // dd($res);
        }
        return $res;
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($payment) {
            if($payment->confirmed==null && $payment->payment_mode_id && $payment->payment_mode_id == PaymentMode::cardPayment()->id) {
                $payment->confirmed = ($payment->flag==0 && $payment->success==1);
            }
            if($payment->confirmed) {
                $res = self::uploadReceipt($payment);
                $payment->receipt_file_id = $res['receiptFile']->id;
            }
        });

        static::created(function ($payment) {
            if($payment->evidence_file_id) self::updateFile($payment->evidence_file_id, $payment);

        });

        static::updating(function ($payment) {
            if($payment->confirmed == 1 && !$payment->receipt_file_id) {
                $res = $this->uploadReceipt($payment);
                $payment->receipt_file_id = $res['receiptFile']->id;
            }

            // if($payment->receipt_file_id) self::updateFile($payment->receipt_file_id, $payment);
        });
    }

    private static function updateFile($fileId, $payment)
    {
        $fileService = new FileService;
        $file = $fileService->getFile($fileId);
        if($file && (!$file->belongs_id || !$file->belongs_type)){
            $fileMeta = ["belongsId"=>$payment->id, "belongsType"=>self::$type];
            $fileService->updateFileObj($fileMeta, $file);
        }
    }
}
