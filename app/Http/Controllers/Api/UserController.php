<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Http\Requests\UserRequest;
use App\Models\Customer;
use App\Models\Worker;
use App\Models\Role;
use App\Models\Admin;
use App\Models\WorkerImage;
use App\Models\CustomerImage;
use App\Models\AdminImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Services\FileService;
use App\Http\Requests\WorkerRequest;
use App\Http\Requests\CustomerRequest;
use App\Http\Requests\AdminRequest;
use App\Models\Enquiry;
use App\Models\Favourite;
use App\Models\Subscribe;

class UserController extends BaseController
{
    protected $fileservice;
    protected $usercontroller;

    public function __construct()
    {
        $this->fileservice = new FileService();
    }

    //
    public function createUser(Request $request, $update = false)
    {
        // try {
        $UserRequest = new UserRequest();
        $validator = Validator::make($request->all(), $UserRequest->rules($request), $UserRequest->messages());
        if ($validator->fails()) {
            $UserRequest->failedValidation($validator);
        }
        $data = [
            "name" => $request->first_name . " " . $request->last_name,
            "email" => $request->email,
            "username" => $request->username,
            "password" => $request->password,
            "phone_no" => $request->phone_no,
            "role_id" => $request->role_id,
            "status" => ($request->status) ? $request->status : 1,
            "domain_id" => $request->domain_id
        ];
        if ($request->user_id != null && $request->user_id != "") {
            if ($update) {
                unset($data['password']);
            }
            $user = User::updateOrCreate(["id" => $request->user_id], $data);
        } else {
            $user = User::create($data);
        }

        return response()->json($user, 200);
    }

    public function getUser($id)
    {
        $user = User::findOrFail($id);
        return response()->json($user, 200);
    }

    public function getAdmin($encrypt_id)
    {
        try {
            if ($encrypt_id == null || $encrypt_id == '') {
                return $this->responseAPI(false, "Invaid Data", 200);
            }
            $id = encryptID($encrypt_id, 'd');

            $response = [];

            $admin = Admin::findOrFail($id);

            $response['user'] = [];
            if ($admin->user_id != "" || $admin->user_id != NULL) {
                $user = $this->getUser($admin->user_id);
                if ($user->status() == 200) {
                    $user_data = $user->getData();
                    $response['user'] = ['username' => $user_data->username];
                }
            }

            $images = [];
            if (!empty($admin->adminImages)) {
                foreach ($admin->adminImages as $key => $image) {
                    $images[] = ['url' => env('APP_URL') . Storage::url($image->path), 'name' => $image->name, 'id' => $image->id];
                }
            }

            unset($admin->adminImages);

            $response['admin'] = $admin;
            $response['profile_image']['profile_image'] = $images;

            return $this->responseAPI(true, "Data get successfully", 200, $response);
        } catch (\Exception $e) {
            return $this->responseAPI(false, $e->getMessage(), 200);
        }
    }

    public function getCustomer($encrypt_id)
    {
        try {
            if ($encrypt_id == null || $encrypt_id == '') {
                return $this->responseAPI(false, "Invaid Data", 200);
            }
            $id = encryptID($encrypt_id, 'd');

            $response = [];

            $customer = Customer::findOrFail($id);

            $response['user'] = [];
            if ($customer->user_id != "" || $customer->user_id != NULL) {
                $user = $this->getUser($customer->user_id);
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

            unset($customer->customerImages);

            $response['customer'] = $customer;
            $response['profile_image']['profile_image'] = $images;

            $enquiries = Enquiry::where('user_id', $customer->user_id)->get();
            $favourites = Favourite::where('user_id', $customer->user_id)->get();
            $subscribe = Subscribe::where('email', $user_data->email)->first();
            $subscribed_email = "";
            if ($subscribe) {
                $subscribed_email = $subscribe->email;
            }


            $response['enquiries'] = ['enquiry_count' => $enquiries->count()];
            $response['favourites'] = ['favourite_count' => $favourites->count()];
            $response['subscribe'] = ['subscribed_email' => $subscribed_email];


            return $this->responseAPI(true, "Data get successfully", 200, $response);
        } catch (\Exception $e) {
            return $this->responseAPI(false, $e->getMessage(), 200);
        }
    }


    public function getWorker($encrypt_id)
    {
        try {
            if ($encrypt_id == null || $encrypt_id == '') {
                return $this->responseAPI(false, "Invaid Data", 200);
            }
            $id = encryptID($encrypt_id, 'd');

            $response = [];

            $worker = Worker::findOrFail($id);

            $response['user'] = [];
            if ($worker->user_id != "" || $worker->user_id != NULL) {
                $user = $this->getUser($worker->user_id);
                if ($user->status() == 200) {
                    $user_data = $user->getData();
                    $response['user'] = ['username' => $user_data->username];
                }
            }

            $images = [];
            if (!empty($worker->workerImages)) {
                foreach ($worker->workerImages as $key => $image) {
                    $images[] = ['url' => env('APP_URL') . Storage::url($image->path), 'name' => $image->name, 'id' => $image->id];
                }
            }

            unset($worker->workerImages);
            $worker->tasks_count = $worker->all_tasks_count;
            $response['worker'] = $worker;
            $response['profile_image']['profile_image'] = $images;

            return $this->responseAPI(true, "Data get successfully", 200, $response);
        } catch (\Exception $e) {
            return $this->responseAPI(false, $e->getMessage(), 200);
        }
    }

    public function updateAdmin($request)
    {
        try {

            $AdminRequest = new AdminRequest();
            $validator = Validator::make($request->all(), $AdminRequest->rules($request), $AdminRequest->messages());
            if ($validator->fails()) {
                successLog("Admin", "update-validation", "Admin",  null, $validator->errors()->first());
                $AdminRequest->failedValidation($validator);
            }

            $encrypt_id = $request->encrypt_id;

            if ($encrypt_id == null || $encrypt_id == '') {
                return $this->responseAPI(false, "Invaid Data", 200);
            }

            $auth = Auth::user();
            $request->merge([
                'domain_id' => $auth->domain_id,
                'role_id' => Role::admin(),
                'created_by' => $auth->id,
                'updated_by' => $auth->id,
            ]);

            $id = encryptID($encrypt_id, 'd');


            $data = $request->all();

            DB::beginTransaction();

            $admin = Admin::updateOrCreate(["id" => $id], $data);
            // $admin->save();

            $message = "Admin Datas Updated Successfully";


            // User Login Creation
            if ($request->isPasswordChange) {
                if ($request->username != "" && $request->password != "") {
                    $user = $this->createUser($request);
                    if ($user->status() == 200) {
                        $admin->update(['user_id' => $user->getData()->id]);
                        $message = "Admin Datas and Login Details Updated Successfully";
                        successLog("Admin", "update-user-password-change", "User",  $user->getData()->id, $message);
                    }
                }
            }

            //update user Login information
            if ($admin->user_id && $admin->user_id != NULL && ($request->isPasswordChange == "false")) {
                $request->merge([
                    'user_id' => (int)$admin->user_id,
                    'updated_by' => $auth->id
                ]);
                $user = $this->createUser($request, true);
                if ($user->status() == 200) {
                    $message = "Admin Datas and Login Details Saved Successfully";
                    successLog("Admin", "update-user-login", "User",  $user->getData()->id, $message);
                }
            }

            if (!empty($request->deleteImages)) {
                $images = AdminImage::whereIn('id', $request->deleteImages)->get();
                AdminImage::whereIn('id', $request->deleteImages)->delete();
                $this->fileservice->remove_file_attachment($images, config('const.admin'));
                successLog("Admin", "update-image-delete", "AdminImage",  implode("~", $request->deleteImages), "Admin image deleted");
            }

            if (!empty($request->profile_image)) {
                $this->uploadAdminImages($request->profile_image, $admin);
                successLog("Admin", "update-image-upload", "AdminImage",  $admin->id, "Admin image uploaded");
            }

            if (!empty($admin->adminImages)) {
                foreach ($admin->adminImages as $key => $image) {
                    $images[] = ['url' => env('APP_URL') . Storage::url($image->path), 'name' => $image->name, 'id' => $image->id];
                }
            }
            $response = ['profile_image' => $images];


            DB::commit();
            successLog("Admin", "update", "Admin",  $admin->id, $message);
            return $this->responseAPI(true, $message, 200, $response);
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof HttpResponseException) {
                errorLog("Admin", "Update", "Admin",  null, $e->getResponse());
                return $e->getResponse();
            }
            errorLog("Admin", "Update", "Admin",  null, $e->getMessage());
            return $this->responseAPI(false, $e->getMessage(), 200);
        }
    }

    public function updateCustomer($request)
    {
        try {

            $CustomerRequest = new CustomerRequest();
            $validator = Validator::make($request->all(), $CustomerRequest->rules($request), $CustomerRequest->messages());
            if ($validator->fails()) {
                $CustomerRequest->failedValidation($validator);
                successLog("Customer", "update-validation", "Customer",  null, $validator->errors()->first());
            }

            $encrypt_id = $request->encrypt_id;

            if ($encrypt_id == null || $encrypt_id == '') {
                return $this->responseAPI(false, "Invaid Data", 200);
            }
            $auth = Auth::user();

            $admin_id = ($request->header('Admin-EncryptId') != null || $request->header('Admin-EncryptId')) ? encryptID($request->header('Admin-EncryptId'), 'd') : $auth->admin_id;

            $request->merge([
                'domain_id' => $auth->domain_id,
                'admin_id' => $admin_id,
                'role_id' => Role::customer(),
                'created_by' => $auth->id,
                'updated_by' => $auth->id,
            ]);

            $id = encryptID($encrypt_id, 'd');


            $data = $request->all();

            DB::beginTransaction();

            $customer = Customer::updateOrCreate(["id" => $id], $data);
            // $customer->save();
            // return $this->responseAPI(true, $customer->user_id, 200);

            $message = "Customer Datas Updated Successfully";

            // User Login Creation
            if ($request->isPasswordChange) {
                if ($request->username != "" && $request->password != "") {
                    $user = $this->createUser($request);
                    if ($user->status() == 200) {
                        $customer->update(['user_id' => $user->getData()->id]);
                        $message = "Customer Datas and Login Details Updated Successfully";
                        successLog("Customer", "update-user-password-change", "User",  $user->getData()->id, $message);
                    }
                }
            }

            //update user Login information
            if ($customer->user_id && $customer->user_id != NULL && ($request->isPasswordChange == "false")) {
                $request->merge([
                    'user_id' => (int)$customer->user_id,
                    'updated_by' => $auth->id
                ]);
                $user = $this->createUser($request, true);
                if ($user->status() == 200) {
                    $message = "Customer Datas and Login Details Saved Successfully";
                    successLog("Customer", "update-user-login", "User",  $user->getData()->id, $message);
                }
            }

            if (!empty($request->deleteImages)) {
                $images = CustomerImage::whereIn('id', $request->deleteImages)->get();
                CustomerImage::whereIn('id', $request->deleteImages)->delete();
                $this->fileservice->remove_file_attachment($images, config('const.customer'));
                successLog("Customer", "update-image-delete", "CustomerImage",  implode("~", $request->deleteImages), "Customer image deleted");
            }

            if (!empty($request->profile_image)) {
                $this->uploadCustomerImages($request->profile_image, $customer);
                successLog("Customer", "update-image-upload", "CustomerImage",  $customer->id, "Customer image uploaded");
            }

            if (!empty($customer->customerImages)) {
                foreach ($customer->customerImages as $key => $image) {
                    $images[] = ['url' => env('APP_URL') . Storage::url($image->path), 'name' => $image->name, 'id' => $image->id];
                }
            }
            $response = ['profile_image' => $images];

            DB::commit();
            successLog("Customer", "update", "Customer",  $customer->id, $message);
            return $this->responseAPI(true, $message, 200, $response);
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof HttpResponseException) {
                errorLog("Customer", "Update", "Customer",  null, $e->getResponse());
                return $e->getResponse();
            }
            errorLog("Customer", "Update", "Customer",  null, $e->getMessage());
            return $this->responseAPI(false, $e->getMessage(), 200);
        }
    }

    public function updateWorker($request)
    {
        try {

            $WorkerRequest = new WorkerRequest();
            $validator = Validator::make($request->all(), $WorkerRequest->rules($request), $WorkerRequest->messages());
            if ($validator->fails()) {
                $WorkerRequest->failedValidation($validator);
                successLog("Worker", "update-validation", "Worker",  null, $validator->errors()->first());
            }

            $encrypt_id = $request->encrypt_id;
            if ($encrypt_id == null || $encrypt_id == '') {
                return $this->responseAPI(false, "Invaid Data", 200);
            }

            $id = encryptID($encrypt_id, 'd');
            $auth = Auth::user();
            $request->merge([
                'domain_id' => $auth->domain_id,
                'admin_id' => $auth->admin_id,
                'role_id' => Role::worker(),
                'updated_by' => $auth->id,
            ]);

            $data = $request->all();

            DB::beginTransaction();

            $worker = Worker::updateOrCreate(["id" => $id], $data);
            // $worker->save();

            $message = "Worker Datas Updated Successfully";

            // User Login Creation
            if ($request->isPasswordChange) {
                if ($request->username != "" && $request->password != "") {
                    $user = $this->createUser($request);
                    if ($user->status() == 200) {
                        $worker->update(['user_id' => $user->getData()->id]);
                        $message = "Worker Datas and Login Details Updated Successfully";
                        successLog("Worker", "update-user-password-change", "Worker",  $user->getData()->id, $message);
                    }
                }
            }

            // update user Login information
            if ($worker->user_id && ($request->isPasswordChange == "false")) {
                $request->merge([
                    'user_id' => $worker->user_id,
                    'updated_by' => $auth->id
                ]);
                $user = $this->createUser($request, true); //update add true 
                if ($user->status() == 200) {
                    $message = "Worker Datas and Login Details Saved Successfully";
                    successLog("Worker", "update-user-login", "User",  $user->getData()->id, $message);
                }
            }

            if (!empty($request->deleteImages)) {
                $images = WorkerImage::whereIn('id', $request->deleteImages)->get();
                WorkerImage::whereIn('id', $request->deleteImages)->delete();
                $this->fileservice->remove_file_attachment($images, config('const.worker'));
                successLog("Worker", "update-image-delete", "WorkerImage",  implode("~", $request->deleteImages), "Worker image deleted");
            }

            if (!empty($request->profile_image)) {
                $this->uploadWorkerImages($request->profile_image, $worker);
                successLog("Worker", "update-image-upload", "WorkerImage",  $worker->id, "Worker image uploaded");
            }

            if (!empty($worker->workerImages)) {
                foreach ($worker->workerImages as $key => $image) {
                    $images[] = ['url' => env('APP_URL') . Storage::url($image->path), 'name' => $image->name, 'id' => $image->id];
                }
            }
            $response = ['profile_image' => $images];

            DB::commit();
            successLog("Worker", "update", "Worker",  $worker->id, $message);
            return $this->responseAPI(true, $message, 200, $response);
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof HttpResponseException) {
                errorLog("Worker", "Update", "Worker",  null, $e->getResponse());
                return $e->getResponse();
            }
            errorLog("Worker", "Update", "Worker",  null, $e->getMessage());
            return $this->responseAPI(false, $e->getMessage(), 200);
        }
    }

    public function uploadAdminImages($profile_image, $admin)
    {
        if (!empty($profile_image)) {
            foreach ($profile_image as $key => $image) {
                if ($image instanceof UploadedFile) {
                    AdminImage::whereIn('admin_id', [$admin->id])->delete();
                    $fileUpload = $this->fileservice->upload($image, config('const.admin'), $admin->code);
                    $url = config('const.admin') . "/" . $fileUpload->getBaseName();
                    $img_name = $image->getClientOriginalName();

                    $data = [
                        'admin_id' => $admin->id,
                        'name' => $img_name,
                        'path' => $url,
                        'created_by' => $admin->created_by,
                        'updated_by' => $admin->updated_by,
                    ];
                    $admin_image = $admin->adminImages()->create($data);
                    $size = $admin_image->getFileSize();
                    AdminImage::where('id', $admin_image->id)->update(['size' => $size]);
                }
            }
        }
    }

    public function uploadWorkerImages($profile_image, $worker)
    {
        if (!empty($profile_image)) {
            foreach ($profile_image as $key => $image) {
                if ($image instanceof UploadedFile) {
                    WorkerImage::whereIn('worker_id', [$worker->id])->delete();
                    $fileUpload = $this->fileservice->upload($image, config('const.worker'), $worker->code);
                    $url = config('const.worker') . "/" . $fileUpload->getBaseName();
                    $img_name = $image->getClientOriginalName();

                    $data = [
                        'worker_id' => $worker->id,
                        'name' => $img_name,
                        'path' => $url,
                        'created_by' => $worker->created_by,
                        'updated_by' => $worker->updated_by,
                    ];
                    $worker_image = $worker->workerImages()->create($data);
                    $size = $worker_image->getFileSize();
                    WorkerImage::where('id', $worker_image->id)->update(['size' => $size]);
                }
            }
        }
    }

    public function uploadCustomerImages($profile_image, $customer)
    {
        if (!empty($profile_image)) {
            foreach ($profile_image as $key => $image) {
                if ($image instanceof UploadedFile) {
                    CustomerImage::whereIn('customer_id', [$customer->id])->delete();
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
                    $customer_image = $customer->customerImages()->create($data);
                    $size = $customer_image->getFileSize();
                    CustomerImage::where('id', $customer_image->id)->update(['size' => $size]);
                }
            }
        }
    }
}
