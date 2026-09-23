<?php

namespace app\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PackageRequestResource extends JsonResource
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
            "phone" => $this->phone,
            "email" => $this->email,
            "status" => $this->status,
            "package" => $this->package(),
            "createdAt" => $this->created_at->format('F j, Y'),
        ];
    }

    private function package()
    {
        return $this->package ?
        [
            "id" => $this->package->id,
            "name" => $this->package->name,
            "project" => $this->package?->project?->name,
            "amount" => $this->package->amount,
            "units" => $this->package->units,
            "availableUnits" => $this->package->available_units,
        ]
        : null;
    }
}
