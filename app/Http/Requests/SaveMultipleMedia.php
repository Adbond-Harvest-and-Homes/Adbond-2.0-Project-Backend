<?php

namespace app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use app\Http\Requests\BaseRequest;
use app\Rules\VideoDuration;

class SaveMultipleMedia extends BaseRequest
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
            "media" => "required|array",
            "media.*" => ["required","file","mimes:jpg,jpeg,png,gif,mp4,mpeg,mov,avi,wmv,webm,flv,3gp,m4v","max:50000", new VideoDuration(90)]
        ];
    }
}
