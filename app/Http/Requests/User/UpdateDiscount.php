<?php

namespace app\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

use app\Http\Requests\BaseRequest;

use app\EnumClass;

class UpdateDiscount extends BaseRequest
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
            "discount" => "required|numeric|max:100",
            "measurement" => ["nullable", Rule::in(EnumClass::Measurements())]
        ];
    }
}
