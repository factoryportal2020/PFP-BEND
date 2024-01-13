<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Http\Requests\CustomerRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Customer;
use App\Models\User;
use App\Models\Role;
use App\Models\CustomerImage;
use Illuminate\Http\UploadedFile;
use App\Services\FileService;
use App\Http\Controllers\Api\UserController;
use App\Http\Requests\UserRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;


class CustomerController extends BaseController
{
    protected $fileservice;
    protected $usercontroller;

    public function __construct()
    {
        $this->middleware('role:admin');
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

            $datas->limit($limit)->orderBy("customers.id", "DESC");

            if ($offset) {
                $datas->offset($offset);
            }
            $customers = $datas->get();

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

            $auth = Auth::user();
            $request->merge([
                'domain_id' => $auth->domain_id,
                'admin_id' => $auth->id,
                'role_id' => Role::customer(),
                'created_by' => $auth->id,
                'updated_by' => $auth->id,
            ]);

            $data = $request->all();

            if ($request->username == "" || $request->password == "") {
                $data = array_filter($data, static function ($element) {
                    return $element !== "username" || $element !== "password";
                });
            }
            DB::beginTransaction();
            $data['code'] = Customer::getCode();
            $customer = Customer::create($data);
            // $customer->code = $customer->getCode();
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
                $this->usercontroller->uploadCustomerImages($request->profile_image, $customer);
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
        return $this->usercontroller->getCustomer($encrypt_id);
    }

    public function update(Request $request)
    {
        return $this->usercontroller->updateCustomer($request);
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

    public function delete($encrypt_id)
    {
        try {
            if ($encrypt_id == null || $encrypt_id == '') {
                return $this->responseAPI(false, "Invaid Data", 200);
            }
            $id = encryptID($encrypt_id, 'd');
            $customer = Customer::findOrFail($id);
            if ($customer->user_id && $customer->user_id != null) {
                $delete = User::findOrFail($customer->user_id)->delete();
            }
            $delete = $customer->delete();
            if ($delete) {
                $message = "Customer data deleted successfully";
                return $this->responseAPI(true, $message, 200);
            } else {
                $message = "Something went wrong";
                return $this->responseAPI(false, $message, 200);
            }
        } catch (\Exception $e) {
            return $this->responseAPI(false, $e->getMessage(), 200);
        }
    }

    
}
