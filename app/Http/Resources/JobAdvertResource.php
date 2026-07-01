<?php

namespace app\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use app\Http\Resources\DepartmentResource;
use app\Http\Resources\EmploymentTypeResource;
use app\Http\Resources\JobBenefitResource;
use app\Http\Resources\JobRequirementResource;
use app\Http\Resources\JobResponsibilityResource;

class JobAdvertResource extends JsonResource
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
            "slug" => $this->slug,
            "department" => new DepartmentResource($this->department),
            "employmentType" => new EmploymentTypeResource($this->employmentType),
            "location" => $this->location,
            "slots" => $this->slots,
            "deadline" => $this->deadline,
            "description" => $this->description,
            "isOpen" => $this->is_open == 1,
            "openedOn" => $this->opened_on,
            "benefits" => JobBenefitResource::collection($this->whenLoaded('benefits')),
            "requirements" => JobRequirementResource::collection($this->whenLoaded('requirements')),
            "responsibilities" => JobResponsibilityResource::collection($this->whenLoaded('responsibilities'))
        ];
        /*
            $table->id();
            $table->string("title");
            $table->foreignId("department_id");
            $table->foreignId("employment_type_id");
            $table->string("location")->nullable();
            $table->integer("slots")->nullable();
            $table->date("deadline")->nullable();
            $table->text("description");
            $table->boolean("is_open")->default(true);
            $table->date("opened_on");
        */
    }
}
