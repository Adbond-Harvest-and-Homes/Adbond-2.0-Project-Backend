<?php

namespace app\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use app\Http\Resources\PromoResource;

class PromoCodeResource extends JsonResource
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
            "code" => $this->code,
            "active" => ($this->active == 1) ? true : false,
            "expiry" => $this->expiry,
            "usageCount" => $this->usage_count,
            "maxUsage" => $this->max_usage,
            "packageLimited" => ($this->package_limited == 1) ? true : false,
            "combineWithFullPaymentDiscount" => ($this->combine_with_full_payment_discount == 1) ? true : false,
            "promo" => new PromoResource($this->whenLoaded("promo"))
        ];
    }
}
