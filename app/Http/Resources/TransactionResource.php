<?php

namespace app\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use app\Enums\PackagePaymentOption;

use app\Models\Order;

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
            "receipt" => new FileResource($this->paymentReceipt),
            "evidence" => new FileResource($this->paymentEvidence),
            "date" => $this->created_at->format('F j, Y'),
            "plan" => ($this->purchase && $this->purchase_type==Order::$type && $this->purchase?->is_installment==1) ?  PackagePaymentOption::INSTALLMENT->value : PackagePaymentOption::ONE_OFF->value,
        ];
    }
}
