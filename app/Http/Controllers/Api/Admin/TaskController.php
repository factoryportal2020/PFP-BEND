<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Http\Requests\TaskRequest;
use App\Models\Admin;
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
use Illuminate\Support\Facades\Auth;
use App\Models\Worker;

class TaskController extends BaseController
{
    //
    protected $fileservice;
    protected $usercontroller;

    // public function __construct()
    // {
    //     $this->middleware('role:admin');
    //     $this->fileservice = new FileService();
    // }


    public function list(Request $request)
    {
        try {
            $auth = Auth::user();

            $admin_id = Admin::where('user_id', $auth->id)->value('id');

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
                ->where('tasks.admin_id', $admin_id)
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
                ->when(($start_date), function ($query, $start_date) {
                    $query->where("tasks.start_date", ">=", $start_date);
                })
                ->when(($end_date), function ($query, $end_date) {
                    $query->where("tasks.end_date", "<=", $end_date);
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

                    if (($task->end_date &&
                            (date("Y-m-d H:i:s") > date("Y-m-d H:i:s", strtotime($task->end_date))))
                        && ($task->status == "Assigned"
                            || $task->status == "Inprogress"
                            || $task->status == "Restarted")
                    ) {
                        $task->overdue = "Overdue";
                    }

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


            return $this->responseAPI(true, $end_date, 200, $response);
        } catch (\Exception $e) {
            return $this->responseAPI(false, $e->getMessage(), 200);
        }
    }


    public function create(TaskRequest $request)
    {
        try {

            $auth = Auth::user();
            $request->merge([
                'domain_id' => $auth->domain_id,
                'admin_id' => $auth->admin->id,
                'created_by' => $auth->id,
                'updated_by' => $auth->id,
            ]);

            $data = $request->all();


            DB::beginTransaction();

            $data['code'] = Task::getCode();

            $task = Task::create($data);
            $task->save();

            $message = "Task Datas Saved Successfully";


            if (!empty($request->task_image)) {
                $this->uploadImages($request->task_image, $task);
                successLog("Task", "Create-task-UploadImage", "TaskImage",  $task->id, $message);
            }

            if (!empty($request->initial_image)) {
                $this->uploadImages($request->initial_image, $task, "initial");
                successLog("Task", "Create-initial-UploadImage", "TaskImage",  $task->id, $message);
            }

            if (!empty($request->working_image)) {
                $this->uploadImages($request->working_image, $task, "working");
                successLog("Task", "Create-working-UploadImage", "TaskImage",  $task->id, $message);
            }

            if (!empty($request->completed_image)) {
                $this->uploadImages($request->completed_image, $task, "completed");
                successLog("Task", "Create-completed-UploadImage", "TaskImage",  $task->id, $message);
            }

            if (!empty($request->delivered_image)) {
                $this->uploadImages($request->delivered_image, $task, "delivered");
                successLog("Task", "Create-delivered-UploadImage", "TaskImage",  $task->id, $message);
            }

            $this->updateSpecifications($request->other_specifications, $request->delete_specifications_ids, $task->id);

            $this->updatePricebreakdowns($request->price_breakdowns, $request->delete_pricebreakdowns_ids, $task->id);

            $this->updateTaskHistory($task->id, "", $request->status);

            DB::commit();
            successLog("Task", "Create", "Task",  $task->id, $message);
            return $this->responseAPI(true, $message, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof HttpResponseException) {
                errorLog("Task", "Create", "Task",  null, $e->getResponse());
                return $e->getResponse();
            }
            errorLog("Task", "Create", "Task",  null, $e->getMessage());
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
            $user_id = Auth::user()->id;
            $task = Task::findOrFail($id);
            $task->category_name = $task->category->name;

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

            $customerImages = [];
            if (!empty($task->customer->customerImages)) {
                foreach ($task->customer->customerImages as $key => $image) {
                    $customerImages[] = ['url' => env('APP_URL') . Storage::url($image->path), 'name' => $image->name, 'id' => $image->id];
                }
                $task->customer->customer_image = $customerImages;
                unset($task->customer->customerImages);
            }

            $workerImages = [];
            if (!empty($task->worker->workerImages)) {
                foreach ($task->worker->workerImages as $key => $image) {
                    $workerImages[] = ['url' => env('APP_URL') . Storage::url($image->path), 'name' => $image->name, 'id' => $image->id];
                }

                $task->worker->worker_image = $workerImages;
                unset($task->worker->workerImages);
            }

            unset($task->taskImages);
            unset($task->initialImages);
            unset($task->workingImages);
            unset($task->completedImages);
            unset($task->deliveredImages);

            $taskHistories = [];
            if (!empty($task->taskHistories)) {
                foreach ($task->taskHistories as $key => $history) {
                    $user_name = ($history->user) ? $history->user->name : $history->updated_by;
                    $history->updated_by = ($history->updated_by == $user_id) ?
                        "You" : $user_name;
                    unset($history->user);
                    $taskHistories[] = $history;
                }
            }


            $response['task'] = $task;
            $response['other_specifications'] = $task->taskSpecifications;
            $response['price_breakdowns'] = $task->taskBreakdowns;
            $response['task_histories'] = $taskHistories;
            $response['task_image']['task_image'] = $mainImages;
            $response['initial_image']['initial_image'] = $initialImages;
            $response['working_image']['working_image'] = $workingImages;
            $response['completed_image']['completed_image'] = $completedImages;
            $response['delivered_image']['delivered_image'] = $deliveredImages;

            // $response['customer_image']['customer_image'] = $customerImages;
            // $response['worker_image']['worker_image'] = $workerImages;


            unset($task->taskSpecifications);
            unset($task->taskBreakdowns);
            unset($task->taskHistories);


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
            $encrypt_id = $request->encrypt_id;
            if ($encrypt_id == null || $encrypt_id == '') {
                return $this->responseAPI(false, "Invaid Data", 200);
            }

            $id = encryptID($encrypt_id, 'd');
            $auth = Auth::user();
            $request->merge([
                'domain_id' => $auth->domain_id,
                'admin_id' => $auth->admin->id,
                'updated_by' => $auth->id,
            ]);

            $data = $request->all();

            DB::beginTransaction();

            $current_status = Task::findOrFail($id)->value('status');

            $task = Task::updateOrCreate(["id" => $id], $data);
            // $task->save();

            $message = "Task Datas Updated Successfully";

            if (!empty($request->deleteImages)) {
                $images = TaskImage::whereIn('id', $request->deleteImages)->get();
                TaskImage::whereIn('id', $request->deleteImages)->delete();
                $this->fileservice->remove_file_attachment($images, config('const.task'));
                successLog("Task", "update-image-delete", "TaskImage",  implode("~", $request->deleteImages), "Task image deleted");
            }

            if (!empty($request->task_image)) {
                $this->uploadImages($request->task_image, $task);
                successLog("Task", "update-task-image-upload", "TaskImage",  $task->id, "Task image uploaded");
            }

            if (!empty($request->initial_image)) {
                $this->uploadImages($request->initial_image, $task, "initial");
                successLog("Task", "update-initial-image-upload", "TaskImage",  $task->id, "Task image uploaded");
            }

            if (!empty($request->working_image)) {
                $this->uploadImages($request->working_image, $task, "working");
                successLog("Task", "update-working-image-upload", "TaskImage",  $task->id, "Task image uploaded");
            }

            if (!empty($request->completed_image)) {
                $this->uploadImages($request->completed_image, $task, "completed");
                successLog("Task", "update-completed-image-upload", "TaskImage",  $task->id, "Task image uploaded");
            }

            if (!empty($request->delivered_image)) {
                $this->uploadImages($request->delivered_image, $task, "delivered");
                successLog("Task", "update-delivered-image-upload", "TaskImage",  $task->id, "Task image uploaded");
            }

            $this->updateSpecifications($request->other_specifications, $request->delete_specifications_ids, $task->id);

            $this->updatePricebreakdowns($request->price_breakdowns, $request->delete_pricebreakdowns_ids, $task->id);

            $this->updateTaskHistory($task->id, $request->comment, $request->status, $current_status);

            DB::commit();
            successLog("Task", "update", "Task",  $task->id, $message);
            return $this->responseAPI(true, $message, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof HttpResponseException) {
                errorLog("Task", "Update", "Task",  null, $e->getResponse());
                return $e->getResponse();
            }
            errorLog("Task", "Update", "Task",  null, $e->getMessage());
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
                    $task_image = $task->taskImages()->create($data);
                    $size = $task_image->getFileSize();
                    TaskImage::where('id', $task_image->id)->update(['size' => $size]);
                }
            }
        }
    }

    public function updateTaskHistory($task_id, $comment, $request_status, $current_status = null)
    {
        $user_id = Auth::user()->id;
        $data = [
            'task_id' => $task_id,
            'created_by' => $user_id,
            'updated_by' => $user_id,
            'status' => $request_status,
            'comment' => $comment,
        ];
        if ($request_status != $current_status) {
            TaskHistory::create($data);
        }
    }

    public function taskStatusUpdate(Request $request)
    {
        try {
            DB::beginTransaction();

            $task_id = encryptID($request->encrypt_id, 'd');

            $request_status = $request->status;
            $comment = $request->comment;

            $task = Task::findOrFail($task_id);
            $current_status = $task->status;
            $task->update(['status' => $request_status]);

            $this->updateTaskHistory($task_id, $comment, $request_status, $current_status);
            $message = "Status updated successfully" . $current_status . " to " . $request_status;
            DB::commit();
            successLog("Task", "Status-update", "Task",  $task->id, $message);
            return $this->responseAPI(true, $message, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof HttpResponseException) {
                errorLog("Task", "Status-Update", "Task",  null, $e->getResponse());
                return $e->getResponse();
            }
            errorLog("Task", "Status-Update", "Task",  null, $e->getMessage());
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
            $images = TaskImage::where('task_id', $id)->get();
            TaskImage::where('task_id', $id)->delete();
            $this->fileservice->remove_file_attachment($images);
            $task = Task::findOrFail($id);
            $delete = Task::findOrFail($id)->delete();
            if ($delete) {
                $message = "Task data deleted successfully";
                successLog("Task", "Delete", "Task",  $task->id, $message);
                return $this->responseAPI(true, $message, 200);
            } else {
                $message = "Something went wrong";
                errorLog("Task", "Delete", "Task",  $task->id, $message);
                return $this->responseAPI(false, $message, 200);
            }
        } catch (\Exception $e) {
            errorLog("Task", "Delete", "Task",  $task->id, $e->getMessage());
            return $this->responseAPI(false, $e->getMessage(), 200);
        }
    }

    public function getCategoryList($selectCondition)
    {
        $auth = Auth::user();
        $admin_id = $auth->admin->id;
        $datas = DB::table('categories')->selectRaw('id as value, name as label')
            ->when(($selectCondition == "wt"), function ($query, $encrypt_id) {
                $query->where('deleted_at', null)
                    ->where('status', 1);
            })
            ->where('admin_id', $admin_id)
            ->get();
        return $this->responseAPI(true, "Category get successfully", 200, $datas);
    }

    public function getCustomerList($selectCondition)
    {
        // wt = add, all = edit, list, view 
        $auth = Auth::user();
        $admin_id = $auth->admin->id;
        $datas = DB::table('customers')
            ->selectRaw('id as value, 
                CONCAT(`first_name`," ",`last_name`, " - ", `phone_no`) as label')
            ->when(($selectCondition == "wt"), function ($query, $encrypt_id) {
                $query->where('deleted_at', null)
                    ->where('status', 1);
            })
            ->where('admin_id', $admin_id)
            ->get();
        return $this->responseAPI(true, "Customer get successfully", 200, $datas);
    }

    public function getWorkerList($selectCondition)
    {
        $auth = Auth::user();
        $admin_id = $auth->admin->id;
        $datas = DB::table('workers')->selectRaw('id as value,
        CONCAT(`first_name`," ",`last_name`,  " - ",`specialist`," - ", `phone_no`) as label')
            ->when(($selectCondition == "wt"), function ($query, $encrypt_id) {
                $query->where('deleted_at', null)
                    ->where('status', 1);
            })
            ->where('admin_id', $admin_id)
            ->get();
        return $this->responseAPI(true, "Worker get successfully", 200, $datas);
    }
}
