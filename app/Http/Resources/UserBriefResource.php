<?php

namespace app\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use app\Http\Resources\FileResource;
use app\Http\Resources\RoleResource;
use app\Http\Resources\StaffTypeResource;

class UserBriefResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'email' => $this->email,
            'photo' => new FileResource($this->photo),
            'phoneNumber' => $this->phone_number,
            'passwordSet' => $this->password_set,
            'role' => new RoleResource($this->role),
            'staffType' => new StaffTypeResource($this->staffType),
        ];
    }
}
