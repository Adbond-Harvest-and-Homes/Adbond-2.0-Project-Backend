<?php

namespace app\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use app\Http\Resources\FileResource;

class ClientPurchaseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $receipt = $this->relationLoaded("latestPaymentWithReceipt") ? $this->latestPaymentWithReceipt?->paymentReceipt : null;

        return [
            "id" => $this->id,
            "purchaseDate" => $this->order_date,
            "client" => [
                "id" => $this->client?->id,
                "name" => trim(ucfirst($this->client?->firstname) . " " . ucfirst($this->client?->lastname)),
            ],
            "project" => $this->package?->project?->name,
            "projectType" => $this->package?->project?->projectType?->name,
            "package" => $this->package?->name,
            "amount" => $this->amount_payable,
            "status" => $this->paymentStatus?->name,
            "invoice" => $receipt ? new FileResource($receipt) : null,
        ];
    }
}
