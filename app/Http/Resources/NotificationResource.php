<?php

namespace app\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
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
            "notificationType" => $this->notification_type,
            "targetId" => $this->target_id,
            "userId" => $this->user_id,
            "message" => $this->message,
            "read" => ($this->read == 1) ? true : false,
            "date" => $this->created_at
        ];
    }
}
