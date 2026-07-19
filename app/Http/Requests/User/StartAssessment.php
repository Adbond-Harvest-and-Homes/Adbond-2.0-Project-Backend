<?php

namespace app\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use app\Http\Requests\BaseRequest;

use app\EnumClass;

class StartAssessment extends BaseRequest
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
            "assessmentId" => "nullable|integer",
            "firstname" => "required|string",
            "lastname" => "required|string",
            "email" => "required|email|unique:assessment_attempts,email",
            "phoneNumber" => "required|unique:assessment_attempts,phone_number",
            "occupation" => "nullable|string",
            "address" => "nullable|string",
            "gender" => ["nullable", Rule::in(EnumClass::genders())],
            "categoryId" => "required|exists:virtual_staff_categories,id",
            "referralCode" => "nullable|string"
        ];
    }
}
