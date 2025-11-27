<?php

namespace app\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use app\Http\Resources\ProjectTypeResource;
use app\Http\Resources\PackageResource;
use app\Http\Resources\PromoResource;

class ProjectResource extends JsonResource
{
    private $onlyActivePackages = false;

    /** @param  mixed  $resource
     * @param  array  $options
     * @return void
     */
    public function __construct($resource, $options = [])
    {
        parent::__construct($resource);
        $this->onlyActivePackages = $options['only_active_packages'] ?? false;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Filter packages based on the parameter
        $packages = $this->onlyActivePackages 
            ? $this->packages->where('active', true)
            : $this->packages;
            
        $resource = [
            "id" => $this->id,
            "identifier" => $this->identifier,
            "name" => $this->name,
            "description" => $this->description,
            "status" => ($this->active) ? "Active" : "Inactive",
            "country" => $this?->state?->country?->name,
            "state" => $this?->state?->name,
            "created" => $this->created_at->format("F j, Y"),
            "projectType" => new ProjectTypeResource($this->whenLoaded("projectType")),
            "packages" => PackageResource::collection($this->whenLoaded("packages")),
            "promos" => PromoResource::collection($this->promos)
            // "locations" => ProjectLocationResource::collection($this->whenLoaded("locations"))
        ];
        $resource['packageCount'] = $this->packages->count();
        $resource['canDelete'] = $this->canDelete();

        return $resource;
    }
}
