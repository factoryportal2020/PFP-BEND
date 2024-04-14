<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use PhpParser\Node\Stmt\Foreach_;
use Illuminate\Validation\Rule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;


class CategoryRequest extends FormRequest
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

        $admin_id = Auth::user()->admin->id;

        $existName = DB::table('categories')->where('name', $request->name)->where('id', "!=", $id)->where('admin_id', "=", $admin_id)->first();

        $name = [
            'required', 'max:100',
            ($id && !$existName) ?  Rule::unique('categories')->where('id', "=", $id) : (($existName) ? 'unique:categories' : '')

        ];

        $image_mimes = 'mimes:jpeg,png,jpg,gif,svg';
        if ($id) {
            if (!empty($request->category_image) && !($request->category_image[0] instanceof UploadedFile)) {
                $image_mimes = [];
            }
        }

        return [
            'name' => $name,
            'sub_title' => 'required|max:100',
            'description' => 'max:1000',
            'category_image.*' => $image_mimes,
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Category name is required',
            'name.max' => 'Category name no longer than 100 characters',
            'description.max' => 'Description longer than 1000 characters',
            'category_image.*.mimes' => 'Allowed image formats: jpeg,png,jpg,gif,svg',
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
