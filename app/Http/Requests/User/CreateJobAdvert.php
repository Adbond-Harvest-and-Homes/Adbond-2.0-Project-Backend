<?php

namespace app\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use app\Http\Requests\BaseRequest;

class CreateJobAdvert extends BaseRequest
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
            "title" => "required|string|min:3|max:255|unique:job_adverts,title",
            "departmentId" => "required|exists:departments,id",
            "employmentTypeId" => "required|exists:employment_types,id",
            "location" => "nullable|string|max:255",
            "slots" => "nullable|integer|min:1",
            "deadline" => "nullable|date",
            "description" => "required|string",
            "requirements" => "nullable|array",
            "requirements.*" => "required|integer|exists:job_requirements,id",
            "responsibilities" => "nullable|array",
            "responsibilities.*" => "required|integer|exists:job_responsibilities,id",
            "benefits" => "nullable|array",
            "benefits.*" => "required|integer|exists:job_benefits,id",
        ];
    }
}
