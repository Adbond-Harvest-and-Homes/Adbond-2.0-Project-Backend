<?php

namespace app\Http\Requests\Client;

use app\Http\Requests\BaseRequest;

class AssignAssetOwner extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Omit or send null familyMemberId to assign the property back to yourself.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "familyMemberId" => "nullable|integer|exists:family_members,id",
        ];
    }
}
