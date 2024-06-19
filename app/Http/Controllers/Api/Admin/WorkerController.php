<?php

namespace App\Http\Controllers\Api\Admin;


use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Http\Requests\WorkerRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Worker;
use App\Models\User;
use App\Models\Role;
use App\Models\WorkerImage;
use Illuminate\Http\UploadedFile;
use App\Services\FileService;
use App\Http\Controllers\Api\UserController;
use App\Http\Requests\UserRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Storage;

class WorkerController extends BaseController
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

            $auth = Auth::user();

            $admin_id = $auth->admin_id;

            $search_word = $request->search_word;
            $city = $request->city;

            $specialist = $request->specialist;

            $limit = $request->itemPerPage;
            $offset = $request->offset;

            $totalCount = 0;


            $datas =
                Worker::select(
                    "workers.*",
                    DB::raw('worker_images.path as image_path'),
                    DB::raw('worker_images.name as image_name'),
                    DB::raw('users.username')
                )
                ->leftJoin('worker_images', 'worker_images.worker_id', '=', 'workers.id')
                ->leftJoin('users', 'users.id', '=', 'workers.user_id')
                ->when($search_word, function ($query, $search_word) {
                    $query->where(function ($whr_query) use ($search_word) {
                        $whr_query->where('first_name', 'like', '%' . $search_word . '%');
                        //   ->orWhere('votes', '>', 50);
                    });
                })
                ->when($city, function ($query, $city) {
                    $query->where("city", $city);
                })
                ->where('workers.admin_id', $admin_id)
                ->when($specialist, function ($query, $specialist) {
                    $query->where("specialist", $specialist);
                });


            $totalCount = $datas->count();

            $datas->limit($limit)->orderBy("workers.id", "DESC");

            if ($offset) {
                $datas->offset($offset);
            }
            $workers = $datas->get();

            if (!empty($workers)) {
                foreach ($workers as $key => $worker) {
                    $url = ($worker->image_path != "" || $worker->image_path != null) ? env('APP_URL') . Storage::url($worker->image_path) : "";
                    $worker['profile_image'] = [
                        'url' => $url,
                        'name' => $worker->image_name
                    ];
                    $worker->encrypt_id = encryptID($worker->id, 'e');
                    unset($worker->image_path);
                    unset($worker->image_name);
                }
            }

            // $response['profile_image']['profile_image'] = $images;

            $response['data'] = $workers;
            $response['totalCount'] = $totalCount;


            return $this->responseAPI(true, "Data get successfully", 200, $response);
        } catch (\Exception $e) {
            return $this->responseAPI(false, $e->getMessage(), 200);
        }
    }


    public function create(WorkerRequest $request)
    {
        try {

            $auth = Auth::user();
            $request->merge([
                'domain_id' => $auth->domain_id,
                'admin_id' => $auth->admin_id,
                'role_id' => Role::worker(),
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

            $data['code'] = Worker::getCode();

            $worker = Worker::create($data);
            $worker->save();

            $message = "Worker Datas Saved Successfully";

            // User Login Creation
            if ($request->username != "" && $request->password != "") {
                $user = $this->usercontroller->createUser($request);
                if ($user->status() == 200) {
                    $worker->update(['user_id' => $user->getData()->id]);
                    $message = "Worker Datas and Login Details Saved Successfully";
                    successLog("Worker", "Create", "User",  $user->getData()->id, $message);
                }
            }

            if (!empty($request->profile_image)) {
                $this->usercontroller->uploadWorkerImages($request->profile_image, $worker);
                successLog("Worker", "create-UploadImage", "WorkerImage",  $worker->id, $message);
            }

            DB::commit();
            successLog("Worker", "Create", "Worker",  $worker->id, $message);
            return $this->responseAPI(true, $message, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof HttpResponseException) {
                errorLog("Worker", "Create", "Worker",  null, $e->getResponse());
                return $e->getResponse();
            }
            errorLog("Worker", "Create", "Worker",  null, $e->getMessage());
            return $this->responseAPI(false, $e->getMessage(), 200);
        }
    }


    public function get($encrypt_id)
    {
        return $this->usercontroller->getWorker($encrypt_id);
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

    public function update(Request $request)
    {
        return $this->usercontroller->updateWorker($request);
    }

    public function delete($encrypt_id)
    {
        try {
            if ($encrypt_id == null || $encrypt_id == '') {
                return $this->responseAPI(false, "Invaid Data", 200);
            }
            $id = encryptID($encrypt_id, 'd');
            $worker = Worker::findOrFail($id);
            if ($worker->user_id && $worker->user_id != null) {
                $delete = User::findOrFail($worker->user_id)->delete();
            }
            $delete = $worker->delete();
            if ($delete) {
                $message = "Worker data deleted successfully";
                successLog("Worker", "Delete", "Worker",  $worker->id, $message);
                return $this->responseAPI(true, $message, 200);
            } else {
                $message = "Something went wrong";
                errorLog("Worker", "Delete", "Worker",  $worker->id, $message);
                return $this->responseAPI(false, $message, 200);
            }
        } catch (\Exception $e) {
            errorLog("Worker", "Delete", "Worker",  null, $e->getMessage());
            return $this->responseAPI(false, $e->getMessage(), 200);
        }
    }
}
