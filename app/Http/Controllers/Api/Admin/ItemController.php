<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Http\Requests\ItemRequest;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use App\Models\Item;
use App\Models\ItemBreakdown;
use App\Models\ItemImage;
use App\Models\ItemSpecification;
use Illuminate\Http\UploadedFile;
use App\Services\FileService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;


class ItemController extends BaseController
{
    //
    protected $fileservice;
    protected $usercontroller;

    public function __construct()
    {
        $this->middleware('role:admin');
        $this->fileservice = new FileService();
    }


    public function list(Request $request)
    {
        try {
            $search_word = $request->search_word;
            $category_id = $request->category_id;
            $admin_id = Auth::user()->admin->id;

            $limit = $request->itemPerPage;
            $offset = $request->offset;

            $totalCount = 0;

            $datas =
                Item::select(
                    "items.*",
                    DB::raw('categories.name as category_name'),
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
                        $whr_query->where('name', 'like', '%' . $search_word . '%');
                        //   ->orWhere('votes', '>', 50);
                    });
                })
                ->when($category_id, function ($query, $category_id) {
                    $query->where("categories.id", $category_id);
                })->where('items.admin_id', $admin_id);


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


            return $this->responseAPI(true, "Data get successfully", 200, $response);
        } catch (\Exception $e) {
            return $this->responseAPI(false, $e->getMessage(), 200);
        }
    }


    public function create(ItemRequest $request)
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

            $data['code'] = Item::getCode();

            $item = Item::create($data);
            $item->save();

            $message = "Item Datas Saved Successfully";


            if (!empty($request->item_image)) {
                $this->uploadImages($request->item_image, $item);
            }

            if (!empty($request->other_image)) {
                $this->uploadImages($request->other_image, $item, "other");
            }

            $this->updateSpecifications($request->other_specifications, $request->delete_specifications_ids, $item->id);

            $this->updatePricebreakdowns($request->price_breakdowns, $request->delete_pricebreakdowns_ids, $item->id);


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
            $admin_id = Auth::user()->admin->id;

            $response = [];

            $item = Item::where('admin_id', $admin_id)->findOrFail($id);

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

            $response['item'] = $item;
            $response['item']['category_name'] = $item->category->name;
            $response['other_specifications'] = $item->itemSpecifications;
            $response['price_breakdowns'] = $item->itemBreakdowns;
            $response['item_image']['item_image'] = $mainImages;
            $response['other_image']['other_image'] = $otherImages;

            unset($item->itemSpecifications);
            unset($item->itemBreakdowns);


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

    public function update(ItemRequest $request)
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

            $item = Item::updateOrCreate(["id" => $id], $data);
            // $item->save();

            $message = "Item Datas Updated Successfully";

            if (!empty($request->deleteImages)) {
                $item_images = ItemImage::whereIn('id', $request->deleteImages)->get();
                ItemImage::whereIn('id', $request->deleteImages)->delete();
                $this->fileservice->remove_file_attachment($item_images, config('const.item'));
            }

            if (!empty($request->item_image)) {
                $this->uploadImages($request->item_image, $item);
            }

            if (!empty($request->other_image)) {
                $this->uploadImages($request->other_image, $item, "other");
            }

            $this->updateSpecifications($request->other_specifications, $request->delete_specifications_ids, $item->id);

            $this->updatePricebreakdowns($request->price_breakdowns, $request->delete_pricebreakdowns_ids, $item->id);

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

    public function delete($encrypt_id)
    {
        try {
            if ($encrypt_id == null || $encrypt_id == '') {
                return $this->responseAPI(false, "Invaid Data", 200);
            }
            $id = encryptID($encrypt_id, 'd');
            $admin_id = Auth::user()->admin->id;

            $images = ItemImage::where('item_id', $id)->get();
            ItemImage::where('item_id', $id)->delete();
            $this->fileservice->remove_file_attachment($images);
            $delete = Item::where('admin_id', $admin_id)->findOrFail($id)->delete();
            if ($delete) {
                $message = "Item data deleted successfully";
                return $this->responseAPI(true, $message, 200);
            } else {
                $message = "Something went wrong";
                return $this->responseAPI(false, $message, 200);
            }
        } catch (\Exception $e) {
            return $this->responseAPI(false, $e->getMessage(), 200);
        }
    }

    public function updateSpecifications($specifications, $deleteIds, $item_id)
    {
        if (!empty($deleteIds)) {
            ItemSpecification::whereIn('id', [$deleteIds])->delete();
        }
        if (!empty($specifications)) {
            foreach ($specifications as $key => $spec) {
                if ($spec['label_name'] == null || $spec['value'] == null) {
                    continue;
                }
                $data = [
                    'item_id' => $item_id,
                    'label_name' => $spec['label_name'],
                    'type' => "text",
                    'value' => $spec['value'],
                ];
                ItemSpecification::updateOrCreate(['id' => $spec['id']], $data);
            }
        }
    }

    public function updatePricebreakdowns($breakdowns, $deleteIds, $item_id)
    {
        if (!empty($deleteIds)) {
            ItemBreakdown::whereIn('id', [$deleteIds])->delete();
        }
        if (!empty($breakdowns)) {
            foreach ($breakdowns as $key => $break) {
                if ($break['label_name'] == null || $break['value'] == null) {
                    continue;
                }
                $data = [
                    'item_id' => $item_id,
                    'label_name' => $break['label_name'],
                    'value' => $break['value'],
                ];
                ItemBreakdown::updateOrCreate(['id' => $break['id']], $data);
            }
        }
    }


    public function uploadImages($item_image, $item, $type = "main")
    {
        if (!empty($item_image)) {

            foreach ($item_image as $key => $image) {
                if ($image instanceof UploadedFile) {
                    if ($type == "main") {
                        ItemImage::whereIn('item_id', [$item->id])->where('type', "main")->delete();
                    }
                    $fileUpload = $this->fileservice->upload($image, config('const.item'), $item->code);
                    $url = config('const.item') . "/" . $fileUpload->getBaseName();
                    $img_name = $image->getClientOriginalName();
                    // $request->file('file')->getSize();
                    $data = [
                        'item_id' => $item->id,
                        'name' => $img_name,
                        'path' => $url,
                        'type' => $type,
                        'created_by' => $item->created_by,
                        'updated_by' => $item->updated_by,
                    ];
                    $item_image = $item->itemImages()->create($data);
                    $size = $item_image->getFileSize();
                    ItemImage::where('id', $item_image->id)->update(['size' => $size]);
                }
            }
        }
    }

    public function getCategoryList($selectCondition)
    {
        $admin_id = Auth::user()->admin->id;
        
        $datas = DB::table('categories')->selectRaw('id as value, name as label')
            ->when(($selectCondition == "wt"), function ($query) {
                $query->where('deleted_at', null)
                    ->where('status', 1);
            })->where('categories.admin_id', $admin_id)
            ->get();
        return $this->responseAPI(true, "Category get successfully", 200, $datas);
    }
}
