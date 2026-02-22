<?php

namespace app\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use app\Http\Resources\PromoCodeResource;
use app\Http\Resources\ProjectMinResource;
use app\Http\Resources\PackageResource;

class PromoResource extends JsonResource
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
            "title" => $this->title,
            "discount" => $this->discount,
            "discountAmount" => $this->discount_amount,
            "start" => $this->start,
            "end" => $this->end,
            "active" => ($this->active == 1) ? true : false,
            "description" => $this->description,
            "promoCodes" => PromoCodeResource::collection($this->whenLoaded("promoCodes")),
            "projects" => ProjectMinResource::collection($this->whenLoaded("projects")),
            "packages" => PackageResource::collection($this->whenLoaded("packages"))
        ];
    }
}
