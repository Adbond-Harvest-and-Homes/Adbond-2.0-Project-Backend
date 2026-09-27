<?php

namespace app\Services;

use app\Models\FamilyMember;

use app\Enums\FamilyRelationship;

class FamilyMemberService
{
    public function list($clientId)
    {
        return FamilyMember::where("client_id", $clientId)->orderBy("created_at", "DESC")->get();
    }

    public function find($id, $clientId)
    {
        return FamilyMember::where("id", $id)->where("client_id", $clientId)->first();
    }

    public function create($data)
    {
        $familyMember = new FamilyMember;
        $familyMember->client_id = $data['clientId'];
        return $this->fill($familyMember, $data);
    }

    public function update($familyMember, $data)
    {
        return $this->fill($familyMember, $data);
    }

    public function delete($familyMember)
    {
        return $familyMember->delete();
    }

    /**
     * A client can only have one spouse recorded at a time.
     */
    public function hasSpouse($clientId, $excludingId = null)
    {
        $query = FamilyMember::where("client_id", $clientId)->where("relationship", FamilyRelationship::SPOUSE->value);
        if ($excludingId) $query->where("id", "!=", $excludingId);
        return $query->exists();
    }

    private function fill($familyMember, $data)
    {
        if (isset($data['title'])) $familyMember->title = $data['title'];
        if (isset($data['firstname'])) $familyMember->firstname = $data['firstname'];
        if (isset($data['lastname'])) $familyMember->lastname = $data['lastname'];
        if (isset($data['gender'])) $familyMember->gender = $data['gender'];
        if (isset($data['relationship'])) $familyMember->relationship = $data['relationship'];
        if (isset($data['dob'])) $familyMember->dob = $data['dob'];
        if (isset($data['email'])) $familyMember->email = $data['email'];
        if (isset($data['phoneNumber'])) $familyMember->phone_number = $data['phoneNumber'];
        if (isset($data['photoId'])) $familyMember->photo_id = $data['photoId'];
        $familyMember->save();
        return $familyMember;
    }
}
