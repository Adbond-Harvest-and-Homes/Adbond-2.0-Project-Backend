<?php

namespace app\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use app\Http\Resources\UserBriefResource;
use app\Http\Resources\ClientBriefResource;
use app\Http\Resources\StaffTypeResource;
use app\Http\Resources\RoleResource;
use app\Http\Resources\FileResource;

use app\Models\Role;

use app\Helpers;
use app\Utilities;

class UserResource extends JsonResource
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
            // 'title' => $this->title,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'name' => $this->full_name,
            'email' => $this->email,
            'profilePhoto' => new FileResource($this->photo),
            'role' => new RoleResource($this->role),
            'staffType' => new StaffTypeResource($this->staffType),
            'refererCode' => $this->referer_code,
            'staffRefererCode' => $this->staff_referer_code,
            'phoneNumber' => $this->phone_number,
            'address' => $this->address,
            'gender' => $this->gender,
            'postalCode' => $this->postal_code,
            // 'country' => ($this->country) ? $this->country->name : null,
            'address' => $this->address,
            'maritalStatus' => $this->marital_status,
            'registeredBy' => new UserBriefResource($this->whenLoaded('registerer')),
            'clients' => ClientBriefResource::collection($this->whenLoaded('clients')),
            // 'sales' => OrderSalesResource::collection($this->sales),
            // 'commission' => $this->commission(),
            // 'commission_before_tax' => $this->beforeTax(),
            'commissionBalance' => $this->commission_balance,
            'dateJoined' => $this->date_joined,
            // 'rating' => new StaffRatingResource($this->rating()),
            'ratingOpen' => (Utilities::isMidYear(date('Y-m-d')) || Utilities::isEndYear(date('Y-m-d'))) ? true : false,
            'canRate' => ($this->role && ($this->role->id==Role::HR()?->id || $this->role->id==Role::SuperAdmin()?->id)) ? true : false,
            // 'commission_tax' => Helpers::companyInfo()->commission_tax,
            'bank' => $this->bank?->name,
            'accountNumber' => $this->account_number,
            'accountName' => $this->account_name
        ];
    }
}
