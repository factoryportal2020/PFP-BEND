<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class AdminRequest extends FormRequest
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

        $existEmail = DB::table('admins')->where('email', $request->email)->where('id', "!=", $id)->first();
        // $existPhoneNo = DB::table('customers')->where('phone_no', $request->phone_no)->where('id', "!=", $id)->first();
        $existPhoneNo = DB::table('users')->where('phone_no', $request->phone_no)->where('id', "!=", $request->user_id)->first();

        $email = [
            'required', 'email',
            ($id && !$existEmail) ? Rule::unique('admins')->where('id', "=", $id) : 'unique:admins',
        ];

        $phone_no = [
            'required', 'max:50',
            ($id && !$existPhoneNo) ? Rule::unique('users')->where('id', "=", $request->user_id) : 'unique:users'
        ];
        
        $image_mimes = 'mimes:jpeg,png,jpg,gif,svg';
        if ($id) {
            if (!empty($request->profile_image) && !($request->profile_image[0] instanceof UploadedFile)) {
                $image_mimes = [];
            }
        }

        return [
            'first_name' => 'max:100',
            'last_name' => 'max:100',
            'email' => $email,
            'gender' => 'max:25',
            'phone_no' => $phone_no,
            'whatsapp_no' => 'max:50',
            'instagram_id' => 'max:100',
            'address' => 'max:1000',
            'city' => 'max:100',
            'state' => 'max:100',
            'notes' => 'max:1000',
            'profile_image.*' => $image_mimes,
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
            'first_name.max' => 'First name no longer than 100 characters',
            'last_name.max' => 'Last name no longer than 100 characters',
            'email.unique' => 'Email is already registered!',
            'email.max' => 'Email  no longer than 255 characters',
            'gender.max' => 'gender longer than 25 characters',
            'phone_no.required' => 'phone no is required',
            'phone_no.max' => 'phone no longer than 50 characters',
            'whatsapp_no.max' => 'whatsapp_no longer than 50 characters',
            'instagram_id.max' => 'instagram_id longer than 100 characters',
            'address.max' => 'address longer than 100 characters',
            'city.max' => 'city longer than 100 characters',
            'state.max' => 'state longer than 100 characters',
            'notes.max' => 'notes longer than 1000 characters',
            'profile_image.*.mimes' => 'Allowed image formats: jpeg,png,jpg,gif,svg',
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
