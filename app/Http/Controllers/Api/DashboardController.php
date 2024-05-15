<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Enquiry;
use App\Models\Favourite;
use App\Models\Item;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Subscribe;
use App\Models\Task;
use App\Models\Website;
use App\Models\Worker;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DashboardController extends BaseController
{
    //
    public function dashboard(Request $request)
    {
        try {
            $response = [];
            $start_date = (!empty($request->start_date)) ? date("Y-m-d H:i:s", strtotime($request->start_date)) : "";
            $end_date = (!empty($request->end_date)) ? date("Y-m-d H:i:s", strtotime($request->end_date)) : "";


            $timeEvent = $request->timeEvent;


            $user = Auth::user();


            if (!$user) {
                return $this->responseAPI(false, "Dashboard get failed", 200, null);
            }
            $role = $user->role->name;

            $profile_encrypt_id = null;

            $customers = $workers = $categories  = $products  = $messages  = $subscribes  = $enquiries = $favourites = $website = [];

            if ($role == "customer") {
                $customer_id = Customer::where('user_id', $user->id)->value('id');
                $admin_id = Customer::where('user_id', $user->id)->value('admin_id');
                $profile_encrypt_id = ($customer_id) ? encryptID($customer_id, 'e') : null;

                $favourites['count'] =
                    Favourite::where('admin_id', $admin_id)
                    ->where('user_id', $user->id)
                    ->when(($start_date), function ($query, $start_date) {
                        $query->where("favourites.created_at", ">=", $start_date);
                    })
                    ->when(($end_date), function ($query, $end_date) {
                        $query->where("favourites.created_at", "<=", $end_date);
                    })->count();

                $enquiries['count'] =
                    Enquiry::where('admin_id', $admin_id)
                    ->where('user_id', $user->id)
                    ->when(($start_date), function ($query, $start_date) {
                        $query->where("enquiries.created_at", ">=", $start_date);
                    })
                    ->when(($end_date), function ($query, $end_date) {
                        $query->where("enquiries.created_at", "<=", $end_date);
                    })->count();
            } elseif ($role == "admin" || $role == "worker") {
                if ($role == "admin") {
                    $admin_id = Admin::where('user_id', $user->id)->value('id');
                    $profile_encrypt_id = ($admin_id) ? encryptID($admin_id, 'e') : null;

                    $customers['count'] =
                        Customer::where('admin_id', $admin_id)
                        ->when(($start_date), function ($query, $start_date) {
                            $query->where("customers.created_at", ">=", $start_date);
                        })
                        ->when(($end_date), function ($query, $end_date) {
                            $query->where("customers.created_at", "<=", $end_date);
                        })->count();

                    $workers['count'] =
                        Worker::where('admin_id', $admin_id)
                        ->when(($start_date), function ($query, $start_date) {
                            $query->where("workers.created_at", ">=", $start_date);
                        })
                        ->when(($end_date), function ($query, $end_date) {
                            $query->where("workers.created_at", "<=", $end_date);
                        })->count();

                    $categories['count'] =
                        Category::where('admin_id', $admin_id)
                        ->when(($start_date), function ($query, $start_date) {
                            $query->where("categories.created_at", ">=", $start_date);
                        })
                        ->when(($end_date), function ($query, $end_date) {
                            $query->where("categories.created_at", "<=", $end_date);
                        })->count();

                    $products['count'] =
                        Item::where('admin_id', $admin_id)
                        ->when(($start_date), function ($query, $start_date) {
                            $query->where("items.created_at", ">=", $start_date);
                        })
                        ->when(($end_date), function ($query, $end_date) {
                            $query->where("items.created_at", "<=", $end_date);
                        })->count();

                    $messages['count'] =
                        Message::where('admin_id', $admin_id)
                        ->when(($start_date), function ($query, $start_date) {
                            $query->where("messages.created_at", ">=", $start_date);
                        })
                        ->when(($end_date), function ($query, $end_date) {
                            $query->where("messages.created_at", "<=", $end_date);
                        })->count();

                    $favourites['count'] =
                        Favourite::where('admin_id', $admin_id)
                        ->when(($start_date), function ($query, $start_date) {
                            $query->where("favourites.created_at", ">=", $start_date);
                        })
                        ->when(($end_date), function ($query, $end_date) {
                            $query->where("favourites.created_at", "<=", $end_date);
                        })->count();

                    $enquiries['count'] =
                        Enquiry::where('admin_id', $admin_id)
                        ->when(($start_date), function ($query, $start_date) {
                            $query->where("enquiries.created_at", ">=", $start_date);
                        })
                        ->when(($end_date), function ($query, $end_date) {
                            $query->where("enquiries.created_at", "<=", $end_date);
                        })->count();

                    $subscribes['count'] =
                        Subscribe::where('admin_id', $admin_id)
                        ->when(($start_date), function ($query, $start_date) {
                            $query->where("subscribes.created_at", ">=", $start_date);
                        })
                        ->when(($end_date), function ($query, $end_date) {
                            $query->where("subscribes.created_at", "<=", $end_date);
                        })->count();

                    $website_check = Website::where("admin_id", $admin_id)->first();

                    $website['website'] = $website_check;
                    $bannerImages = [];
                    if (!empty($website['website']) && !empty($website['website']->bannerImages)) {
                        foreach ($website['website']->bannerImages as $key => $image) {
                            if ($image->type == "banner1") {
                                $bannerImages = [
                                    'url' => env('APP_URL') . Storage::url($image->path), 'name' => $image->name, 'id' => $image->id,
                                    'title' => $image->title, 'caption' => $image->caption,
                                ];
                            }
                        }
                    }
                }

                if ($role == "admin" || $role == "worker") {
                    if ($role == "worker") {
                        $worker_id = Worker::where('user_id', $user->id)->value('id');
                        $admin_id = Worker::where('user_id', $user->id)->value('admin_id');
                        $profile_encrypt_id = ($worker_id) ? encryptID($worker_id, 'e') : null;
                    }
                    $task['total_tasks'] = $task['Unassigned'] =
                        $task['Assigned'] = $task['Inprogress'] = $task['Holding'] = $task['Restarted'] =
                        $task['Cancelled'] = $task['Pending'] = $task['Completed'] =
                        $task['Delivered'] = $task['overdue_tasks'] = 0;
                    $datas = Task::where('admin_id', $admin_id);

                    if ($timeEvent == "today") {
                        $datas->whereDate('created_at', Carbon::today());
                    }
                    if ($timeEvent == "yesterday") {
                        $datas->whereDate('created_at', Carbon::yesterday());
                    }

                    if ($timeEvent == "week") {
                        $datas->whereBetween("created_at", [
                            Carbon::now()->startOfWeek()->format('Y-m-d H:i:s'),
                            Carbon::now()->endOfWeek()->format('Y-m-d H:i:s')
                        ]);
                    }

                    if ($timeEvent == "lastweek") {
                        $datas->whereBetween("created_at", [
                            Carbon::now()->subWeek()->startOfWeek()->format('Y-m-d H:i:s'),
                            Carbon::now()->subWeek()->endOfWeek()->format('Y-m-d H:i:s')
                        ]);
                    }

                    if ($timeEvent == "month") {
                        $datas->whereMonth('created_at', Carbon::now()->month);
                    }

                    if ($timeEvent == "lastmonth") {
                        $datas->whereMonth('created_at', Carbon::now()->subMonth()->month);
                    }

                    if ($timeEvent == "year") {
                        $datas->whereYear('created_at', Carbon::now()->subMonth()->year);
                    }

                    //Worker
                    if ($role == "worker") {
                        // Worker ID is the (user id) 
                        $datas->where('worker_id', $worker_id);
                    }

                    $tasks = $datas->get();

                    if (!empty($tasks)) {
                        foreach ($tasks as $key => $t) {
                            if ($t->status == "Unassigned") {
                                $task['Unassigned'] = $task['Unassigned'] + 1;
                            } elseif ($t->status == "Assigned") {
                                $task['Assigned'] = $task['Assigned'] + 1;
                            } elseif ($t->status == "Inprogress") {
                                $task['Inprogress'] = $task['Inprogress'] + 1;
                            } elseif ($t->status == "Holding") {
                                $task['Holding'] = $task['Holding'] + 1;
                            } elseif ($t->status == "Restarted") {
                                $task['Restarted'] = $task['Restarted'] + 1;
                            } elseif ($t->status == "Cancelled") {
                                $task['Cancelled'] = $task['Cancelled'] + 1;
                            } elseif ($t->status == "Pending") {
                                $task['Pending'] = $task['Pending'] + 1;
                            } elseif ($t->status == "Completed") {
                                $task['Completed'] = $task['Completed'] + 1;
                            } elseif ($t->status == "") {
                                $task['Delivered'] = $task['Delivered'] + 1;
                            }

                            $task['total_tasks'] = $task['total_tasks'] + 1;

                            if (($t->end_date &&
                                    (date("Y-m-d H:i:s") > date("Y-m-d H:i:s", strtotime($t->end_date))))
                                && ($t->status == "Assigned"
                                    || $t->status == "Inprogress"
                                    || $t->status == "Restarted")
                            ) {
                                $task['overdue_tasks'] = $task['overdue_tasks'] + 1;
                            }
                        }
                    }
                }
            }


            $userInfo = [
                'role' => $user->role->name,
                'username' => $user->username,
                'email' => $user->email,
                'user_encrypt_id' => encryptID($user->id, 'e'),
                'profile_encrypt_id' => $profile_encrypt_id
            ];
            $response['userInfo'] = $userInfo;

            if ($role == "admin" || $role == "customer") {
                if ($role == "admin") {
                    $response['customers'] = ($customers) ? $customers : [];
                    $response['workers'] = ($workers) ? $workers : [];
                    $response['categories'] = ($categories) ? $categories : [];
                    $response['products'] = ($products) ? $products : [];

                    $response['website'] = ($website) ? $website : [];
                    $response['website']['bannerImages'] = ($bannerImages) ? $bannerImages : [];
                    $response['messages'] = ($messages) ? $messages : [];
                    $response['subscribes'] = ($subscribes) ? $subscribes : [];
                }


                $response['enquiries'] = ($enquiries) ? $enquiries : [];
                $response['favourites'] = ($favourites) ? $favourites : [];
            }

            if ($role == "admin" || $role == "worker") {
                $response['tasks'] = ($task) ? $task : [];
            }
            $message = "Dashboard get successfully";
            return $this->responseAPI(true, $message, 200, $response);
        } catch (\Exception $e) {
            return $this->responseAPI(false, $e->getMessage(), 200);
        }
    }

    public function getNotification(Request $request)
    {
        try {
            $response = [];

            $search_word = $request->search_word;

            $itemPerPage = $request->itemPerPage;
            $offset = $request->offset;
            $totalCount = 0;

            $limit = $request->limit;
            $user = Auth::user();

            if (!$user) {
                return $this->responseAPI(false, "Notification get failed", 200, null);
            }

            $role = $user->role->name;
            if ($role == "admin") {
            } else if ($role == "worker") {
            } else {
            }

            $admin_id = $user->admin->id;

            $data = Notification::where('admin_id', $admin_id)
                ->where('receiver_id', $user->id)->where('is_viewed', 0);

            $totalCount = $data->count();


            if ($limit) {
                $data->limit($limit)->orderBy('created_at', 'DESC');
            } else {
                $data->limit($itemPerPage)->orderBy("notifications.id", "DESC");
            }

            if ($offset) {
                $data->offset($offset);
            }

            $response['notifications'] = $data->get();
            $response['notifications_count'] = $data->count();
            $response['totalCount'] = $totalCount;

            $message = "Notification get successfully";
            return $this->responseAPI(true, $message, 200, $response);
        } catch (\Exception $e) {
            return $this->responseAPI(false, $e->getMessage(), 200);
        }
    }
}
