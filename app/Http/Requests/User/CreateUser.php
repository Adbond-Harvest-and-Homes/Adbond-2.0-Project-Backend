<?php

namespace app\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use app\Http\Requests\BaseRequest;

class CreateUser extends BaseRequest
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
            "firstname" => "required|string",
            "lastname" => "required|string",
            "email" => "required|email|unique:users,email",
            "phoneNumber" => "string|max:15|min:11",
            "staffTypeId" => "required|integer|exists:staff_types,id",
            "roleId" => "required|integer|exists:roles,id",
            "dateJoined" => "nullable|date|before:today",
            "referalCode" => "nullable|string|exists:users,staff_referer_code"
        ];
    }
}
