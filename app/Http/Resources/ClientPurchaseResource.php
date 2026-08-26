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
        return [
            "id" => $this->id,
            "purchaseDate" => $this->payment_date,
            "client" => [
                "id" => $this->client?->id,
                "name" => trim(ucfirst($this->client?->firstname) . " " . ucfirst($this->client?->lastname)),
            ],
            "project" => $this->purchase?->package?->project?->name,
            "projectType" => $this->purchase?->package?->project?->projectType?->name,
            "package" => $this->purchase?->package?->name,
            "amount" => $this->amount,
            "status" => $this->purchase?->paymentStatus?->name,
            "invoice" => $this->paymentReceipt ? new FileResource($this->paymentReceipt) : null,
        ];
    }
}
