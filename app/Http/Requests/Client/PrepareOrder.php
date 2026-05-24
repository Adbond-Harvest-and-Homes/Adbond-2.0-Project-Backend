<?php

namespace app\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use app\Http\Requests\BaseRequest;

use app\EnumClass;

class PrepareOrder extends BaseRequest
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
            "packageId" => "required|integer|exists:packages,id",
            "isInstallment" => "required|boolean",
            "installmentCount" => "required_if:isInstallment,true",
            "amount" => "required_if:isInstallment,true|numeric",
            "units" => "required|integer",
            "promoCode" => "nullable|string|exists:promo_codes,code",
            "processingId" => "nullable",
            "redemptionOption" => ['nullable', 'string', Rule::in(EnumClass::investmentRedemptionOptions())],
            // "redemptionPackageId" => "nullable|integer|exists:packages,id"
        ];
    }
}
