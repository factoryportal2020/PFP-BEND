<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use App\Models\Favourite;
use App\Models\Message;
use App\Models\Subscribe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EnquiryController extends BaseController
{
    //
    public function enquiryList(Request $request)
    {
        try {
            $search_word = $request->search_word;
            $admin_id = Auth::user()->admin_id;


            $limit = $request->itemPerPage;
            $offset = $request->offset;
            $totalCount = 0;

            $datas =
                Enquiry::select(
                    "enquiries.*",
                    DB::raw('enquiries.code as code'),
                    DB::raw('items.name as product_name'),
                    DB::raw('users.username as username'),
                    DB::raw('users.name as loggedname'),
                    DB::raw('items.id as product_id'),
                    DB::raw('items.code as product_code'),
                    DB::raw('item_images.path as image_path'),
                    DB::raw('item_images.name as image_name')
                )
                ->join('items', function ($join) {
                    $join->on('enquiries.item_id', '=', 'items.id');
                })
                ->leftJoin('item_images', function ($join) {
                    $join->on('item_images.item_id', '=', 'items.id')
                        ->where('item_images.type', '=', "main");
                })
                ->leftJoin('users', function ($join) {
                    $join->on('users.id', '=', 'enquiries.user_id')
                        ->where('enquiries.user_id', '!=', null);
                })
                ->when($search_word, function ($query, $search_word) {
                    $query->where(function ($whr_query) use ($search_word) {
                        $whr_query->where('enquiries.name', 'like', '%' . $search_word . '%');
                        //   ->orWhere('votes', '>', 50);
                    });
                })
                ->where('items.deleted_at', null)
                ->where('items.admin_id', $admin_id)
                ->where('enquiries.admin_id', $admin_id)
                ->where('items.is_show', 1)
                ->where('items.status', 1);

            $totalCount = $datas->count();

            $datas->limit($limit)->orderBy("enquiries.id", "DESC");

            if ($offset) {
                $datas->offset($offset);
            }

            $enquiries = $datas->get();

            if (!empty($enquiries)) {
                foreach ($enquiries as $key => $enquiry) {
                    $url = ($enquiry->image_path != "" || $enquiry->image_path != null) ? env('APP_URL') . Storage::url($enquiry->image_path) : "";
                    $enquiry['item_image'] = [
                        'url' => $url,
                        'name' => $enquiry->image_name
                    ];
                    $enquiry->encrypt_id = encryptID($enquiry->id, 'e');
                    $enquiry->product_encrypt_id = encryptID($enquiry->product_id, 'e');
                    unset($enquiry->image_path);
                    unset($enquiry->image_name);
                }
            }

            $response['data'] = $enquiries;
            $response['totalCount'] = $totalCount;


            return $this->responseAPI(true, "Enquiry list get successfully", 200, $response);
        } catch (\Exception $e) {
            return $this->responseAPI(false, $e->getMessage(), 200);
        }
    }

    public function favouriteList(Request $request)
    {
        try {
            $search_word = $request->search_word;
            $admin_id = Auth::user()->admin_id;

            $limit = $request->itemPerPage;
            $offset = $request->offset;
            $totalCount = 0;

            $datas =
                Favourite::select(
                    "favourites.*",
                    DB::raw('items.name as product_name'),
                    DB::raw('items.id as product_id'),
                    DB::raw('items.code as product_code'),
                    DB::raw('item_images.path as image_path'),
                    DB::raw('item_images.name as image_name')
                )
                ->join('items', function ($join) {
                    $join->on('favourites.item_id', '=', 'items.id');
                })
                ->leftJoin('item_images', function ($join) {
                    $join->on('item_images.item_id', '=', 'items.id')
                        ->where('item_images.type', '=', "main");
                })
                ->leftJoin('users', function ($join) {
                    $join->on('users.id', '=', 'favourites.user_id')
                        ->where('favourites.user_id', '!=', null);
                })
                ->when($search_word, function ($query, $search_word) {
                    $query->where(function ($whr_query) use ($search_word) {
                        $whr_query->where('favourites.name', 'like', '%' . $search_word . '%');
                        //   ->orWhere('votes', '>', 50);
                    });
                })
                ->where('items.deleted_at', null)
                ->where('items.admin_id', $admin_id)
                ->where('favourites.admin_id', $admin_id)
                ->where('items.is_show', 1)
                ->where('items.status', 1);

            $totalCount = $datas->count();

            $datas->limit($limit)->orderBy("favourites.id", "DESC");

            if ($offset) {
                $datas->offset($offset);
            }

            $favourites = $datas->get();

            if (!empty($favourites)) {
                foreach ($favourites as $key => $favourite) {
                    $url = ($favourite->image_path != "" || $favourite->image_path != null) ? env('APP_URL') . Storage::url($favourite->image_path) : "";
                    $favourite['item_image'] = [
                        'url' => $url,
                        'name' => $favourite->image_name
                    ];
                    $favourite->encrypt_id = encryptID($favourite->id, 'e');
                    $favourite->product_encrypt_id = encryptID($favourite->product_id, 'e');
                    unset($favourite->image_path);
                    unset($favourite->image_name);
                }
            }

            $response['data'] = $favourites;
            $response['totalCount'] = $totalCount;
            return $this->responseAPI(true, "Favourite list get successfully", 200, $response);
        } catch (\Exception $e) {
            return $this->responseAPI(false, $e->getMessage(), 200);
        }
    }


    public function subscribeList(Request $request)
    {
        try {
            $search_word = $request->search_word;
            $admin_id = Auth::user()->admin_id;

            $limit = $request->itemPerPage;
            $offset = $request->offset;
            $totalCount = 0;

            $datas =
                Subscribe::select(
                    "subscribes.*",
                )
                ->when($search_word, function ($query, $search_word) {
                    $query->where(function ($whr_query) use ($search_word) {
                        $whr_query->where('subscribes.name', 'like', '%' . $search_word . '%');
                        //   ->orWhere('votes', '>', 50);
                    });
                })
                ->where('subscribes.admin_id', $admin_id);


            $totalCount = $datas->count();

            $datas->limit($limit)->orderBy("subscribes.id", "DESC");

            if ($offset) {
                $datas->offset($offset);
            }

            $subscribes = $datas->get();

            $response['data'] = $subscribes;
            $response['totalCount'] = $totalCount;
            return $this->responseAPI(true, "Subscribe list get successfully", 200, $response);
        } catch (\Exception $e) {
            return $this->responseAPI(false, $e->getLine(), 200);
        }
    }

    public function messageList(Request $request)
    {
        try {
            $search_word = $request->search_word;
            $admin_id = Auth::user()->admin_id;

            $limit = $request->itemPerPage;
            $offset = $request->offset;
            $totalCount = 0;

            $datas =
                Message::select(
                    "messages.*",
                )
                ->when($search_word, function ($query, $search_word) {
                    $query->where(function ($whr_query) use ($search_word) {
                        $whr_query->where('messages.name', 'like', '%' . $search_word . '%');
                        //   ->orWhere('votes', '>', 50);
                    });
                })
                ->where('messages.admin_id', $admin_id);


            $totalCount = $datas->count();

            $datas->limit($limit)->orderBy("messages.id", "DESC");

            if ($offset) {
                $datas->offset($offset);
            }

            $messages = $datas->get();

            $response['data'] = $messages;
            $response['totalCount'] = $totalCount;
            return $this->responseAPI(true, "Message list get successfully", 200, $response);
        } catch (\Exception $e) {
            return $this->responseAPI(false, $e->getMessage(), 200);
        }
    }
}
