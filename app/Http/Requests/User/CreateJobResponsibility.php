<?php

namespace app\Http\Requests\User;

use app\Http\Requests\BaseRequest;

class CreateJobResponsibility extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "name" => "required|string|min:2|max:255|unique:job_responsibilities,name",
        ];
    }
}
