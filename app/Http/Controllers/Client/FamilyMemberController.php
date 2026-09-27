<?php

namespace app\Http\Controllers\Client;

use Illuminate\Support\Facades\Auth;
use app\Http\Controllers\Controller;

use app\Http\Requests\Client\AddFamilyMember;
use app\Http\Requests\Client\UpdateFamilyMember;

use app\Http\Resources\FamilyMemberResource;

use app\Services\FamilyMemberService;

use app\Enums\FamilyRelationship;

use app\Utilities;

class FamilyMemberController extends Controller
{
    private $familyMemberService;

    public function __construct()
    {
        $this->familyMemberService = new FamilyMemberService;
    }

    public function index()
    {
        $familyMembers = $this->familyMemberService->list(Auth::guard('client')->user()->id);
        return Utilities::ok(FamilyMemberResource::collection($familyMembers));
    }

    public function store(AddFamilyMember $request)
    {
        try {
            $data = $request->validated();
            $clientId = Auth::guard('client')->user()->id;

            if ($data['relationship'] == FamilyRelationship::SPOUSE->value && $this->familyMemberService->hasSpouse($clientId)) {
                return Utilities::error402("You already have a spouse on record. Please update the existing entry instead");
            }

            $data['clientId'] = $clientId;
            $familyMember = $this->familyMemberService->create($data);

            return Utilities::okay("Family member added successfully", new FamilyMemberResource($familyMember));
        } catch (\Exception $e) {
            return Utilities::error($e, 'An error occurred while trying to perform this operation, Please try again later or contact support');
        }
    }

    public function update(UpdateFamilyMember $request, $id)
    {
        try {
            $data = $request->validated();
            $clientId = Auth::guard('client')->user()->id;

            $familyMember = $this->familyMemberService->find($id, $clientId);
            if (!$familyMember) return Utilities::error402("Family member not found");

            $relationship = $data['relationship'] ?? $familyMember->relationship;
            if ($relationship == FamilyRelationship::SPOUSE->value && $this->familyMemberService->hasSpouse($clientId, $familyMember->id)) {
                return Utilities::error402("You already have a spouse on record. Please update the existing entry instead");
            }

            $familyMember = $this->familyMemberService->update($familyMember, $data);

            return Utilities::okay("Family member updated successfully", new FamilyMemberResource($familyMember));
        } catch (\Exception $e) {
            return Utilities::error($e, 'An error occurred while trying to perform this operation, Please try again later or contact support');
        }
    }

    public function destroy($id)
    {
        try {
            $clientId = Auth::guard('client')->user()->id;

            $familyMember = $this->familyMemberService->find($id, $clientId);
            if (!$familyMember) return Utilities::error402("Family member not found");

            if ($familyMember->orders()->exists() || $familyMember->clientPackages()->exists()) {
                return Utilities::error402("This family member owns properties and cannot be removed. Reassign their properties first");
            }

            $this->familyMemberService->delete($familyMember);

            return Utilities::okay("Family member removed successfully");
        } catch (\Exception $e) {
            return Utilities::error($e, 'An error occurred while trying to perform this operation, Please try again later or contact support');
        }
    }
}
