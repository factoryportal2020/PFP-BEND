<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Http\Requests\WorkerRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Worker;
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
        $this->fileservice = new FileService();
        $this->usercontroller = new UserController();
    }


    public function list(Request $request)
    {
        try {
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
            // print_r($request->profile_image);exit;
            // return $this->responseAPI(false, $request->file(), 200);

            // $user_id = Auth::user()->id;
            $user_id = 1;
            $domain_id = 1;
            $role_id = 4;

            $request->merge([
                'domain_id' => $domain_id,
                'admin_id' => $user_id,
                'role_id' => $role_id,
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
                }
            }

            if (!empty($request->profile_image)) {
                $this->uploadImages($request->profile_image, $worker);
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

            $worker = Worker::findOrFail($id);

            $response['user'] = [];
            if ($worker->user_id != "" || $worker->user_id != NULL) {
                $user = $this->usercontroller->getUser($worker->user_id);
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

            $response['worker'] = $worker;
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

    public function update(WorkerRequest $request)
    {
        try {
            $user_id = 1;
            $domain_id = 1;
            $role_id = 4;

            $encrypt_id = $request->encrypt_id;
            if ($encrypt_id == null || $encrypt_id == '') {
                return $this->responseAPI(false, "Invaid Data", 200);
            }

            $id = encryptID($encrypt_id, 'd');
            $request->merge([
                'domain_id' => $domain_id,
                'admin_id' => $user_id,
                'role_id' => $role_id,
                'updated_by' => $user_id,
            ]);

            $data = $request->all();

            DB::beginTransaction();

            $worker = Worker::updateOrCreate(["id" => $id], $data);
            // $worker->save();

            $message = "Worker Datas Updated Successfully";

            // User Login Creation
            if ($request->isPasswordChange) {
                if ($request->username != "" && $request->password != "") {
                    $user = $this->usercontroller->createUser($request);
                    if ($user->status() == 200) {
                        $worker->update(['user_id' => $user->getData()->id]);
                        $message = "Worker Datas and Login Details Saved Successfully";
                    }
                }
            }

            //update user Login information
            if ($worker->user_id) {
                $request->merge([
                    'user_id' => $worker->user_id,
                ]);
                $user = $this->usercontroller->createUser($request);
                if ($user->status() == 200) {
                    $message = "Worker Datas and Login Details Saved Successfully";
                }
            }

            if (!empty($request->deleteImages)) {
                WorkerImage::whereIn('id', $request->deleteImages)->delete();
            }

            if (!empty($request->profile_image)) {
                $this->uploadImages($request->profile_image, $worker);
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


    public function uploadImages($profile_image, $worker)
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
                    $worker->workerImages()->create($data);
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
