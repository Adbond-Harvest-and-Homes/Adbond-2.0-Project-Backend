<?php

namespace app\Http\Requests\Client;

use Illuminate\Validation\Rule;
use app\Http\Requests\BaseRequest;

use app\EnumClass;

class UpdateFamilyMember extends BaseRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "title" => "string|nullable",
            "firstname" => "nullable|string",
            "lastname" => "nullable|string",
            "gender" => ["nullable", "string", Rule::in(EnumClass::genders())],
            "relationship" => ["nullable", "string", Rule::in(EnumClass::familyRelationships())],
            "dob" => "nullable|date|before:today",
            "email" => "nullable|string|email",
            "phoneNumber" => "nullable|string|min:8|max:25",
        ];
    }
}
