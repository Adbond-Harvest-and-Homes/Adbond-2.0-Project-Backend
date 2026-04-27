<?php

namespace app\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use app\Http\Requests\BaseRequest;

use app\EnumClass;

class CreatePromo extends BaseRequest
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
            "title" => "required|string|unique:promos,title",
            "discount" => "required_without:discountAmount|numeric",
            "discountAmount" => "required_without:discount|numeric",
            "start" => "nullable|date|date_format:Y-m-d",
            "end" => "nullable|date|date_format:Y-m-d",
            "description" => "nullable|string",
            "products" => "nullable|array",
            "products.*" => "array",
            "products.*.type" => ["string", Rule::in(EnumClass::promoProductTypes())],
            "products.*.id" => "integer",
            "promoCode" => "nullable|array",
            "promoCode.*" => "nullable|array",
            "promoCode.*.code" => "string|unique:promo_codes,code",
            "promoCode.*.expiry" => "nullable|date|date_format:Y-m-d",
            "promoCode.*.maxUsage" => "nullable|integer"
        ];
    }
}
