<?php

namespace App\Http\Controllers\Api\Website;

use App\Events\SaveNotification;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\BaseController;
use App\Models\Admin;
use Illuminate\Support\Facades\DB;
use App\Models\Item;
use App\Models\Website;
use App\Models\Category;
use App\Models\Enquiry;
use App\Models\Favourite;
use App\Models\Message;
use App\Models\Subscribe;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\Auth;

class HomeController extends BaseController
{
    //
    // public function __construct()
    // {
    //     $this->middleware('role:customer');
    // }

    public function getCustomerList(Request $request)
    {
        try {
            $response = [];
            $admin_id = encryptID($request->header('Admin-EncryptId'), 'd');
            $website = Website::where("admin_id", $admin_id)->first();

            $logoImages = [];
            if (!empty($website->logoImages)) {
                foreach ($website->logoImages as $key => $image) {
                    $logoImages[] = [
                        'url' => env('APP_URL') . Storage::url($image->path),
                        'name' => $image->name,
                        'id' => $image->id,
                        'detail' => $image->detail
                    ];
                }
            }

            $aboutImages = [];
            if (!empty($website->aboutImages)) {
                foreach ($website->aboutImages as $key => $image) {
                    $aboutImages[] = [
                        'url' => env('APP_URL') . Storage::url($image->path),
                        'name' => $image->name,
                        'id' => $image->id,
                        'detail' => $image->detail
                    ];
                }
            }

            $featureImages = [];
            if (!empty($website->featureImages)) {
                foreach ($website->featureImages as $key => $image) {
                    $featureImages[] = ['url' => env('APP_URL') . Storage::url($image->path), 'name' => $image->name, 'id' => $image->id];
                }
            }

            $bannerImages = [];
            if (!empty($website->bannerImages)) {
                foreach ($website->bannerImages as $key => $image) {
                    $bannerImages[] = [
                        'url' => env('APP_URL') . Storage::url($image->path),
                        'name' => $image->name,
                        'id' => $image->id,
                        'title' => $image->title,
                        'caption' => $image->caption,
                    ];
                }
            }

            unset($website->aboutImages);
            unset($website->featureImages);
            unset($website->bannerImages);
            unset($website->logoImages);

            $response['website'] = $website;
            $response['website']['encrypt_id'] = ($website) ? encryptID($website->id) : null;
            $response['about_image']['about_image'] = $aboutImages;
            $response['logo_image']['logo_image'] = $logoImages;
            $response['website']['about'] =  ($aboutImages && count($aboutImages) > 0) ? $aboutImages[0]['detail'] : "";

            $response['banner_image1']['banner_image1'] = ($bannerImages && count($bannerImages) > 0) ? [$bannerImages[0]] : [];
            $response['website']['banner_title1'] = ($bannerImages && count($bannerImages) > 0) ? $bannerImages[0]['title'] : "";
            $response['website']['banner_caption1'] = ($bannerImages && count($bannerImages)  > 0) ? $bannerImages[0]['caption'] : "";

            $response['banner_image2']['banner_image2'] = ($bannerImages && count($bannerImages) > 1) ? [$bannerImages[1]] : [];
            $response['website']['banner_title2'] = ($bannerImages && count($bannerImages) > 1) ? $bannerImages[1]['title'] : "";
            $response['website']['banner_caption2'] = ($bannerImages && count($bannerImages) > 1) ? $bannerImages[1]['caption'] : "";

            $response['banner_image3']['banner_image3'] = ($bannerImages && count($bannerImages) > 2) ? [$bannerImages[2]] : [];
            $response['website']['banner_title3'] = ($bannerImages && count($bannerImages) > 2) ? $bannerImages[2]['title'] : "";
            $response['website']['banner_caption3'] = ($bannerImages && count($bannerImages) > 2) ? $bannerImages[2]['caption'] : "";

            $response['feature_image1']['feature_image1'] = ($featureImages && count($featureImages) > 0) ? [$featureImages[0]] : [];
            $response['feature_image2']['feature_image2'] = ($featureImages && count($featureImages) > 1) ? [$featureImages[1]] : [];
            $response['feature_image3']['feature_image3'] = ($featureImages && count($featureImages) > 2) ? [$featureImages[2]] : [];

            return $this->responseAPI(true, "Customer get successfully", 200, $response);
        } catch (\Exception $e) {
            return $this->responseAPI(false, $e->getMessage(), 200);
        }
    }

    public function getLogolist(Request $request)
    {
        try {
            $response = [];
            $admin_id = encryptID($request->header('Admin-EncryptId'), 'd');
            $website = Website::where("admin_id", $admin_id)->first();

            $logoImages = [];
            if (!empty($website->logoImages)) {
                foreach ($website->logoImages as $key => $image) {
                    $logoImages[] = [
                        'url' => env('APP_URL') . Storage::url($image->path),
                        'name' => $image->name,
                        'id' => $image->id,
                        'detail' => $image->detail
                    ];
                }
            }


            unset($website->aboutImages);
            unset($website->featureImages);
            unset($website->bannerImages);
            unset($website->logoImages);

            $response['website'] = $website;
            $response['logo_image']['logo_image'] = $logoImages;

            return $this->responseAPI(true, "Logo get successfully", 200, $response);
        } catch (\Exception $e) {
            return $this->responseAPI(false, $e->getMessage(), 200);
        }
    }

    public function getBannerList(Request $request)
    {
        try {
            $response = [];
            $admin_id = encryptID($request->header('Admin-EncryptId'), 'd');
            $website = Website::where("admin_id", $admin_id)->first();


            $bannerImages = [];
            if (!empty($website->bannerImages)) {
                foreach ($website->bannerImages as $key => $image) {
                    $bannerImages[] = [
                        'url' => env('APP_URL') . Storage::url($image->path),
                        'name' => $image->name,
                        'id' => $image->id,
                        'title' => $image->title,
                        'caption' => $image->caption,
                    ];
                }
            }

            unset($website->aboutImages);
            unset($website->featureImages);
            unset($website->bannerImages);
            unset($website->logoImages);

            // $response['website'] = $website;
            // $response['website']['encrypt_id'] = ($website) ? encryptID($website->id) : null;

            $response['banners']['banner_image1'] = ($bannerImages && count($bannerImages) > 0) ? [$bannerImages[0]] : [];
            // $response['website']['banner_title1'] = ($bannerImages && count($bannerImages) > 0) ? $bannerImages[0]['title'] : "";
            // $response['website']['banner_caption1'] = ($bannerImages && count($bannerImages)  > 0) ? $bannerImages[0]['caption'] : "";

            $response['banners']['banner_image2'] = ($bannerImages && count($bannerImages) > 1) ? [$bannerImages[1]] : [];
            // $response['website']['banner_title2'] = ($bannerImages && count($bannerImages) > 1) ? $bannerImages[1]['title'] : "";
            // $response['website']['banner_caption2'] = ($bannerImages && count($bannerImages) > 1) ? $bannerImages[1]['caption'] : "";

            $response['banners']['banner_image3'] = ($bannerImages && count($bannerImages) > 2) ? [$bannerImages[2]] : [];
            // $response['website']['banner_title3'] = ($bannerImages && count($bannerImages) > 2) ? $bannerImages[2]['title'] : "";
            // $response['website']['banner_caption3'] = ($bannerImages && count($bannerImages) > 2) ? $bannerImages[2]['caption'] : "";


            return $this->responseAPI(true, "Banner get successfully", 200, $response);
        } catch (\Exception $e) {
            return $this->responseAPI(false, $e->getMessage(), 200);
        }
    }

    public function getAboutList(Request $request)
    {
        try {
            $response = [];
            $admin_id = encryptID($request->header('Admin-EncryptId'), 'd');
            $website = Website::where("admin_id", $admin_id)->first();

            $aboutImages = [];
            if (!empty($website->aboutImages)) {
                foreach ($website->aboutImages as $key => $image) {
                    $aboutImages[] = [
                        'url' => env('APP_URL') . Storage::url($image->path),
                        'name' => $image->name,
                        'id' => $image->id,
                        'detail' => $image->detail,
                        'caption' => $image->caption,
                    ];
                }
            }

            $bannerImages = [];
            if (!empty($website->bannerImages)) {
                foreach ($website->bannerImages as $key => $image) {
                    $bannerImages[] = [
                        'url' => env('APP_URL') . Storage::url($image->path),
                        'name' => $image->name,
                        'id' => $image->id,
                        'title' => $image->title,
                        'caption' => $image->caption,
                    ];
                }
            }


            unset($website->aboutImages);
            unset($website->featureImages);
            unset($website->bannerImages);
            unset($website->logoImages);

            // $response['website'] = $website;
            // $response['website']['encrypt_id'] = ($website) ? encryptID($website->id) : null;

            $response['about']['about_image'] = ($aboutImages && count($aboutImages) > 0) ? [$aboutImages[0]] : [];
            $response['banners']['banner_image1'] = ($bannerImages && count($bannerImages) > 0) ? [$bannerImages[0]] : [];

            return $this->responseAPI(true, "About get successfully", 200, $response);
        } catch (\Exception $e) {
            return $this->responseAPI(false, $e->getMessage(), 200);
        }
    }

    public function getCategoryList(Request $request)
    {
        // if ($encrypt_admin_id == null || $encrypt_admin_id == '') {
        //     return $this->responseAPI(false, "Invaid Data", 200);
        // }
        // $admin_id = encryptID($encrypt_admin_id, 'd');
        $admin_id = encryptID($request->header('Admin-EncryptId'), 'd');

        $search_word = $request->search_word;

        $limit = ($request->itemPerPage) ? $request->itemPerPage : "";
        $offset = $request->offset;

        $totalCount = 0;

        $datas =
            Category::select(
                "categories.*",
                DB::raw('category_images.path as image_path'),
                DB::raw('category_images.name as image_name')
            )
            ->leftJoin('category_images', 'category_images.category_id', '=', 'categories.id')
            // ->leftJoin('users', 'users.id', '=', 'categories.user_id')
            ->when($search_word, function ($query, $search_word) {
                $query->where(function ($whr_query) use ($search_word) {
                    $whr_query->where('categories.name', 'like', '%' . $search_word . '%');
                    //   ->orWhere('votes', '>', 50);
                });
            })->where('deleted_at', null)->where('categories.admin_id', $admin_id)->where('status', 1)->where('is_show', 1);


        $totalCount = $datas->count();

        if ($limit != "") {
            $datas->limit($limit)->orderBy("categories.id", "DESC");
        }
        if ($offset) {
            $datas->offset($offset);
        }
        // $datas->groupBy("categories.id")
        $categories = $datas->get();

        if (!empty($categories)) {
            foreach ($categories as $key => $category) {
                $url = ($category->image_path != "" || $category->image_path != null) ? env('APP_URL') . Storage::url($category->image_path) : "";
                $category['category_image'] = [
                    'url' => $url,
                    'name' => $category->image_name
                ];
                $category->encrypt_id = encryptID($category->id, 'e');
                unset($category->image_path);
                unset($category->image_name);
            }
        }

        // $response['category_image']['category_image'] = $images;

        $response['data'] = $categories;
        $response['totalCount'] = $totalCount;


        return $this->responseAPI(true, "Category get successfully", 200, $response);
    }

    public function getProductlist(Request $request)
    {
        try {
            $search_word = $request->search_word;
            // $category_id = $request->category_id;
            $category_code = $request->category_code;
            $categoriesFilter = $request->categoriesFilter;
            $categoriesFilterTrig = (!empty($categoriesFilter)) ? true : false;
            $category_not_all = ($category_code != "*") ? 1 : 0;
            // $admin_id = $request->admin_id;
            $admin_id = encryptID($request->header('Admin-EncryptId'), 'd');

            $limit = $request->itemPerPage;
            $offset = $request->offset;

            $totalCount = 0;

            $datas =
                Item::select(
                    "items.*",
                    DB::raw('categories.name as category_name'),
                    DB::raw('categories.code as category_code'),
                    DB::raw('item_images.path as image_path'),
                    DB::raw('item_images.name as image_name')
                )
                ->leftJoin('item_images', function ($join) {
                    $join->on('item_images.item_id', '=', 'items.id')
                        ->where('item_images.type', '=', "main");
                })
                ->join('categories', 'categories.id', '=', 'items.category_id')
                // ->leftJoin('users', 'users.id', '=', 'items.user_id')
                ->when($search_word, function ($query, $search_word) {
                    $query->where(function ($whr_query) use ($search_word) {
                        $whr_query->where('items.name', 'like', '%' . $search_word . '%');
                        //   ->orWhere('votes', '>', 50);
                    });
                })
                ->when($category_code, function ($query, $category_code) {
                    $query->where("categories.code", $category_code);
                })

                ->when($categoriesFilterTrig, function ($query) use ($categoriesFilter) {
                    $query->whereIn("categories.id", $categoriesFilter);
                })

                ->where('items.deleted_at', null)
                ->where('categories.admin_id', $admin_id)
                ->where('items.admin_id', $admin_id)
                ->where('items.is_show', 1)
                ->where('items.status', 1);


            $totalCount = $datas->count();

            $datas->limit($limit)->orderBy("items.id", "DESC");

            if ($offset) {
                $datas->offset($offset);
            }
            // $datas->groupBy("item_images.item_id");
            $items = $datas->get();

            if (!empty($items)) {
                foreach ($items as $key => $item) {
                    $url = ($item->image_path != "" || $item->image_path != null) ? env('APP_URL') . Storage::url($item->image_path) : "";
                    $item['item_image'] = [
                        'url' => $url,
                        'name' => $item->image_name
                    ];
                    $item->encrypt_id = encryptID($item->id, 'e');
                    unset($item->image_path);
                    unset($item->image_name);
                }
            }

            // $response['item_image']['item_image'] = $images;

            $response['data'] = $items;
            $response['totalCount'] = $totalCount;


            return $this->responseAPI(true, "Product list get successfully", 200, $response);
        } catch (\Exception $e) {
            return $this->responseAPI(false, $e->getMessage(), 200);
        }
    }

    public function getProduct(Request $request, $encrypt_id)
    {
        try {
            if ($encrypt_id == null || $encrypt_id == '') {
                return $this->responseAPI(false, "Invaid Data", 200);
            }
            $id = encryptID($encrypt_id, 'd');

            $admin_id = encryptID($request->header('Admin-EncryptId'), 'd');
            $user_id = ($request->userEncryptID) ? encryptID($request->userEncryptID, 'd') : null;

            $response = [];

            $item = Item::where('admin_id', $admin_id)
                ->where('items.deleted_at', null)
                ->where('items.status', 1)
                ->where('items.is_show', 1)->findOrFail($id);

            $mainImages = [];
            if (!empty($item->mainImages)) {
                foreach ($item->mainImages as $key => $image) {
                    $mainImages[] = ['url' => env('APP_URL') . Storage::url($image->path), 'name' => $image->name, 'id' => $image->id];
                }
            }

            $otherImages = [];
            if (!empty($item->otherImages)) {
                foreach ($item->otherImages as $key => $image) {
                    $otherImages[] = ['url' => env('APP_URL') . Storage::url($image->path), 'name' => $image->name, 'id' => $image->id];
                }
            }

            unset($item->itemImages);
            $item_name =  $item->name;
            $category_name =  $item->category->name;
            // Related items
            $related_items =
                Item::select(
                    "items.*",
                    DB::raw('categories.name as category_name'),
                    DB::raw('categories.code as category_code'),
                    DB::raw('item_images.path as image_path'),
                    DB::raw('item_images.name as image_name')
                )
                ->leftJoin('item_images', function ($join) {
                    $join->on('item_images.item_id', '=', 'items.id')
                        ->where('item_images.type', '=', "main");
                })
                ->join('categories', 'categories.id', '=', 'items.category_id')
                ->where(function ($whr_query) use ($item_name, $category_name) {
                    $whr_query->where('items.name', 'like', '%' . $item_name . '%')
                        ->orWhere('categories.name', 'like', '%' . $category_name . '%');
                })
                ->where('items.deleted_at', null)
                ->where('categories.admin_id', $admin_id)
                ->where('items.admin_id', $admin_id)
                ->where('items.id', "!=", $id)
                ->where('items.is_show', 1)->where('items.status', 1)->limit(8)->orderBy("items.id", "DESC")->get();

            if (!empty($related_items)) {
                foreach ($related_items as $key => $ri) {
                    $url = ($ri->image_path != "" || $ri->image_path != null) ? env('APP_URL') . Storage::url($ri->image_path) : "";
                    $ri['item_image'] = [
                        'url' => $url,
                        'name' => $ri->image_name
                    ];
                    $ri->encrypt_id = encryptID($ri->id, 'e');
                    unset($ri->image_path);
                    unset($ri->image_name);
                }
            }
            $response['related_items'] = $related_items;
            //End Related items

            $favourite = false;
            if ($user_id) {
                $favouritedata = Favourite::where('item_id', $item->id)->where('user_id', $user_id)->first();
                $favourite = ($favouritedata) ? true : false;
            }

            $response['item'] = $item;
            $response["favourite"] = $favourite;
            $response['item']['category_name'] = $category_name;
            $response['other_specifications'] = $item->itemSpecifications;
            $response['price_breakdowns'] = $item->itemBreakdowns;
            $response['item_image']['item_image'] = $mainImages;
            $response['other_image']['other_image'] = $otherImages;

            unset($item->itemSpecifications);
            unset($item->itemBreakdowns);


            return $this->responseAPI(true, "Product get successfully", 200, $response);
        } catch (\Exception $e) {
            return $this->responseAPI(false, $e->getMessage(), 200);
        }
    }

    public function getContact(Request $request)
    {
        try {
            $response = [];
            $admin_id = encryptID($request->header('Admin-EncryptId'), 'd');
            $website = Website::where("admin_id", $admin_id)->first();

            unset($website->aboutImages);
            unset($website->featureImages);
            unset($website->bannerImages);
            unset($website->logoImages);

            $response['contact'] = $website;
            return $this->responseAPI(true, "Contact get successfully", 200, $response);
        } catch (\Exception $e) {
            return $this->responseAPI(false, $e->getMessage(), 200);
        }
    }

    public function enquirySave(Request $request)
    {
        try {
            $response = [];
            $admin_id = encryptID($request->header('Admin-EncryptId'), 'd');
            if ($admin_id == null || $admin_id == "") {
                $response['message'] = ["Error" => ["Admin Id Required"]];
                successVisitorLog("Enquiry", "Enquiry-Save", "EnquiryAdminId",  null, $response['message']['Error'][0]);
                return $this->responseAPI(false, $response['message'], 200);
            }

            if ($request->currentEncryptID == null || $request->currentEncryptID == "") {
                successVisitorLog("Enquiry", "Enquiry-Save", "Admin~id~" . $admin_id,  null, "Product Id Required");
                return $this->responseAPI(false, "Product Id Required", 200);
            }
            $item_id = encryptID($request->currentEncryptID, 'd');
            $user_id = ($request->userEncryptID) ? encryptID($request->userEncryptID, 'd') : null;
            if ($user_id == null || $user_id == "") {
                $response['message'] = ["Error" => ["User Login Required"]];
                successVisitorLog("Enquiry", "Enquiry-Save", "Admin~id~" . $admin_id,  null, $response['message']['Error'][0]);
                return $this->responseAPI(false, $response['message'], 200);
            }
            if ($request->email == null || $request->email == "") {
                $response['message'] = ["Error" => ["Email Id Required"]];
                successVisitorLog("Enquiry", "Enquiry-Save", "Admin~id~" . $admin_id,  null, $response['message']['Error'][0]);
                return $this->responseAPI(false, $response['message'], 200);
            }

            $enquiry = Enquiry::where("item_id", $item_id)->where("email", $request->email)->first();

            if ($enquiry && $user_id) {
                $enquiry = Enquiry::where("item_id", $item_id)->where("user_id", $user_id)->first();
            }
            if ($enquiry) {
                $response['message'] = ["Error" => ["Item have Already Enquired"]];
                successVisitorLog("Enquiry", "Enquiry-Save", "Admin~id~" . $admin_id,  null, $response['message']['Error'][0]);
                return $this->responseAPI(false, $response['message'], 200);
            }
            $count = ($request->count) ? $request->count : 1;
            $comment = ($request->comment) ? $request->comment : null;
            $phone_no = ($request->phone_no) ? $request->phone_no : null;
            $email = $request->email;

            $data = ['admin_id' => $admin_id, 'user_id' => $user_id, 'email' => $email, 'item_id' => $item_id, 'count' => $count, 'comment' => $comment, 'phone_no' => $phone_no];

            $data['code'] = Enquiry::getCode();

            $new_enquiry = new Enquiry();
            $new_enquiry->fill($data);
            $new_enquiry->save();

            $message = ($new_enquiry) ? "Item have enquired" : "Something went wrong";
            if ($new_enquiry) {
                successVisitorLog("Enquiry", "Enquiry-Save", "Admin~id~" . $admin_id,  $new_enquiry->id, $message);
                $admin_user_id = Admin::where('id', $admin_id)->value('user_id');
                $item_name = Item::where('admin_id', $admin_id)->where('id', $item_id)->value('name');
                SaveNotification::dispatch([
                    'domain_id' => null,
                    'admin_id' => $admin_id,
                    'sender_id' => $user_id,
                    'menu' => "enquiries",
                    'menu_id' => null,
                    'receiver_id' => $admin_user_id,
                    'message' => sprintf(config('const.enquiry.message'), $item_name),
                    'link' => config('const.enquiry.link'),
                ]);
            } else {
                errorVisitorLog("Enquiry", "Enquiry-Save", "Admin~id~" . $admin_id,  null, $message);
            }
            return $this->responseAPI(true, $message, 200);
        } catch (\Exception $e) {
            errorVisitorLog("Enquiry", "Enquiry-Save", "Enquiry",  null, $e->getMessage());
            return $this->responseAPI(false, $e->getMessage(), 200);
        }
    }

    public function enquiryList(Request $request)
    {
        try {
            $search_word = $request->search_word;
            $admin_id = encryptID($request->header('Admin-EncryptId'), 'd');
            $user_id = ($request->userEncryptID) ? encryptID($request->userEncryptID, 'd') : null;

            if (!$user_id || $user_id == null) {
                $response['data'] = [];
                $response['totalCount'] = 0;
                return $this->responseAPI(true, "No Records Found", 200, $response);
            }

            $limit = $request->itemPerPage;
            $offset = $request->offset;
            $totalCount = 0;

            $datas =
                Enquiry::select(
                    "enquiries.*",
                    DB::raw('enquiries.code as code'),
                    DB::raw('items.name as product_name'),
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
                ->when($search_word, function ($query, $search_word) {
                    $query->where(function ($whr_query) use ($search_word) {
                        $whr_query->where('enquiries.name', 'like', '%' . $search_word . '%');
                        //   ->orWhere('votes', '>', 50);
                    });
                })
                ->where('items.deleted_at', null)
                ->where('items.admin_id', $admin_id)
                ->where('enquiries.admin_id', $admin_id)
                ->where('enquiries.user_id', $user_id)
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

    public function favouriteSave(Request $request)
    {
        try {
            $response = [];
            $admin_id = encryptID($request->header('Admin-EncryptId'), 'd');
            $item_id = encryptID($request->currentEncryptID, 'd');
            $user_id = ($request->userEncryptID) ? encryptID($request->userEncryptID, 'd') : null;
            if ($admin_id == null || $admin_id == "") {
                $response['message'] = ["Error" => ["Admin Id Required"]];
                successVisitorLog("Favourite", "Favourite-Save", "FavouriteAdminId",  null, $response['message']['Error'][0]);
                return $this->responseAPI(false, $response['message'], 200);
            }

            if ($request->currentEncryptID == null || $request->currentEncryptID == "") {
                successVisitorLog("Favourite", "Favourite-Save", "Admin~id~" . $admin_id,  null, "Product Id Required");
                return $this->responseAPI(false, "Product Id Required", 200);
            }

            if ($user_id == null || $user_id == "") {
                $response['message'] = ["Error" => ["User Login Required"]];
                successVisitorLog("Favourite", "Favourite-Save", "Admin~id~" . $admin_id,  null, $response['message']['Error'][0]);
                return $this->responseAPI(false, $response['message'], 200);
            }
            $user = User::findOrFail($user_id);



            $favourite = Favourite::where("item_id", $item_id)->where("user_id", $user_id)->first();
            if ($favourite) {
                Favourite::where("item_id", $item_id)->where("user_id", $user_id)->delete();
                $response["favourite"] = false;
                successVisitorLog("Favourite", "Favourite-Save", "Admin~id~" . $admin_id,  $item_id, "Item removed from favourite list");
                return $this->responseAPI(true, "Item removed from favourite list", 200, $response);
            }

            $email = $user->email;
            $data = ['admin_id' => $admin_id, 'user_id' => $user_id, 'email' => $email, 'item_id' => $item_id];

            $new_favourite = new Favourite();
            $new_favourite->fill($data);
            $new_favourite->save();
            $response["favourite"] = true;

            $message = ($new_favourite) ? "Item have added in favourite list" : "Something went wrong";
            if ($new_favourite) {
                successVisitorLog("Favourite", "Favourite-Save", "Admin~id~" . $admin_id,  $new_favourite->id, $message);
                $admin_user_id = Admin::where('id', $admin_id)->value('user_id');
                $item_name = Item::where('admin_id', $admin_id)->where('id', $item_id)->value('name');
                SaveNotification::dispatch([
                    'domain_id' => null,
                    'admin_id' => $admin_id,
                    'sender_id' => $user_id,
                    'menu' => "favourites",
                    'menu_id' => $item_id,
                    'receiver_id' => $admin_user_id,
                    'message' => sprintf(config('const.favourite.message'), $item_name),
                    'link' => config('const.favourite.link'),
                ]);
            } else {
                errorVisitorLog("Favourite", "Favourite-Save", "Admin~id~" . $admin_id,  null, $message);
            }
            return $this->responseAPI(true, $message, 200, $response);
        } catch (\Exception $e) {
            errorVisitorLog("Favourite", "Favourite-Save", "Favourite",  null, $e->getMessage());
            return $this->responseAPI(false, $e->getMessage(), 200);
        }
    }

    public function subscribeSave(Request $request)
    {
        try {
            $response = [];

            $admin_id = encryptID($request->header('Admin-EncryptId'), 'd');
            $user_id = ($request->userEncryptID) ? encryptID($request->userEncryptID, 'd') : null;
            if ($admin_id == null || $admin_id == "") {
                $response['message'] = ["Error" => ["Admin Id Required"]];
                successVisitorLog("Subscribe", "Subscribe-Save", "SubscribeAdminId",  null, $response['message']['Error'][0]);
                return $this->responseAPI(false, $response['message'], 200);
            }

            if ($request->email == null || $request->email == "") {
                $response['message'] = ["Error" => ["Email Required"]];
                successVisitorLog("Subscribe", "Subscribe-Save", "Admin~id~" . $admin_id,  null, $response['message']['Error'][0]);
                return $this->responseAPI(false, $response['message'], 200);
            }
            $email = $request->email;

            $subscribe = Subscribe::where("admin_id", $admin_id)->where("email", $email)->first();
            if ($subscribe) {
                $response['subscribed'] = true;
                successVisitorLog("Subscribe", "Subscribe-Save", "Admin~id~" . $admin_id,  $email, "Email Already have subscribed");
                return $this->responseAPI(true, "Email Already have subscribed", 200, $response);
            }

            $data = ['admin_id' => $admin_id, 'email' => $email];

            $new_subscribe = new Subscribe();
            $new_subscribe->fill($data);
            $new_subscribe->save();
            $response['subscribed'] = false;

            $message = ($new_subscribe) ? "Email successfully have subscribed" : "Something went wrong";
            if ($new_subscribe) {
                successVisitorLog("Subscribe", "Subscribe-Save", "Admin~id~" . $admin_id,  $new_subscribe->id, $message);
                $admin_user_id = Admin::where('id', $admin_id)->value('user_id');
                SaveNotification::dispatch([
                    'domain_id' => null,
                    'admin_id' => $admin_id,
                    'sender_id' => $user_id,
                    'menu' => "subscribes",
                    'menu_id' => null,
                    'receiver_id' => $admin_user_id,
                    'message' => sprintf(config('const.subscribe.message'), $email),
                    'link' => config('const.subscribe.link'),
                ]);
            } else {
                errorVisitorLog("Subscribe", "Subscribe-Save", "Admin~id~" . $admin_id,  null, $message);
            }
            return $this->responseAPI(true, $message, 200, $response);
        } catch (\Exception $e) {
            errorVisitorLog("Subscribe", "Subscribe-Save", "Subscribe",  null, $e->getMessage());
            return $this->responseAPI(false, $e->getMessage(), 200);
        }
    }

    public function favouriteList(Request $request)
    {
        try {
            $search_word = $request->search_word;
            $admin_id = encryptID($request->header('Admin-EncryptId'), 'd');
            $user_id = ($request->userEncryptID) ? encryptID($request->userEncryptID, 'd') : null;

            if (!$user_id || $user_id == null) {
                $response['data'] = [];
                $response['totalCount'] = 0;
                return $this->responseAPI(true, "No Records Found", 200, $response);
            }

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
                ->when($search_word, function ($query, $search_word) {
                    $query->where(function ($whr_query) use ($search_word) {
                        $whr_query->where('favourites.name', 'like', '%' . $search_word . '%');
                        //   ->orWhere('votes', '>', 50);
                    });
                })
                ->where('items.deleted_at', null)
                ->where('items.admin_id', $admin_id)
                ->where('favourites.admin_id', $admin_id)
                ->where('favourites.user_id', $user_id)
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


    public function messageSave(Request $request)
    {
        try {
            $response = [];

            $admin_id = encryptID($request->header('Admin-EncryptId'), 'd');
            $user_id = ($request->userEncryptID) ? encryptID($request->userEncryptID, 'd') : null;
            if ($admin_id == null || $admin_id == "") {
                $response['message'] = ["Error" => ["Admin Id Required"]];
                successVisitorLog("Message", "Message-Save", "MessageAdminId",  null, $response['message']['Error'][0]);
                return $this->responseAPI(false, $response['message'], 200);
            }

            if ($request->email == null || $request->email == "") {
                $response['message'] = ["Error" => ["Email Required"]];
                successVisitorLog("Message", "Message-Save", "Admin~id~" . $admin_id,  null, $response['message']['Error'][0]);
                return $this->responseAPI(false, $response['message'], 200);
            }
            $email = $request->email;
            $message = $request->message;

            $data = ['admin_id' => $admin_id, 'email' => $email, 'message' => $message];

            $data['code'] = Message::getCode();

            $new_message = new Message();
            $new_message->fill($data);
            $new_message->save();

            $website = Website::where("admin_id", $admin_id)->first();

            $message = ($new_message) ? "Your message have successfully sent to " . $website->company_name : "Something went wrong";
            if ($new_message) {
                successVisitorLog("Message", "Message-Save", "Admin~id~" . $admin_id,  $new_message->id, $message);
                $admin_user_id = Admin::where('id', $admin_id)->value('user_id');
                SaveNotification::dispatch([
                    'domain_id' => null,
                    'admin_id' => $admin_id,
                    'sender_id' => $user_id,
                    'menu' => "messages",
                    'menu_id' => null,
                    'receiver_id' => $admin_user_id,
                    'message' => sprintf(config('const.message.message'), $email),
                    'link' => config('const.message.link'),
                ]);
            } else {
                errorVisitorLog("Message", "Message-Save", "Admin~id~" . $admin_id,  null, $message);
            }
            return $this->responseAPI(true, $message, 200, $response);
        } catch (\Exception $e) {
            errorVisitorLog("Message", "Message-Save", "Message",  null, $e->getMessage());
            return $this->responseAPI(false, $e->getMessage(), 200);
        }
    }
}
