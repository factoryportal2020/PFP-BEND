<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Http\Requests\CustomerRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Customer;
use App\Models\CustomerImage;
use Illuminate\Http\UploadedFile;
use App\Services\FileService;
use App\Http\Controllers\Api\UserController;
use App\Http\Requests\UserRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Storage;


class CustomerController extends BaseController
{
    protected $fileservice;
    protected $usercontroller;

    public function __construct()
    {
        $this->fileservice = new FileService();
        $this->usercontroller = new UserController();
    }


    public function list(Request $request)
    {
        try {
            $search_word = $request->search_word;
            $city = $request->city;

            $limit = $request->itemPerPage;
            $offset = $request->offset;

            $totalCount = 0;

            $datas =
                Customer::select(
                    "customers.*",
                    DB::raw('customer_images.path as image_path'),
                    DB::raw('customer_images.name as image_name'),
                    DB::raw('users.username')
                )
                ->leftJoin('customer_images', 'customer_images.customer_id', '=', 'customers.id')
                ->leftJoin('users', 'users.id', '=', 'customers.user_id')
                ->when($search_word, function ($query, $search_word) {
                    $query->where(function ($whr_query) use ($search_word) {
                        $whr_query->where('first_name', 'like', '%' . $search_word . '%');
                        //   ->orWhere('votes', '>', 50);
                    });
                })
                ->when($city, function ($query, $city) {
                    $query->where("city", $city);
                });


            $totalCount = $datas->count();
            $customers = $datas->limit($limit)
                ->orderBy("customers.id", "DESC")
                ->offset($offset)
                ->get();

            if (!empty($customers)) {
                foreach ($customers as $key => $customer) {
                    $url = ($customer->image_path != "" || $customer->image_path != null) ? env('APP_URL') . Storage::url($customer->image_path) : "";
                    $customer['profile_image'] = [
                        'url' => $url,
                        'name' => $customer->image_name
                    ];
                    $customer->encrypt_id = encryptID($customer->id, 'e');
                    unset($customer->image_path);
                    unset($customer->image_name);
                }
            }

            // $response['profile_image']['profile_image'] = $images;

            $response['data'] = $customers;
            $response['totalCount'] = $totalCount;


            return $this->responseAPI(true, "Data get successfully", 200, $response);
        } catch (\Exception $e) {
            return $this->responseAPI(false, $e->getMessage(), 200);
        }
    }


    public function create(CustomerRequest $request)
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
                'role_id' => $user_id,
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

            $message = "Customer Datas Saved Successfully";

            // User Login Creation
            if ($request->username != "" && $request->password != "") {
                $user = $this->usercontroller->createUser($request);
                if ($user->status() == 200) {
                    $customer->update(['user_id' => $user->getData()->id]);
                    $message = "Customer Datas and Login Details Saved Successfully";
                }
            }

            if (!empty($request->profile_image)) {
                $this->uploadImages($request->profile_image, $customer);
            }

            DB::commit();
            return $this->responseAPI(true, $message, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof HttpResponseException) {
                return $e->getResponse();
            }
            return $this->responseAPI(false, $e->getMessage(), 200);
        }
    }


    public function get($encrypt_id)
    {
        try {
            if ($encrypt_id == null || $encrypt_id == '') {
                return $this->responseAPI(false, "Invaid Data", 200);
            }
            $id = encryptID($encrypt_id, 'd');

            $response = [];

            $response['customer'] = $customer = Customer::findOrFail($id);

            $response['user'] = [];
            if ($customer->user_id != "" || $customer->user_id != NULL) {
                $user = $this->usercontroller->getUser($customer->user_id);
                if ($user->status() == 200) {
                    $user_data = $user->getData();
                    $response['user'] = ['username' => $user_data->username];
                }
            }

            $images = [];
            if (!empty($customer->customerImages)) {
                foreach ($customer->customerImages as $key => $image) {
                    $images[] = ['url' => env('APP_URL') . Storage::url($image->path), 'name' => $image->name, 'id' => $image->id];
                }
            }
            $response['profile_image']['profile_image'] = $images;

            return $this->responseAPI(true, "Data get successfully", 200, $response);
        } catch (\Exception $e) {
            return $this->responseAPI(false, $e->getMessage(), 200);
        }
    }


    public function getEncryptID($id)
    {
        try {
            if ($id == null || $id == '') {
                return $this->responseAPI(false, "Invaid Data", 200);
            }
            $encrypt_id = encryptID($id);
            return $this->responseAPI(true, $encrypt_id, 200);
        } catch (\Exception $e) {
            return $this->responseAPI(false, $e->getMessage(), 200);
        }
    }

    public function update(CustomerRequest $request)
    {
        try {
            $user_id = 1;
            $domain_id = 1;
            $encrypt_id = $request->encrypt_id;
            if ($encrypt_id == null || $encrypt_id == '') {
                return $this->responseAPI(false, "Invaid Data", 200);
            }

            $id = encryptID($encrypt_id, 'd');
            $request->merge([
                'domain_id' => $domain_id,
                'admin_id' => $user_id,
                'role_id' => $user_id,
                'updated_by' => $user_id,
            ]);

            $data = $request->all();

            DB::beginTransaction();

            $customer = Customer::updateOrCreate(["id" => $id], $data);
            // $customer->save();

            $message = "Customer Datas Updated Successfully";

            // User Login Creation
            if ($request->isPasswordChange) {
                if ($request->username != "" && $request->password != "") {
                    $user = $this->usercontroller->createUser($request);
                    if ($user->status() == 200) {
                        $customer->update(['user_id' => $user->getData()->id]);
                        $message = "Customer Datas and Login Details Saved Successfully";
                    }
                }
            }

            if (!empty($request->deleteImages)) {
                CustomerImage::whereIn('id', $request->deleteImages)->delete();
            }

            if (!empty($request->profile_image)) {
                $this->uploadImages($request->profile_image, $customer);
            }

            DB::commit();
            return $this->responseAPI(true, $message, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof HttpResponseException) {
                return $e->getResponse();
            }
            return $this->responseAPI(false, $e->getMessage(), 200);
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
