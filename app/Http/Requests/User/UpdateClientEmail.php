<?php

namespace app\Http\Requests\User;

use Illuminate\Validation\Rule;
use app\Http\Requests\BaseRequest;

class UpdateClientEmail extends BaseRequest
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
            "email" => [
                "required",
                "email",
                Rule::unique("clients", "email")->ignore($this->route('clientId')),
            ],
        ];
    }
}
