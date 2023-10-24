<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;


class UserRequest extends FormRequest
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
        $id = ($request->user_id) ? $request->user_id : null;

        $username = [
            'required', 'max:255',
            ($id) ? Rule::unique('users')->where('id', "=", $id) : 'unique:users'
        ];

        $email = [
            'required', 'email',
            ($id) ? Rule::unique('users')->where('id', "=", $id) : 'unique:users'
        ];

        return [
            'username' => $username,
            'email' => $email,
            'password' => 'required',
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
            'username.required' => 'Username is required',
            'username.unique' => 'Enter Unique username',
            'username.max' => 'First name no longer than 255 characters',
            'password.required' => 'Password is required',
            'email.required' => 'Email is required',
            'email.unique' => 'Email is unique!',
            'email.max' => 'Email  no longer than 255 characters',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        $response['status'] = false;
        $response['message'] = $validator->errors()->toArray();
        // print_r($validator);
        // print_r($response);
        // return response()->json($response, 200); 
        throw new HttpResponseException(
            response()->json($response, 200)
        );
    }
}
