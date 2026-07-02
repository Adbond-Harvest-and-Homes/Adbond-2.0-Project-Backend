<?php

namespace app\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use app\Http\Requests\BaseRequest;

class UpdatePromo extends BaseRequest
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
            // "title" => "nullable|string|unique:promos,title",
            "title" => [
                "nullable",
                "string",
                Rule::unique("promos", "title")->ignore($this->route("promoId"))
            ],
            "discount" => "nullable|numeric",
            "discountAmount" => "nullable|numeric",
            "start" => "nullable|date|date_format:Y-m-d",
            "end" => "nullable|date|date_format:Y-m-d",
            "description" => "nullable|string",
        ];
    }
}
