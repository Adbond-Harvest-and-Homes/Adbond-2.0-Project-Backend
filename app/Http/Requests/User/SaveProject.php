<?php

namespace app\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use app\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class SaveProject extends BaseRequest
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
            "name" => ["required", "string", Rule::unique('projects', 'name')->where(function ($query) {
                return $query->where('project_type_id', $this->projectTypeId);
            })],
            "projectTypeId" => ["required","integer","exists:project_types,id"],
            "description" => "nullable|string",
            // "address" => "required|string"
        ];
    }
}
