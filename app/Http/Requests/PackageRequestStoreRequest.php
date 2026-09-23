<?php

namespace app\Http\Requests;

use app\Http\Requests\BaseRequest;

class PackageRequestStoreRequest extends BaseRequest
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
            "name" => "required|string",
            "phone" => "required|string",
            "email" => "required|email",
            "package_id" => "required|integer|exists:packages,id",
        ];
    }
}
