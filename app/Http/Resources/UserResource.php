<?php

namespace app\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use app\Http\Resources\UserBriefResource;
use app\Http\Resources\ClientBriefResource;
use app\Http\Resources\StaffTypeResource;
use app\Http\Resources\RoleResource;
use app\Http\Resources\FileResource;
use app\Http\Resources\OrderResource;
use app\Http\Resources\PaymentResource;
use app\Http\Resources\UserHistoryResource;
use app\Http\Resources\VirtualStaffCategoryResource;

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
            "downlines" => $this->staffReferrals->count(),
            "lastActivityDate" => $this->whenLoaded('lastActivity', function () {
                return $this->lastActivity->created_at->diffForHumans();
            }),
            "virtualStaffCategory" => new VirtualStaffCategoryResource($this->virtualStaffCategory),
            'registeredBy' => new UserBriefResource($this->whenLoaded('registerer')),
            'clientsCount' => $this->whenCounted('clients'),
            'clients' => ClientBriefResource::collection($this->whenLoaded('clients')),
            'salesAmount' => $this->whenLoaded('sales', function () {
                return $this->sales->sum('amount_payable');
            }),
            'histories' => UserHistoryResource::collection($this->whenLoaded('histories')),
            'conversionRate' => $this->conversion_rate,
            'clientTransactions' => PaymentResource::collection($this->whenLoaded("clientTransactions")),
            'sales' => OrderResource::collection($this->whenLoaded("clientOrders")),
            // 'commission' => $this->commission(),
            // 'commission_before_tax' => $this->beforeTax(),
            "totalCommissionEarned" => $this->whenLoaded('commissionEarnings', function () {
                return $this->commissionEarnings()->sum("commission_after_tax");
            }),
            'commissionBalance' => $this->commission_balance,
            'dateJoined' => $this->date_joined,
            // 'rating' => new StaffRatingResource($this->rating()),
            'ratingOpen' => (Utilities::isMidYear(date('Y-m-d')) || Utilities::isEndYear(date('Y-m-d'))) ? true : false,
            'canRate' => ($this->role && ($this->role->id == Role::HR()?->id || $this->role->id == Role::SuperAdmin()?->id)) ? true : false,
            // 'commission_tax' => Helpers::companyInfo()->commission_tax,
            'bank' => $this->bank?->name,
            'accountNumber' => $this->account_number,
            'accountName' => $this->account_name
        ];
    }
}
