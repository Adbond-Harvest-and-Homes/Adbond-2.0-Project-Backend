<?php

namespace app\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use app\Http\Resources\ProjectResource;
use app\Http\Resources\ProjectTypeResource;
use app\Http\Resources\StateResource;
use app\Http\Resources\FileResource;
use app\Http\Resources\BenefitResource;

class PackageResource extends JsonResource
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
            "name" => $this->name,
            "type" => $this->type,
            "project" => new ProjectResource($this->whenLoaded("project")),
            "size" => $this->size,
            "amount" => $this->amount,
            "units" => $this->units,
            "availableUnits" => $this->available_units,
            "discounts" => $this->discounts,
            "minPrice" => $this->min_price,
            "installmentDuration" => $this->installment_duration,
            "infrastructureFee" => $this->infrastructure_fee,
            "description" => $this->description,
            "benefits" => BenefitResource::collection($this->benefits),
            "installmentOption" => $this->installment_option,
            "interestReturnDuration" => $this->interest_return_duration." Months",
            "interestReturnTimeline" => $this->interest_return_timeline." Months",
            "interestReturnPercentage" => $this->interest_return_percentage."%",
            "interestReturnAmount" => $this->interest_return_amount,
            "redemptionOptions" => $this->redemption_options,
            "redemptionPackage" => $this->redemptionPackage(),
            "vrUrl" => $this->vr_url,
            "active" => ($this->active) ? true : false,
            "status" => ($this->units==0 || $this->sold_out) ? "Sold Out" : (($this->active) ? "Active" : "Inactive"),
            "soldOut" => ($this->units==0 || $this->sold_out) ? true : false,
            "country" => $this->country?->name,
            "state" => $this?->stateModel?->name,
            "address" => $this->address,
            "location" => $this->address." ".$this->state,
            "brochure" => new FileResource($this->whenLoaded("brochure")),
            "media" => FileResource::collection($this->whenLoaded("media")),
            "promos" => PromoResource::collection($this->promos),
            "createdAt" => $this->created_at->format('F j, Y'), 
        ];
    }

    private function redemptionPackage()
    {
        return $this->redemptionPackage ?
        [
            'id' => $this->redemptionPackage->id,
            'name' => $this->redemptionPackage->name,
            "project" => $this->redemptionPackage->project->name,
            "state" => $this->redemptionPackage->state,
            "address" => $this->redemptionPackage->address,
            "location" => $this->redemptionPackage->address." ".$this->redemptionPackage->state,
            "size" => $this->redemptionPackage->size,
            "amount" => $this->redemptionPackage->amount,
            "minPrice" => $this->redemptionPackage->min_price,
            "infrastructureFee" => $this->redemptionPackage->infrastructure_fee,
            "description" => $this->redemptionPackage->description,
            "benefits" => BenefitResource::collection($this->redemptionPackage->benefits)
        ]
        : null;
    }
}
