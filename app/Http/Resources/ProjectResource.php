<?php

namespace app\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use app\Http\Resources\ProjectTypeResource;
use app\Http\Resources\PackageResource;
use app\Http\Resources\PromoResource;

use app\Http\Resources\CountryResource;
use app\Http\Resources\StateResource;

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
        $resource = [
            "id" => $this->id,
            "identifier" => $this->identifier,
            "name" => $this->name,
            "description" => $this->description,
            "status" => ($this->active) ? "Active" : "Inactive",
            "countries" => CountryResource::collection($this->whenLoaded("countries")),
            "states" => StateResource::collection($this->whenLoaded("states")),
            // "state" => $this?->stateModel?->name,
            "created" => $this->created_at->format("F j, Y"),
            "projectType" => new ProjectTypeResource($this->whenLoaded("projectType")),
            "promos" => PromoResource::collection($this->whenLoaded("promos"))
            // "locations" => ProjectLocationResource::collection($this->whenLoaded("locations"))
        ];

        if ($this->relationLoaded('packages')) {
            $packages = $this->onlyActivePackages
                ? $this->packages->where('active', true)
                : $this->packages;
            $resource["packages"] = PackageResource::collection($packages);
            $resource['packageCount'] = $packages->count();
            $resource['canDelete'] = $this->canDelete();
        } else {
            $resource['packageCount'] = $this->when(isset($this->packages_count), $this->packages_count);
            $resource['canDelete'] = $this->when(isset($this->packages_count), function() {
                return $this->packages_count == 0;
            });
        }

        return $resource;
    }
}
