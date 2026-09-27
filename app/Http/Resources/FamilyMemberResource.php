<?php

namespace app\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FamilyMemberResource extends JsonResource
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
            "firstname" => $this->firstname,
            "lastname" => $this->lastname,
            "fullName" => $this->full_name,
            "gender" => $this->gender,
            "relationship" => $this->relationship,
            "dob" => $this->dob,
            "email" => $this->email,
            "phoneNumber" => $this->phone_number,
            "photo" => $this->photo ? new FileResource($this->photo) : null,
            "createdAt" => $this->created_at,
        ];
    }
}
