<?php

namespace app\Http\Requests\User;

use app\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class UpdateJobBenefit extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "name" => [
                "required",
                "string",
                "min:2",
                "max:255",
                Rule::unique("job_benefits", "name")->ignore($this->route("id")),
            ],
        ];
    }
}
