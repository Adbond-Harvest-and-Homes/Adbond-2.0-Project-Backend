<?php

namespace app\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use app\Enums\PackagePaymentOption;

use app\Models\Order;
use app\Models\Payment;

use app\Http\Resources\ClientBriefResource;
use app\Http\Resources\FileResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "client" => $this->whenLoaded('client', fn() => $this->client->name), // Updated line
            "refId" => $this->receipt_no,
            "purchaseId" => $this->purchase_id,
            "purchaseType" => ($this->purchase_type == Order::$type) ? "Order" : "Offer",
            "package" => $this->purchase?->package?->name,
            "project" => $this->purchase?->package?->project?->name,
            "projectType" => $this->purchase?->package?->project?->projectType?->name,
            "amount" => $this->amount,
            "status" => ($this->confirmed == 1) ? "Successful" : (($this->confirmed === 0) ? "Failed" : "Pending"),
            "paymentMode" => $this->paymentMode?->name,
            // "confirmed" => $this->confirmed,
            "isNewPayment" => $this->isNewPayment(),
            "receipt" => new FileResource($this->paymentReceipt),
            "evidence" => new FileResource($this->paymentEvidence),
            "date" => $this->created_at->format('F j, Y'),
            "plan" => ($this->purchase && $this->purchase_type==Order::$type && $this->purchase?->is_installment==1) ?  PackagePaymentOption::INSTALLMENT->value : PackagePaymentOption::ONE_OFF->value,
        ];
    }

    private function isNewPayment()
    {
        if($this->confirmed == 0) { // rejected payments
            // check whether there has been a confirmed payment before them
            $confirmedPaymentCount = Payment::where("purchase_id", $this->purchase_id)->where("confirmed", 1)->where("created_at", "<", $this->created_at)->count();
            return ($confirmedPaymentCount == 0) ? true : false;
        }

        //for pending confirmation and confirmed payments
        $paymentCount = Payment::where("purchase_id", $this->purchase_id)->where("created_at", "<", $this->created_at)->where(function($query) {
            $query->where("confirmed", 1)->orWhereNull("confirmed");
        })->count();
        return ($paymentCount == 0) ? true : false;
    }
}
