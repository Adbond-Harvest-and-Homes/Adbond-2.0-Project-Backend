<?php

namespace app\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use app\Http\Requests\BaseRequest;

class UpdatePromoCode extends BaseRequest
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
            "code" => "nullable|string|unique:promo_codes,code",
            "expiry" => "nullable|date|date_format:Y-m-d",
            "maxUsage" => "nullable|integer",
            "packageLimited" => "nullable|boolean"
        ];
    }
}
