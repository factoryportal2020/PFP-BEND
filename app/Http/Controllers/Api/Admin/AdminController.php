<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Http\Requests\AdminRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Admin;
use App\Models\User;
use App\Models\Role;
use App\Services\FileService;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Storage;

class AdminController extends BaseController
{
    protected $fileservice;
    protected $usercontroller;

    public function __construct()
    {
        $this->middleware('role:superadmin');
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
                Admin::select(
                    "admins.*",
                    DB::raw('admin_images.path as image_path'),
                    DB::raw('admin_images.name as image_name'),
                    DB::raw('users.username')
                )
                ->leftJoin('admin_images', 'admin_images.admin_id', '=', 'admins.id')
                ->leftJoin('users', 'users.id', '=', 'admins.user_id')
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

            $datas->limit($limit)->orderBy("admins.id", "DESC");

            if ($offset) {
                $datas->offset($offset);
            }
            $admins = $datas->get();

            if (!empty($admins)) {
                foreach ($admins as $key => $admin) {
                    $url = ($admin->image_path != "" || $admin->image_path != null) ? env('APP_URL') . Storage::url($admin->image_path) : "";
                    $admin['profile_image'] = [
                        'url' => $url,
                        'name' => $admin->image_name
                    ];
                    $admin->encrypt_id = encryptID($admin->id, 'e');
                    unset($admin->image_path);
                    unset($admin->image_name);
                }
            }

            // $response['profile_image']['profile_image'] = $images;

            $response['data'] = $admins;
            $response['totalCount'] = $totalCount;


            return $this->responseAPI(true, "Data get successfully", 200, $response);
        } catch (\Exception $e) {
            return $this->responseAPI(false, $e->getMessage(), 200);
        }
    }


    public function create(AdminRequest $request)
    {
        try {

            $auth = Auth::user();
            $request->merge([
                'domain_id' => $auth->domain_id,
                'role_id' => Role::admin(),
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
            $data['code'] = Admin::getCode();
            $admin = Admin::create($data);
            // $admin->code = $admin->getCode();
            $admin->save();

            $message = "Admin Datas Saved Successfully";

            // User Login Creation
            if ($request->username != "" && $request->password != "") {
                $user = $this->usercontroller->createUser($request);
                if ($user->status() == 200) {
                    $admin->update(['user_id' => $user->getData()->id]);
                    $message = "Admin Datas and Login Details Saved Successfully";
                }
            }

            if (!empty($request->profile_image)) {
                $this->usercontroller->uploadAdminImages($request->profile_image, $admin);
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
        return $this->usercontroller->getAdmin($encrypt_id);
    }

    public function update(Request $request)
    {
        return $this->usercontroller->updateAdmin($request);
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
            $admin = Admin::findOrFail($id);
            if ($admin->user_id && $admin->user_id != null) {
                $delete = User::findOrFail($admin->user_id)->delete();
            }
            $delete = $admin->delete();
            if ($delete) {
                $message = "Admin data deleted successfully";
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
