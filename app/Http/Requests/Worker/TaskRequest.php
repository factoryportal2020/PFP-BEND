<?php

namespace App\Http\Requests\Worker;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Illuminate\Http\UploadedFile;

class TaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(Request $request)
    {
        $id = ($request->encrypt_id) ? encryptID($request->encrypt_id, 'd') : null;
        $image_mimes = 'mimes:jpeg,png,jpg,gif,svg';

        $working_image_rules['working_image.*'] = $image_mimes;
        $completed_image_rules['completed_image.*'] = $image_mimes;
        if ($id) {

            if (!empty($request->task_image) && !($request->task_image[0] instanceof UploadedFile)) {
                $image_mimes = [];
            }

            if (!empty($request->working_image)) {
                $working_image_rules = [];
                foreach ($request->working_image as $index => $value) {
                    $rule = (!($value instanceof UploadedFile)) ? "" : $image_mimes;
                    $working_image_rules['working_image.' . $index] = $rule;
                }
            }

            if (!empty($request->completed_image)) {
                $completed_image_rules = [];
                foreach ($request->completed_image as $index => $value) {
                    $rule = (!($value instanceof UploadedFile)) ? "" : $image_mimes;
                    $completed_image_rules['completed_image.' . $index] = $rule;
                }
            }
        }

        return $working_image_rules + $completed_image_rules;
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'working_image.*.mimes' => 'Allowed image formats: jpeg,png,jpg,gif,svg',
            'completed_image.*.mimes' => 'Allowed image formats: jpeg,png,jpg,gif,svg',
        ];
    }


    public function failedValidation(Validator $validator)
    {
        $response['status'] = false;
        $response['message'] = $validator->errors()->toArray();
        throw new HttpResponseException(
            response()->json($response, 200)
        );
    }
}
