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
                        $whr_query->where('name', 'like', '%' . $search_word . '%');
                        //   ->orWhere('votes', '>', 50);
                    });
                });


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
                'admin_id' => $auth->id,
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

            $category = Category::findOrFail($id);

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
                'admin_id' => $auth->id,
                'updated_by' => $auth->id,
            ]);

            $data = $request->all();

            DB::beginTransaction();

            $category = Category::updateOrCreate(["id" => $id], $data);
            // $category->save();

            $message = "Category Datas Updated Successfully";

            if (!empty($request->deleteImages)) {
                CategoryImage::whereIn('id', $request->deleteImages)->delete();
            }

            if (!empty($request->category_image)) {
                // CategoryImage::whereIn('category_id', [$id])->delete();
                $this->uploadImages($request->category_image, $category);
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

    public function delete($encrypt_id){
        try {
            if ($encrypt_id == null || $encrypt_id == '') {
                return $this->responseAPI(false, "Invaid Data", 200);
            }
            $id = encryptID($encrypt_id, 'd');
            $delete = Category::findOrFail($id)->delete();
            if ($delete) {
                $message = "Category data deleted successfully";
                return $this->responseAPI(true, $message, 200);
            } else {
                $message = "Something went wrong";
                return $this->responseAPI(false, $message, 200);
            }
        } catch (\Exception $e) {
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
                    $category->categoryImages()->create($data);
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
