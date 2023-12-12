<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Http\Requests\TaskRequest;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use App\Models\Task;
use App\Models\TaskBreakdown;
use App\Models\TaskImage;
use App\Models\TaskSpecification;
use App\Models\TaskHistory;
use Illuminate\Http\UploadedFile;
use App\Services\FileService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Storage;

class TaskController extends BaseController
{
    //
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
            $category_id = $request->category_id;
            $customer_id = $request->customer_id;
            $worker_id = $request->worker_id;
            $status = $request->status;

            $limit = $request->taskPerPage;
            $offset = $request->offset;

            $start_date = (!empty($request->start_date)) ? date("Y-m-d H:i:s", strtotime($request->start_date)) : "";
            $end_date = (!empty($request->end_date)) ? date("Y-m-d H:i:s", strtotime($request->end_date)) : "";

            $totalCount = 0;

            $datas =
                Task::select(
                    "tasks.*",
                    DB::raw('categories.name as category_name'),
                    DB::raw('workers.first_name as worker_name'),
                    DB::raw('customers.first_name as customer_name'),

                    DB::raw('workers.specialist as worker_specialist'),

                    DB::raw('workers.phone_no as worker_phone_no'),
                    DB::raw('customers.phone_no as customer_phone_no'),

                    DB::raw('task_images.path as image_path'),
                    DB::raw('task_images.name as image_name'),

                    DB::raw('worker_images.path as worker_image_path'),
                    DB::raw('worker_images.name as worker_image_name'),

                    DB::raw('customer_images.path as customer_image_path'),
                    DB::raw('customer_images.name as customer_image_name')
                )
                ->join('categories', 'categories.id', '=', 'tasks.category_id')
                ->join('workers', 'workers.id', '=', 'tasks.worker_id')
                ->leftJoin('task_images', function ($join) {
                    $join->on('task_images.task_id', '=', 'tasks.id')
                        ->where('task_images.type', '=', "main");
                })
                ->leftJoin('worker_images', function ($join) {
                    $join->on('worker_images.worker_id', '=', 'workers.id');
                })
                ->leftJoin('customers', 'customers.id', '=', 'tasks.customer_id')
                ->leftJoin('customer_images', function ($join) {
                    $join->on('customer_images.customer_id', '=', 'customers.id');
                })
                ->when($search_word, function ($query, $search_word) {
                    $query->where(function ($whr_query) use ($search_word) {
                        $whr_query->where('name', 'like', '%' . $search_word . '%');
                    });
                })
                ->when($category_id, function ($query, $category_id) {
                    $query->where("categories.id", $category_id);
                })
                ->when($customer_id, function ($query, $customer_id) {
                    $query->where("customers.id", $customer_id);
                })
                ->when($status, function ($query, $status) {
                    $query->where("tasks.status", $status);
                })
                ->when($worker_id, function ($query, $worker_id) {
                    $query->where("workers.id", $worker_id);
                })
                ->when($start_date, function ($query, $start_date) {
                    $query->whereDate("tasks.start_date", ">=", $start_date);
                })
                ->when($end_date, function ($query, $end_date) {
                    $query->whereDate("tasks.end_date", "<=", $end_date);
                });


            $totalCount = $datas->count();

            $datas->limit($limit)->orderBy("tasks.updated_at", "DESC");

            if ($offset) {
                $datas->offset($offset);
            }
            // $datas->groupBy("task_images.task_id");
            $tasks = $datas->get();

            if (!empty($tasks)) {
                foreach ($tasks as $key => $task) {
                    $url = ($task->image_path != "" || $task->image_path != null) ? env('APP_URL') . Storage::url($task->image_path) : "";
                    $task['task_image'] = [
                        'url' => $url,
                        'name' => $task->image_name
                    ];

                    $worker_image_url = ($task->worker_image_path != "" || $task->worker_image_path != null) ? env('APP_URL') . Storage::url($task->worker_image_path) : "";
                    $task['worker_image'] = [
                        'url' => $worker_image_url,
                        'name' => $task->worker_image_name
                    ];

                    $customer_image_url = ($task->customer_image_path != "" || $task->customer_image_path != null) ? env('APP_URL') . Storage::url($task->customer_image_path) : "";
                    $task['customer_image'] = [
                        'url' => $customer_image_url,
                        'name' => $task->customer_image_name
                    ];


                    $task->encrypt_id = encryptID($task->id, 'e');
                    unset($task->image_path);
                    unset($task->image_name);

                    unset($task->worker_image_path);
                    unset($task->worker_image_name);

                    unset($task->customer_image_path);
                    unset($task->customer_image_name);
                }
            }

            $response['data'] = $tasks;
            $response['totalCount'] = $totalCount;


            return $this->responseAPI(true, "Data get successfully", 200, $response);
        } catch (\Exception $e) {
            return $this->responseAPI(false, $e->getMessage(), 200);
        }
    }


    public function create(TaskRequest $request)
    {
        try {

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


            DB::beginTransaction();

            $data['code'] = Task::getCode();

            $task = Task::create($data);
            $task->save();

            $message = "Task Datas Saved Successfully";


            if (!empty($request->task_image)) {
                $this->uploadImages($request->task_image, $task);
            }

            if (!empty($request->initial_image)) {
                $this->uploadImages($request->initial_image, $task, "initial");
            }

            if (!empty($request->working_image)) {
                $this->uploadImages($request->working_image, $task, "working");
            }

            if (!empty($request->completed_image)) {
                $this->uploadImages($request->completed_image, $task, "completed");
            }

            if (!empty($request->delivered_image)) {
                $this->uploadImages($request->delivered_image, $task, "delivered");
            }

            $this->updateSpecifications($request->other_specifications, $request->delete_specifications_ids, $task->id);

            $this->updatePricebreakdowns($request->price_breakdowns, $request->delete_pricebreakdowns_ids, $task->id);


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

            $task = Task::findOrFail($id);

            $mainImages = [];
            if (!empty($task->mainImages)) {
                foreach ($task->mainImages as $key => $image) {
                    $mainImages[] = ['url' => env('APP_URL') . Storage::url($image->path), 'name' => $image->name, 'id' => $image->id];
                }
            }

            $initialImages = [];
            if (!empty($task->initialImages)) {
                foreach ($task->initialImages as $key => $image) {
                    $initialImages[] = ['url' => env('APP_URL') . Storage::url($image->path), 'name' => $image->name, 'id' => $image->id];
                }
            }

            $workingImages = [];
            if (!empty($task->workingImages)) {
                foreach ($task->workingImages as $key => $image) {
                    $workingImages[] = ['url' => env('APP_URL') . Storage::url($image->path), 'name' => $image->name, 'id' => $image->id];
                }
            }

            $completedImages = [];
            if (!empty($task->completedImages)) {
                foreach ($task->completedImages as $key => $image) {
                    $completedImages[] = ['url' => env('APP_URL') . Storage::url($image->path), 'name' => $image->name, 'id' => $image->id];
                }
            }

            $deliveredImages = [];
            if (!empty($task->deliveredImages)) {
                foreach ($task->deliveredImages as $key => $image) {
                    $deliveredImages[] = ['url' => env('APP_URL') . Storage::url($image->path), 'name' => $image->name, 'id' => $image->id];
                }
            }

            unset($task->taskImages);
            unset($task->initialImages);
            unset($task->workingImages);
            unset($task->completedImages);
            unset($task->deliveredImages);

            $response['task'] = $task;
            $response['other_specifications'] = $task->taskSpecifications;
            $response['price_breakdowns'] = $task->taskBreakdowns;
            $response['task_image']['task_image'] = $mainImages;
            $response['initial_image']['initial_image'] = $initialImages;
            $response['working_image']['working_image'] = $workingImages;
            $response['completed_image']['completed_image'] = $completedImages;
            $response['delivered_image']['delivered_image'] = $deliveredImages;

            unset($task->taskSpecifications);
            unset($task->taskBreakdowns);


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

    public function update(TaskRequest $request)
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
                'updated_by' => $user_id,
            ]);

            $data = $request->all();

            DB::beginTransaction();

            $task = Task::updateOrCreate(["id" => $id], $data);
            // $task->save();

            $message = "Task Datas Updated Successfully";

            if (!empty($request->deleteImages)) {
                TaskImage::whereIn('id', $request->deleteImages)->delete();
            }

            if (!empty($request->task_image)) {
                $this->uploadImages($request->task_image, $task);
            }

            if (!empty($request->initial_image)) {
                $this->uploadImages($request->initial_image, $task, "initial");
            }

            if (!empty($request->working_image)) {
                $this->uploadImages($request->working_image, $task, "working");
            }

            if (!empty($request->completed_image)) {
                $this->uploadImages($request->completed_image, $task, "completed");
            }

            if (!empty($request->delivered_image)) {
                $this->uploadImages($request->delivered_image, $task, "delivered");
            }

            $this->updateSpecifications($request->other_specifications, $request->delete_specifications_ids, $task->id);

            $this->updatePricebreakdowns($request->price_breakdowns, $request->delete_pricebreakdowns_ids, $task->id);

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

    public function updateSpecifications($specifications, $deleteIds, $task_id)
    {
        if (!empty($deleteIds)) {
            TaskSpecification::whereIn('id', [$deleteIds])->delete();
        }
        if (!empty($specifications)) {
            foreach ($specifications as $key => $spec) {
                if ($spec['label_name'] == null || $spec['value'] == null) {
                    continue;
                }
                $data = [
                    'task_id' => $task_id,
                    'label_name' => $spec['label_name'],
                    'type' => "text",
                    'value' => $spec['value'],
                ];
                TaskSpecification::updateOrCreate(['id' => $spec['id']], $data);
            }
        }
    }

    public function updatePricebreakdowns($breakdowns, $deleteIds, $task_id)
    {
        if (!empty($deleteIds)) {
            TaskBreakdown::whereIn('id', [$deleteIds])->delete();
        }
        if (!empty($breakdowns)) {
            foreach ($breakdowns as $key => $break) {
                if ($break['label_name'] == null || $break['value'] == null) {
                    continue;
                }
                $data = [
                    'task_id' => $task_id,
                    'label_name' => $break['label_name'],
                    'value' => $break['value'],
                ];
                TaskBreakdown::updateOrCreate(['id' => $break['id']], $data);
            }
        }
    }


    public function uploadImages($task_image, $task, $type = "main")
    {
        if (!empty($task_image)) {
            foreach ($task_image as $key => $image) {
                if ($image instanceof UploadedFile) {
                    if ($type == "main") {
                        TaskImage::whereIn('task_id', [$task->id])->where('type', "main")->delete();
                    }
                    $fileUpload = $this->fileservice->upload($image, config('const.task'), $task->code);
                    $url = config('const.task') . "/" . $fileUpload->getBaseName();
                    $img_name = $image->getClientOriginalName();

                    $data = [
                        'task_id' => $task->id,
                        'name' => $img_name,
                        'path' => $url,
                        'type' => $type,
                        // 'extension' => "image",
                        'created_by' => $task->created_by,
                        'updated_by' => $task->updated_by,
                    ];
                    $task->taskImages()->create($data);
                }
            }
        }
    }

    public function getCategoryList()
    {
        $datas = DB::table('categories')->selectRaw('id as value, name as label')->get();
        return $this->responseAPI(true, "Category get successfully", 200, $datas);
    }

    public function getCustomerList()
    {
        $datas = DB::table('customers')->selectRaw('id as value, 
        CONCAT(`first_name`," ",`last_name`, " - ", `phone_no`) as label')->get();
        return $this->responseAPI(true, "Customer get successfully", 200, $datas);
    }

    public function getWorkerList()
    {
        $datas = DB::table('workers')->selectRaw('id as value,
        CONCAT(`first_name`," ",`last_name`,  " - ",`specialist`," - ", `phone_no`) as label')->get();
        return $this->responseAPI(true, "Worker get successfully", 200, $datas);
    }
}
