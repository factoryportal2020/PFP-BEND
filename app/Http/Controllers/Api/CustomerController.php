<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Http\Requests\CustomerRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Customer;
use Illuminate\Http\UploadedFile;
use App\Services\FileService;

class CustomerController extends BaseController
{
    protected $fileservice;

    public function __construct()
    {
        $this->fileservice = new FileService();
    }

    public function create(CustomerRequest $request)
    // public function create(Request $request)
    {
        try {
            // print_r($request->profile_image);exit;
            // return $this->responseAPI(false, $request->file(), 200);

            // $user_id = Auth::user()->id;
            $user_id = 1;
            $domain_id = 1;

            $request->merge([
                'domain_id' => $domain_id,
                'admin_id' => $user_id,
                'created_by' => $user_id,
                'updated_by' => $user_id,
            ]);

            $data = $request->all();

            if ($request->username == "" || $request->password == "") {
                $data = array_filter($data, static function ($element) {
                    return $element !== "username" || $element !== "password";
                });
            }
            DB::beginTransaction();

            $customer = Customer::create($data);
            $customer->save();

            if (!empty($request->profile_image)) {
                $this->uploadImages($request->profile_image, $customer);
            }
            DB::commit();
            return $this->responseAPI(true, "Customer Data Saved Successfully", 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->responseAPI(false, $e->getMessage(), 200);
        }
    }


    public function update(CustomerRequest $request, $id)
    {
        try {
            // $user_id = Auth::user()->id;
            $user_id = 1;

            $request->merge([
                'updated_by' => $user_id,
            ]);

            $data = $request->all();

            DB::beginTransaction();

            $customer = Customer::findOrFail($id)->fill($data);
            $customer->save();

            $this->uploadImage($request->profile_image, $customer->id);

            DB::commit();
            return $this->responseAPI(true, "Customer Data Saved Successfully", 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->responseAPI(false, $e->getLine(), 200);
        }
    }


    public function uploadImages($profile_image, $customer)
    {
        if (!empty($profile_image)) {
            foreach ($profile_image as $key => $image) {
                if ($image instanceof UploadedFile) {
                    $fileUpload = $this->fileservice->upload($image, config('const.customer'), $customer->code);
                    $url = config('const.customer') . "/" . $fileUpload->getBaseName();
                    $img_name = $image->getClientOriginalName();

                    $data = [
                        'customer_id' => $customer->id,
                        'name' => $img_name,
                        'path' => $url,
                        'created_by' => $customer->created_by,
                        'updated_by' => $customer->updated_by,
                    ];
                    $customer->customerImages()->create($data);
                }
                // else{
                //     $image = json_decode($image);
                //     $data =[

                //     ]
                // }
            }
        }
    }
}
