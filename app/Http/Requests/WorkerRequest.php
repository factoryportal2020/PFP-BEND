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

class WorkerRequest extends FormRequest
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

        $existEmail = DB::table('workers')->where('email', $request->email)->where('id', "!=", $id)->first();
        $existPhoneNo = DB::table('workers')->where('phone_no', $request->phone_no)->where('id', "!=", $id)->first();

        $email = [
            'required', 'email',
            ($id && !$existEmail) ? Rule::unique('workers')->where('id', "=", $id) : 'unique:workers'
        ];

        $phone_no = [
            'required', 'max:50',
            ($id && !$existPhoneNo) ? Rule::unique('workers')->where('id', "=", $id) : 'unique:workers'
        ];

        $image_mimes = 'mimes:jpeg,png,jpg,gif,svg';
        if ($id) {
            if (!empty($request->profile_image) && !($request->profile_image[0] instanceof UploadedFile)) {
                $image_mimes = [];
            }
        }

        return [
            'first_name' => 'required|max:100',
            'last_name' => 'required|max:100',
            'email' => $email,
            'gender' => 'required|max:25',
            'phone_no' => $phone_no,
            'whatsapp_no' => 'max:50',
            'instagram_id' => 'max:100',
            'address' => 'required|max:1000',
            'city' => 'required|max:100',
            'state' => 'required|max:100',
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
            'first_name.required' => 'First name is required',
            'first_name.max' => 'First name no longer than 100 characters',
            'last_name.required' => 'Last name is required',
            'last_name.max' => 'Last name no longer than 100 characters',
            'email.required' => 'Email is required',
            'email.unique' => 'Email is unique!',
            'email.max' => 'Email  no longer than 255 characters',
            'gender.required' => 'gender is required',
            'gender.max' => 'gender longer than 25 characters',
            'phone_no.required' => 'phone no is required',
            'phone_no.max' => 'phone no longer than 50 characters',
            'whatsapp_no.max' => 'whatsapp_no longer than 50 characters',
            'instagram_id.max' => 'instagram_id longer than 100 characters',
            'address.required' => 'address is required',
            'address.max' => 'address longer than 100 characters',
            'city.required' => 'city is required',
            'city.max' => 'city longer than 100 characters',
            'state.required' => 'state is required',
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
