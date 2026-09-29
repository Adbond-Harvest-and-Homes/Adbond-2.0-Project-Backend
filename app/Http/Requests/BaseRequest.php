<?php

namespace app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;


class BaseRequest extends FormRequest
{

    /**
     * If validator fails return the exception in json form
     * @param Validator $validator
     * @return array
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
        response()->json([
                'statusCode' => 422,
                'message' => $validator->errors()->all()[0],
                'error' => $validator->errors()->all()[0],
                'errors' => $validator->errors()
            ], 422)
        );
    }

    // /**
    //  * Custom message for validation
    //  *
    //  * @return array
    //  */
    // public function messages()
    // {
    //     $messages = [];
    //     $messages["houseId.exists"] = "House does not exist";
    //     return $messages;
    // }
}
