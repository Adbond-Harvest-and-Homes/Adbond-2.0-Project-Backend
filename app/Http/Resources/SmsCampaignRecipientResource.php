<?php

namespace app\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SmsCampaignRecipientResource extends JsonResource
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
            "client" => [
                "id" => $this->client?->id,
                "name" => trim(ucfirst($this->client?->firstname) . " " . ucfirst($this->client?->lastname)),
            ],
            "phoneNumber" => $this->phone_number,
            "status" => $this->status,
            "errorMessage" => $this->error_message,
            "sentAt" => $this->sent_at,
        ];
    }
}
