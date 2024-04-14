<?php

namespace App\Http\Requests;

use App\Models\Website;
use App\Models\Temp;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class WebsiteRequest extends FormRequest
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
        $auth = Auth::user();

        $admin_id = $auth->admin->id;

        $website = Website::where('admin_id', $admin_id)->first();
        if(!$website){
            $website = Temp::where('admin_id', $admin_id)->first();
        }
        $id = ($website) ? $website->id : null;

        $image_mimes = 'mimes:jpeg,png,jpg,gif,svg';

        $banner_image1_rules['banner_image1.*'] = $image_mimes;
        $banner_image2_rules['banner_image2.*'] = $image_mimes;
        $banner_image3_rules['banner_image3.*'] = $image_mimes;
        $about_image_rules['about_image.*'] = $image_mimes;
        $feature_image1_rules['feature_image1.*'] = $image_mimes;
        $feature_image2_rules['feature_image2.*'] = $image_mimes;
        $feature_image3_rules['feature_image3.*'] = $image_mimes;
        $logo_image_rules['logo_image.*']=$image_mimes;

        $company_name = [
            'required', 'max:400',
            ($id) ? Rule::unique('websites')->where('id', "=", $id) : 'unique:websites'
        ];

        $site_url = [
            'required', 'max:400',
            ($id) ? Rule::unique('websites')->where('id', "=", $id) : 'unique:websites'
        ];

        $email = [
            'required', 'email',
            ($id) ? Rule::unique('websites')->where('id', "=", $id) : 'unique:websites'
        ];

        $phone_no = [
            'required', 'max:50',
            ($id) ? Rule::unique('websites')->where('id', "=", $id) : 'unique:websites'
        ];

        if ($id) {

            $logo_image_rules = [];
            if (!empty($request->logo_image)) {
                foreach ($request->logo_image as $index => $value) {
                    $rule = (!($value instanceof UploadedFile)) ? "" : $image_mimes;
                    $logo_image_rules['logo_image.' . $index] = $rule;
                }
            }

            if (!empty($request->banner_image1)) {
                $banner_image1_rules = [];
                foreach ($request->banner_image1 as $index => $value) {
                    $rule = (!($value instanceof UploadedFile)) ? "" : $image_mimes;
                    $banner_image1_rules['banner_image1.' . $index] = $rule;
                }
            }

            if (!empty($request->banner_image2)) {
                $banner_image2_rules = [];
                foreach ($request->banner_image2 as $index => $value) {
                    $rule = (!($value instanceof UploadedFile)) ? "" : $image_mimes;
                    $banner_image2_rules['banner_image2.' . $index] = $rule;
                }
            }

            if (!empty($request->banner_image3)) {
                $banner_image3_rules = [];
                foreach ($request->banner_image3 as $index => $value) {
                    $rule = (!($value instanceof UploadedFile)) ? "" : $image_mimes;
                    $banner_image3_rules['banner_image3.' . $index] = $rule;
                }
            }

            if (!empty($request->about_image)) {
                $about_image_rules = [];
                foreach ($request->about_image as $index => $value) {
                    $rule = (!($value instanceof UploadedFile)) ? "" : $image_mimes;
                    $about_image_rules['about_image.' . $index] = $rule;
                }
            }

            if (!empty($request->feature_image1)) {
                $feature_image1_rules = [];
                foreach ($request->feature_image1 as $index => $value) {
                    $rule = (!($value instanceof UploadedFile)) ? "" : $image_mimes;
                    $feature_image1_rules['feature_image1.' . $index] = $rule;
                }
            }

            if (!empty($request->feature_image2)) {
                $feature_image2_rules = [];
                foreach ($request->feature_image2 as $index => $value) {
                    $rule = (!($value instanceof UploadedFile)) ? "" : $image_mimes;
                    $feature_image2_rules['feature_image2.' . $index] = $rule;
                }
            }
            if (!empty($request->feature_image3)) {
                $feature_image3_rules = [];
                foreach ($request->feature_image3 as $index => $value) {
                    $rule = (!($value instanceof UploadedFile)) ? "" : $image_mimes;
                    $feature_image3_rules['feature_image3.' . $index] = $rule;
                }
            }
        }

        return [
            'company_name' => $company_name,
            'site_url' => $site_url,
            'email' => $email,
            'phone_no' => $phone_no,
            'address' => ['required', 'max:400'],
        ] + $logo_image_rules + $banner_image1_rules + $banner_image2_rules + $banner_image3_rules + $about_image_rules
            + $feature_image1_rules + $feature_image2_rules + $feature_image3_rules;
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'company_name.required' => 'Company Name is required',
            'company_name.unique' => 'Enter Unique Company name',
            'site_url.required' => 'Site Url Name is required',
            'site_url.unique' => 'Enter Unique Site Url',
            'email.required' => 'Email is required',
            'email.unique' => 'Email is already registered',
            'email.max' => 'Email no longer than 255 characters',
            'phone_no.required' => 'Phone number is required',
            'phone_no.unique' => 'Phone number is already registered',
            'phone_no.max' => 'Phone number longer than 10 characters',
            'address.required' => 'Address is required',
            'address.max' => 'Address longer than 400 characters',
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
