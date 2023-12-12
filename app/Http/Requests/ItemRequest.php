<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Illuminate\Http\UploadedFile;

class ItemRequest extends FormRequest
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
        $other_image_rules['other_image.*'] = $image_mimes;
        if ($id) {
            if (!empty($request->item_image) && !($request->item_image[0] instanceof UploadedFile)) {
                $image_mimes = [];
            }

            if (!empty($request->other_image)) {
                $other_image_rules = [];
                foreach ($request->other_image as $index => $value) {
                    $rule = (!($value instanceof UploadedFile)) ? "" : $image_mimes;
                    $other_image_rules['other_image.' . $index] = $rule;
                }
            }
        }

        return [
            'name' => 'required|max:100',
            'category_id' => 'required',
            'description' => 'max:1000',
            'item_image.*' => $image_mimes,
            // 'other_image.*' => $image_mimes,
        ] + $other_image_rules;
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Item name is required',
            'category_id.required' => 'Category is required',
            'name.max' => 'Item name no longer than 100 characters',
            'description.max' => 'Description longer than 1000 characters',
            'item_image.*.mimes' => 'Allowed image formats: jpeg,png,jpg,gif,svg',
            'other_image.*.mimes' => 'Allowed image formats: jpeg,png,jpg,gif,svg',
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
