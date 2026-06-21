<?php

namespace app\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use app\Http\Requests\BaseRequest;

class UpdateJobAdvert extends BaseRequest
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
            "title" => [
                "nullable",
                "string",
                "min:3",
                "max:255",
                Rule::unique("job_adverts", "title")->ignore($this->route("id"))
            ],
            "departmentId" => "nullable|exists:departments,id",
            "employmentTypeId" => "nullable|exists:employment_types,id",
            "location" => "nullable|string|max:255",
            "slots" => "nullable|integer|min:1",
            "deadline" => "nullable|date",
            "description" => "nullable|string",
        ];
    }
}
