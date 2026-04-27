<?php

namespace app\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use app\Http\Resources\ClientBriefResource;
use app\Http\Resources\OrderResource;
use app\Http\Resources\PaymentGatewayResource;
use app\Http\Resources\PaymentModeResource;
use app\Http\Resources\PaymentStatusResource;
use app\Http\Resources\PaymentPeriodStatusResource;
use app\Http\Resources\BankAccountResource;
use app\Http\Resources\FileResource;

use app\Models\Order;
use app\Models\Offer;
use app\Models\Payment;

class PaymentResource extends JsonResource
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
            "receiptNumber" => $this->receipt_no,
            "amount" => $this->amount,
            "confirmed" => ($this->confirmed === null) ? null : ($this->confirmed == 1),
            "rejectedMessage" => $this->rejected_message,
            "reference" => $this->reference,
            "success" => ($this->success == 1),
            "failureMessage" => $this->failure_message,
            "flag" => ($this->flag == 1),
            "flagMessage" => $this->flag_message,
            "purpose" => $this->purpose,
            "isNewPayment" => $this->isNewPayment(),
            "paymentMode" => new PaymentModeResource($this->paymentMode),
            "paymentGateway" => new PaymentGatewayResource($this->paymentGateway),
            "paymentEvidence" => new FileResource($this->paymentEvidence),
            "paymentReceipt" => new FileResource($this->paymentReceipt),
            "order" => ($this->purchase_type == Order::$type) ? new OrderResource($this->whenLoaded("order")) : []
        ];
    }

    private function isNewPayment()
    {
        // if($this->purchase_type == Offer::$type || $this->purchase->is_installment == 0) return true;
        // $paymentCount = Payment::where("purchase_id", $this->purchase_id)->where("confirmed", 1)->whereNull("confirmed")->count();
        // if($paymentCount == 0) return null;
        // return ($paymentCount == 1) ? true : false;

        if($this->confirmed == 0) { // rejected payments
            // check whether there has been a confirmed payment before them
            $confirmedPaymentCount = Payment::where("purchase_id", $this->purchase_id)->where("confirmed", 1)->where("created_at", "<", $this->created_at)->count();
            return ($confirmedPaymentCount == 0) ? true : false;
        }

        //for pending confirmation and confirmed payments
        $paymentCount = Payment::where("purchase_id", $this->purchase_id)->where("confirmed", 1)->whereNull("confirmed")->where("created_at", "<", $this->created_at)->count();
        return ($paymentCount == 0) ? true : false;
    }

}
