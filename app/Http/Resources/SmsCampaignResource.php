<?php

namespace app\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SmsCampaignResource extends JsonResource
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
            "message" => $this->message,
            "senderId" => $this->sender_id,
            "source" => $this->source,
            "status" => $this->status,
            "totalRecipients" => $this->total_recipients,
            "sentCount" => $this->sent_count,
            "failedCount" => $this->failed_count,
            "createdBy" => $this->creator ? trim($this->creator->firstname . " " . $this->creator->lastname) : null,
            "sentAt" => $this->sent_at,
            "createdAt" => $this->created_at,
            "recipients" => $this->whenLoaded('recipients', function () {
                return SmsCampaignRecipientResource::collection($this->recipients);
            }),
        ];
    }
}
