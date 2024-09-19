<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Worker;
use App\Models\Enquiry;
use App\Models\Admin;
use Illuminate\Support\Facades\Auth;
use App\Services\FileService;
use App\Http\Controllers\Api\UserController;
use App\Models\Favourite;
use Illuminate\Support\Facades\Storage;

class ProfileController extends BaseController
{
    protected $fileservice;
    protected $usercontroller;

    public function __construct()
    {
        // $this->middleware('role:profile');
        $this->fileservice = new FileService();
        $this->usercontroller = new UserController();
    }

    public function profile()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->responseAPI(false, "Profile get failed", 200, null);
            }
            $role = $user->role->name;


            $profile_encrypt_id = null;

            $images = [];
            if ($role == "customer") {
                $customer = Customer::where('user_id', $user->id)->first();
                $customer_id = $customer->id;
                $profile_encrypt_id = ($customer_id) ? encryptID($customer_id, 'e') : null;

                if (!empty($customer->customerImages)) {
                    foreach ($customer->customerImages as $key => $image) {
                        $images[] = ['url' => env('APP_URL') . Storage::url($image->path), 'name' => $image->name, 'id' => $image->id];
                    }
                }
            } elseif ($role == "worker") {
                $worker = Worker::where('user_id', $user->id)->first();
                $worker_id = $worker->id;
                $profile_encrypt_id = ($worker_id) ? encryptID($worker_id, 'e') : null;
                if (!empty($worker->workerImages)) {
                    foreach ($worker->workerImages as $key => $image) {
                        $images[] = ['url' => env('APP_URL') . Storage::url($image->path), 'name' => $image->name, 'id' => $image->id];
                    }
                }
            } elseif ($role == "admin") {
                $admin = Admin::where('user_id', $user->id)->first();
                $admin_id = $admin->id;
                $profile_encrypt_id = ($admin_id) ? encryptID($admin_id, 'e') : null;
                if (!empty($admin->adminImages)) {
                    foreach ($admin->adminImages as $key => $image) {
                        $images[] = ['url' => env('APP_URL') . Storage::url($image->path), 'name' => $image->name, 'id' => $image->id];
                    }
                }
            }

            $enquiries = Enquiry::where('user_id', $user->id)->get();
            $favourites = Favourite::where('user_id', $user->id)->get();

            $userInfo = [
                'role' => $user->role->name,
                'username' => $user->username,
                'email' => $user->email,
                'user_encrypt_id' => encryptID($user->id, 'e'),
                'profile_encrypt_id' => $profile_encrypt_id,
                'enquiry_count' => $enquiries->count(),
                'favourite_count' => $favourites->count(),
                'profile_image' => $images
            ];

            $message = "Profile get successfully";

            return $this->responseAPI(true, $message, 200, $userInfo);
        } catch (\Exception $e) {
            return $this->responseAPI(false, $e->getMessage(), 200);
        }
    }

    public function getAdmin($encrypt_id)
    {
        return $this->usercontroller->getAdmin($encrypt_id);
    }

    public function updateAdmin(Request $request)
    {
        return $this->usercontroller->updateAdmin($request);
    }

    public function getCustomer($encrypt_id)
    {
        return $this->usercontroller->getCustomer($encrypt_id);
    }

    public function updateCustomer(Request $request)
    {
        return $this->usercontroller->updateCustomer($request);
    }

    public function getWorker($encrypt_id)
    {
        return $this->usercontroller->getWorker($encrypt_id);
    }

    public function updateWorker(Request $request)
    {
        return $this->usercontroller->updateWorker($request);
    }
}
