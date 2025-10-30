<?php

namespace app\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use app\Http\Resources\ClientBriefResource;
use app\Http\Resources\PackageResource;
use app\Http\Resources\PaymentStatusResource;
use app\Http\Resources\OrderDiscountResource;
use app\Http\Resources\PaymentResource;

class OrderResource extends JsonResource
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
            "client" => new ClientBriefResource($this->whenLoaded("client")),
            "package" => new PackageResource($this->whenLoaded("package")),
            "units" => $this->units,
            "amountPayed" => $this->amount_payed,
            "amountPayable" => $this->amount_payable,
            "isInstallment" => $this->is_installment,
            "amountPerInstallment" => $this->amount_per_installment,
            "balance" => $this->balance,
            "discounts" => OrderDiscountResource::collection($this->whenLoaded("discounts")),
            "paymentStatus" => new PaymentStatusResource($this->whenLoaded("paymentStatus")),
            "orderDate" => $this->order_date,
            "paymentDueDate" => $this->payment_due_date,
            "payments" => PaymentResource::collection($this->whenLoaded('payments'))
        ];
    }
}
