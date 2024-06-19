<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Http\Requests\CategoryRequest;
use Illuminate\Support\Facades\DB;
use App\Models\Category;
use App\Models\CategoryImage;
use Illuminate\Http\UploadedFile;
use App\Services\FileService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Image;

class CategoryController extends BaseController
{
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

            $limit = $request->itemPerPage;
            $offset = $request->offset;

            $admin_id = Auth::user()->admin_id;

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
                })->where('categories.admin_id', $admin_id);


            $totalCount = $datas->count();

            $datas->limit($limit)->orderBy("categories.id", "DESC");

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


            return $this->responseAPI(true, "Data get successfully", 200, $response);
        } catch (\Exception $e) {
            return $this->responseAPI(false, $e->getMessage(), 200);
        }
    }


    public function create(CategoryRequest $request)
    {
        try {
            $auth = Auth::user();
            $request->merge([
                'domain_id' => $auth->domain_id,
                'admin_id' => $auth->admin_id,
                'created_by' => $auth->id,
                'updated_by' => $auth->id,
            ]);

            $data = $request->all();


            DB::beginTransaction();

            $data['code'] = Category::getCode();

            $category = Category::create($data);
            $category->save();

            $message = "Category Datas Saved Successfully";


            if (!empty($request->category_image)) {
                $this->uploadImages($request->category_image, $category);
                successLog("Category", "Create-UploadImage", "CategoryImage",  $category->id, $message);
            }

            DB::commit();
            successLog("Category", "Create", "Category",  $category->id, $message);
            return $this->responseAPI(true, $message, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof HttpResponseException) {
                errorLog("Category", "Create", "Category",  null, $e->getResponse());
                return $e->getResponse();
            }
            errorLog("Category", "Create", "Category",  null, $e->getMessage());
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

            $admin_id = Auth::user()->admin_id;

            $response = [];

            $category = Category::where('admin_id', $admin_id)->findOrFail($id);

            $images = [];
            if (!empty($category->categoryImages)) {
                foreach ($category->categoryImages as $key => $image) {
                    $images[] = ['url' => env('APP_URL') . Storage::url($image->path), 'name' => $image->name, 'id' => $image->id];
                }
            }

            unset($category->categoryImages);

            $response['category'] = $category;
            $response['category']['items_count'] = $category->items->count();
            $response['category']['tasks_count'] = $category->tasks->count();
            unset($category->items);
            unset($category->tasks);

            $response['category_image']['category_image'] = $images;

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

    public function update(CategoryRequest $request)
    {
        try {
            $user_id = 1;
            $domain_id = 1;
            $encrypt_id = $request->encrypt_id;
            if ($encrypt_id == null || $encrypt_id == '') {
                return $this->responseAPI(false, "Invaid Data", 200);
            }

            $id = encryptID($encrypt_id, 'd');
            $auth = Auth::user();
            $request->merge([
                'domain_id' => $auth->domain_id,
                'admin_id' => $auth->admin_id,
                'updated_by' => $auth->id,
            ]);

            $data = $request->all();

            DB::beginTransaction();

            $category = Category::updateOrCreate(["id" => $id], $data);
            // $category->save();

            $message = "Category Datas Updated Successfully";

            if (!empty($request->deleteImages)) {
                $images = CategoryImage::whereIn('id', $request->deleteImages)->get();
                CategoryImage::whereIn('id', $request->deleteImages)->delete();
                $this->fileservice->remove_file_attachment($images, config('const.category'));
                successLog("Category", "update-image-delete", "CategoryImage",  implode("~", $request->deleteImages), "Category image deleted");
            }

            if (!empty($request->category_image)) {
                // CategoryImage::whereIn('category_id', [$id])->delete();
                $this->uploadImages($request->category_image, $category);
                successLog("Category", "update-image-upload", "CategoryImage",  $category->id, "Category image uploaded");
            }

            DB::commit();
            successLog("Category", "update", "Category",  $category->id, $message);
            return $this->responseAPI(true, $message, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof HttpResponseException) {
                errorLog("Category", "Update", "Category",  null, $e->getResponse());
                return $e->getResponse();
            }
            errorLog("Category", "Update", "Category",  null, $e->getMessage());
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
            $admin_id = Auth::user()->admin_id;
            // $images = CategoryImage::where('category_id', $id)->get();
            // CategoryImage::where('category_id', $id)->delete();
            // $this->fileservice->remove_file_attachment($images);
            $category = Category::where('admin_id', $admin_id)->findOrFail($id)->first();
            $delete = Category::where('admin_id', $admin_id)->findOrFail($id)->delete();
            if ($delete) {
                $message = "Category data deleted successfully";
                successLog("Category", "Delete", "Category",  $category->id, $message);
                return $this->responseAPI(true, $message, 200);
            } else {
                $message = "Something went wrong";
                errorLog("Category", "Delete", "Category",  $category->id, $message);
                return $this->responseAPI(false, $message, 200);
            }
        } catch (\Exception $e) {
            errorLog("Category", "Delete", "Category",  $category->id, $e->getMessage());
            return $this->responseAPI(false, $e->getMessage(), 200);
        }
    }

    public function uploadImages($category_image, $category)
    {
        if (!empty($category_image)) {
            foreach ($category_image as $key => $image) {
                if ($image instanceof UploadedFile) {
                    CategoryImage::whereIn('category_id', [$category->id])->delete();

                    // $img = Image::make($image->path());
                    // $img->resize(100, 100, function ($constraint) {
                    //     $constraint->aspectRatio();
                    // });

                    $fileUpload = $this->fileservice->upload($image, config('const.category'), $category->code);
                    $url = config('const.category') . "/" . $fileUpload->getBaseName();
                    $img_name = $image->getClientOriginalName();

                    $data = [
                        'category_id' => $category->id,
                        'name' => $img_name,
                        'path' => $url,
                        'created_by' => $category->created_by,
                        'updated_by' => $category->updated_by,
                    ];
                    $category_image = $category->categoryImages()->create($data);
                    $size = $category_image->getFileSize();
                    CategoryImage::where('id', $category_image->id)->update(['size' => $size]);
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
