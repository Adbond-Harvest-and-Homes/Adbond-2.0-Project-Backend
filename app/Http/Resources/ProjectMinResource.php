<?php

namespace app\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectMinResource extends JsonResource
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
            "identifier" => $this->identifier,
            "name" => $this->name,
            "country" => $this?->stateModel?->country?->name,
            "state" => $this?->stateModel?->name,
            "description" => $this->description,
            "status" => ($this->active) ? "Active" : "Inactive",
            "created" => $this->created_at->format("F j, Y"),
            "projectType" => new ProjectTypeResource($this->whenLoaded("projectType")),
            "packages" => PackageResource::collection($this->whenLoaded("packages"))
            // "locations" => ProjectLocationResource::collection($this->whenLoaded("locations"))
        ];
    }
}
