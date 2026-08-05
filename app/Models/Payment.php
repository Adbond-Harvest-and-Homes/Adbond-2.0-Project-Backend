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
use app\Enums\PackageType;
use app\Enums\BondOwnershipType;

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

    public function markDocUploaded()
    {
        $this->docs_uploaded = 1;
        $this->save();

        return $this;
    }

    public function markReceiptSent()
    {
        $this->receipt_sent = 1;
        $this->save();

        return $this;
    }
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($payment) {
            if($payment->receipt_file_id == $payment->evidence_file_id) $payment->receipt_file_id = null;
            if($payment->confirmed==null && $payment->payment_mode_id && $payment->payment_mode_id == PaymentMode::cardPayment()->id) {
                $payment->confirmed = ($payment->flag==0 && $payment->success==1);
            }
        });

        static::created(function ($payment) {
            if($payment->evidence_file_id) self::updateFile($payment->evidence_file_id, $payment);

        });



        // static::updated(function ($payment) {
        //     if($payment->confirmed == 1) {
        //         // if payment is confirmed

        //         //if its a order purchase
        //         if($payment->purchase_type == Order::$type) {
        //             //deduct unit or bond slot from the package
        //             $order = $payment->purchase;
        //             $package = $order?->package;
        //             if($package) {
        //                 //if its a bond package and co-ownership
        //                 if($package->type == PackageType::BOND->value && $package->bond_ownership_type == BondOwnershipType::CO_OWNERSHIP->value) {

        //                 }
        //             }
        //         }

        //     }
        // });
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
