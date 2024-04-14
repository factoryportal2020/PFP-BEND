<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
// use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Services\FileService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\WebsiteRequest;
use Illuminate\Support\Facades\DB;
use App\Models\Website;
use App\Models\Temp;
use App\Models\WebsiteImage;
use App\Models\TempImage;
use App\Services\ImageService;

class WebsiteController extends BaseController
{
    //
    protected $fileservice;

    public function __construct()
    {
        $this->middleware('role:admin');
        $this->fileservice = new FileService();
    }


    public function create(WebsiteRequest $request)
    {
        try {
            $auth = Auth::user();
            $request->merge([
                'domain_id' => $auth->domain_id,
                'admin_id' => $auth->admin->id,
                'created_by' => $auth->id,
                'updated_by' => $auth->id,
                'launch_at' => null,
            ]);

            $data = $request->all();
            DB::beginTransaction();

            $data['code'] = Temp::getCode();

            // return $this->responseAPI(false, $data['code'], 200);


            $website = Temp::create($data);
            $website->save();

            if (!empty($request->logo_image)) {
                $this->uploadImages($request->logo_image, $website, "logo");
            }

            if (!empty($request->banner_image1) || $request->banner_title1 || $request->banner_caption1) {
                $banner_image_data1 = [$request->banner_title1, $request->banner_caption1];
                $this->uploadImages($request->banner_image1, $website, "banner1", $banner_image_data1);
                $this->uploadImageDatas($website, "banner1", $banner_image_data1);
            }
            if (!empty($request->banner_image2) || $request->banner_title2 || $request->banner_caption2) {
                $banner_image_data2 = [$request->banner_title2, $request->banner_caption2];
                $this->uploadImages($request->banner_image2, $website, "banner2", $banner_image_data2);
                $this->uploadImageDatas($website, "banner2", $banner_image_data2);
            }
            if (!empty($request->banner_image3) || $request->banner_title3 || $request->banner_caption3) {
                $banner_image_data3 = [$request->banner_title3, $request->banner_caption3];
                $this->uploadImages($request->banner_image3, $website, "banner3", $banner_image_data3);
                $this->uploadImageDatas($website, "banner3", $banner_image_data3);
            }

            if (!empty($request->about_image) || $request->about) {
                $about_image_data = [$request->about];
                $this->uploadImages($request->about_image, $website, "about", $about_image_data);
                $this->uploadImageDatas($website, "about", $about_image_data);
            }

            if (!empty($request->feature_image1)) {
                $this->uploadImages($request->feature_image1, $website, "feature1");
            }

            if (!empty($request->feature_image2)) {
                $this->uploadImages($request->feature_image2, $website, "feature2");
            }

            if (!empty($request->feature_image3)) {
                $this->uploadImages($request->feature_image3, $website, "feature3");
            }

            $message = "Website Datas Saved Successfully";

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


    public function get()
    {
        try {
            $response = [];
            $auth = Auth::user();
            $admin_id = $auth->admin->id;
            $website = Website::where("admin_id", $admin_id)->first();
            $website_encrypt_id = null;
            if ($website) {
                $website->oldsite_url = $website->site_url;
                $encrypt_id = encryptID($website->id);
                $website_encrypt_id = encryptID($website->id);
                $launch_at = $website->launch_at;
                if ($launch_at == null || !$launch_at) {
                    $website = Temp::where("admin_id", $admin_id)->first();
                    $website->oldsite_url = $website->site_url;
                    $encrypt_id = encryptID($website->id);
                }
            } else {
                $website = Temp::where("admin_id", $admin_id)->first();
                if (!$website) {
                    $website = new Website();
                    $encrypt_id = null;
                } else {
                    $encrypt_id = ($website) ? encryptID($website->id) : null;
                    $website->oldsite_url = $website->site_url;
                }
            }
            $logoImages = [];
            if (!empty($website->logoImages)) {
                foreach ($website->logoImages as $key => $image) {
                    $logoImages[] = [
                        'url' => env('APP_URL') . Storage::url($image->path), 'name' => $image->name, 'id' => $image->id,
                        'detail' => $image->detail
                    ];
                }
            }

            $aboutImages = [];
            if (!empty($website->aboutImages)) {
                foreach ($website->aboutImages as $key => $image) {
                    $aboutImages[] = [
                        'url' => env('APP_URL') . Storage::url($image->path), 'name' => $image->name, 'id' => $image->id,
                        'detail' => $image->detail
                    ];
                }
            }

            $featureImages = [];
            if (!empty($website->featureImages)) {
                foreach ($website->featureImages as $key => $image) {
                    $featureImages[$image->type] = ['url' => env('APP_URL') . Storage::url($image->path), 'name' => $image->name, 'id' => $image->id];
                }
            }

            $bannerImages = [];
            if (!empty($website->bannerImages)) {
                foreach ($website->bannerImages as $key => $image) {
                    $bannerImages[$image->type] = [
                        'url' => env('APP_URL') . Storage::url($image->path), 'name' => $image->name, 'id' => $image->id,
                        'title' => $image->title, 'caption' => $image->caption,
                    ];
                }
            }

            $defaultBanners = [];
            $defaultBannersFiles = Storage::files(config('const.banner'));
            if (!empty($defaultBannersFiles)) {
                foreach ($defaultBannersFiles as $key => $image) {
                    $i_name = str_replace(config('const.banner') . "/", "", $image);
                    // $i = str_replace(,"",$image);
                    $defaultBanners[] = [
                        'url' => env('APP_URL') . Storage::url($image), 'name' => $i_name, 'id' => null,
                        'public_url' => $image
                    ];
                }
            }

            $defaultAbouts = [];
            $defaultAboutsFiles = Storage::files(config('const.about'));
            if (!empty($defaultAboutsFiles)) {
                foreach ($defaultAboutsFiles as $key => $image) {
                    $i_name = str_replace(config('const.about') . "/", "", $image);
                    // $i = str_replace(,"",$image);
                    $defaultAbouts[] = [
                        'url' => env('APP_URL') . Storage::url($image), 'name' => $i_name, 'id' => null,
                        'public_url' => $image
                    ];
                }
            }


            $defaultFeatures = [];
            $defaultFeaturesFiles = Storage::files(config('const.feature'));
            if (!empty($defaultFeaturesFiles)) {
                foreach ($defaultFeaturesFiles as $key => $image) {
                    $i_name = str_replace(config('const.feature') . "/", "", $image);
                    // $i = str_replace(,"",$image);
                    $defaultFeatures[] = [
                        'url' => env('APP_URL') . Storage::url($image), 'name' => $i_name, 'id' => null,
                        'public_url' => $image
                    ];
                }
            }

            $response['defaultBanners']['defaultBanners'] = $defaultBanners;
            $response['defaultAbouts']['defaultAbouts'] = $defaultAbouts;
            $response['defaultFeatures']['defaultFeatures'] = $defaultFeatures;

            // $path_parts = Storage::size($defaultBanners[0]['public_url']);
            // $response['path_parts'] = $path_parts;

            unset($website->aboutImages);
            unset($website->featureImages);
            unset($website->bannerImages);
            unset($website->logoImages);

            $response['website'] = $website;
            $response['website']['encrypt_id'] = $encrypt_id;
            $response['website']['website_encrypt_id'] = $website_encrypt_id;
            $response['about_image']['about_image'] = $aboutImages;
            $response['logo_image']['logo_image'] = $logoImages;
            $response['website']['about'] =  ($aboutImages && count($aboutImages) > 0) ? $aboutImages[0]['detail'] : "";

            $response['banner_image1']['banner_image1'] = ($bannerImages && isset($bannerImages['banner1'])) ? [$bannerImages['banner1']] : [];
            $response['website']['banner_title1'] = ($bannerImages && isset($bannerImages['banner1'])) ? $bannerImages['banner1']['title'] : "";
            $response['website']['banner_caption1'] = ($bannerImages && isset($bannerImages['banner1'])) ? $bannerImages['banner1']['caption'] : "";

            $response['banner_image2']['banner_image2'] = ($bannerImages && isset($bannerImages['banner2'])) ? [$bannerImages['banner2']] : [];
            $response['website']['banner_title2'] = ($bannerImages && isset($bannerImages['banner2'])) ? $bannerImages['banner2']['title'] : "";
            $response['website']['banner_caption2'] = ($bannerImages && isset($bannerImages['banner2'])) ? $bannerImages['banner2']['caption'] : "";

            $response['banner_image3']['banner_image3'] = ($bannerImages && isset($bannerImages['banner3'])) ? [$bannerImages['banner3']] : [];
            $response['website']['banner_title3'] = ($bannerImages && isset($bannerImages['banner3'])) ? $bannerImages['banner3']['title'] : "";
            $response['website']['banner_caption3'] = ($bannerImages && isset($bannerImages['banner3'])) ? $bannerImages['banner3']['caption'] : "";

            $response['feature_image1']['feature_image1'] = ($featureImages && isset($featureImages["feature1"])) ? [$featureImages["feature1"]] : [];
            $response['feature_image2']['feature_image2'] = ($featureImages && isset($featureImages["feature2"])) ? [$featureImages["feature2"]] : [];
            $response['feature_image3']['feature_image3'] = ($featureImages && isset($featureImages["feature3"])) ? [$featureImages["feature3"]] : [];

            return $this->responseAPI(true, $admin_id, 200, $response);
        } catch (\Exception $e) {
            return $this->responseAPI(false, $e->getLine(), 200);
        }
    }

    public function update(WebsiteRequest $request)
    {
        try {
            $encrypt_id = $request->encrypt_id;
            $id = encryptID($encrypt_id, 'd');
            $website_encrypt_id = $request->website_encrypt_id;
            // return $this->responseAPI(true, encryptID($website_encrypt_id, 'd'), 200);

            $auth = Auth::user();
            $request->merge([
                'domain_id' => $auth->domain_id,
                'admin_id' => $auth->admin->id,
                'updated_by' => $auth->id,
                'launch_at' => null,
            ]);

            $data = $request->all();

            DB::beginTransaction();
            // Temp::where('admin_id', $auth->admin->id)->forceDelete();

            $website = Temp::updateOrCreate(["id" => $id], $data);

            $websiteUpdate_id = null;
            $websiteUpdate = Website::where("id", $id)->first();
            if ($websiteUpdate) {
                Website::findOrFail($id)->update(['launch_at' => null]);
                $websiteUpdate_id = $id;
                // $tempImageIds = WebsiteImage::where('website_id', $websiteUpdate->id)->update(['website_id' => $website->id]);
                // $website = $websiteUpdate;
            }
            $website->table_name = "Temp";



            if (!empty($request->banner_image1) || $request->banner_title1 || $request->banner_caption1) {
                $banner_image_data1 = [$request->banner_title1, $request->banner_caption1];
                $this->uploadImages($request->banner_image1, $website, "banner1", $banner_image_data1);
                $this->uploadImageDatas($website, "banner1", $banner_image_data1, $websiteUpdate_id);
            }
            if (!empty($request->banner_image2) || $request->banner_title2 || $request->banner_caption2) {
                $banner_image_data2 = [$request->banner_title2, $request->banner_caption2];
                $this->uploadImages($request->banner_image2, $website, "banner2", $banner_image_data2);
                $this->uploadImageDatas($website, "banner2", $banner_image_data2, $websiteUpdate_id);
            }
            if (!empty($request->banner_image3) || $request->banner_title3 || $request->banner_caption3) {
                $banner_image_data3 = [$request->banner_title3, $request->banner_caption3];
                $this->uploadImages($request->banner_image3, $website, "banner3", $banner_image_data3);
                $this->uploadImageDatas($website, "banner3", $banner_image_data3, $websiteUpdate_id);
            }

            if (!empty($request->about_image) || $request->about) {
                $about_image_data = [$request->about];
                $this->uploadImages($request->about_image, $website, "about", $about_image_data);
                $this->uploadImageDatas($website, "about", $about_image_data, $websiteUpdate_id);
            }

            if (!empty($request->logo_image)) {
                $this->uploadImages($request->logo_image, $website, "logo");
                $this->uploadImageDatas($website, "logo", [], $websiteUpdate_id);
            }

            if (!empty($request->feature_image1)) {
                $this->uploadImages($request->feature_image1, $website, "feature1");
            }
            if (!empty($request->feature_image2)) {
                $this->uploadImages($request->feature_image2, $website, "feature2");
            }
            if (!empty($request->feature_image3)) {
                $this->uploadImages($request->feature_image3, $website, "feature3");
            }

            if (!empty($request->deleteImages)) {
                $images = TempImage::whereIn('id', $request->deleteImages)->get();
                if (!empty($images) && ($website_encrypt_id == null || !$website_encrypt_id)) {
                    TempImage::whereIn('id', $request->deleteImages)->update(['delete_at' => date("Y-m-d H:i:s")]);
                    $this->fileservice->remove_file_attachment($images, config('const.website'));
                }
                if ($website_encrypt_id) {
                    $website_id = encryptID($website_encrypt_id, 'd');
                    $deletingImages = WebsiteImage::whereIn('id', $request->deleteImages)->get();
                    if (!empty($deletingImages)) {
                        foreach ($deletingImages as $key => $deletingImage) {
                            TempImage::where('website_id', $website->id)->where('type', $deletingImage['type'])->update(['delete_at' => date("Y-m-d H:i:s")]);
                            WebsiteImage::where('website_id', $website_id)->where('type', $deletingImage['type'])->forceDelete();
                        }
                        $this->fileservice->remove_file_attachment($deletingImages, config('const.website'));
                    }
                }
            }

            $message = "Website Datas Updated Successfully";

            DB::commit();
            return $this->responseAPI(true, $message, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof HttpResponseException) {
                return $e->getResponse();
            }
            return $this->responseAPI(false, $e->getMessage() . $e->getLine(), 200);
        }
    }

    public function launchAtUpdate(Request $request)
    {
        try {
            $encrypt_id = $request->encrypt_id;
            $website_encrypt_id = $request->website_encrypt_id;

            $id = encryptID($encrypt_id, 'd');
            $auth = Auth::user();

            $data = [
                'updated_by' => $auth->id,
                'launch_at' => date("Y-m-d H:i:s")
            ];

            DB::beginTransaction();
            $temp = $temp_array = Temp::where("id", $id)->first();
            $website_id = $id;
            if ($website_encrypt_id) {
                $website_id = encryptID($website_encrypt_id, 'd');
                $websiteCheck = Website::where("id", $website_id)->first();
                $launch_at = $websiteCheck->launch_at;
                if ($launch_at != null) {
                    $message = "Your Website Launched Successfully";
                    return $this->responseAPI(true, $message, 200);
                }
            }
            $temp_array = $data + $temp_array->toArray();

            $website = Website::where("id", $website_id)->first();
            if ($website) {
                Website::findOrFail($website_id)->update($temp_array);
            } else {
                $website = new Website();
                $website->fill($temp_array);
                $website->save();
            }
            $temp_image_array = TempImage::where('website_id', $id)->get();
            if (!empty($temp_image_array)) {
                $temp_image_data = [];
                foreach ($temp_image_array->toArray() as $key => $value) {
                    $temp_image_data = $value;
                    $temp_image_data['website_id'] = $website->id;
                    unset($temp_image_data['created_at']);
                    unset($temp_image_data['updated_at']);
                    $check_image = WebsiteImage::where('website_id', $website->id)->where('type', $value['type'])->first();
                    // return $this->responseAPI(false, $temp_image_data, 200);

                    if ($check_image) {
                        WebsiteImage::where('website_id', $website_id)->where('type', $value['type'])->update($temp_image_data);
                    } else {
                        $websiteImage = new WebsiteImage();
                        $websiteImage->fill($temp_image_data);
                        $websiteImage->save();
                    }
                }
            }

            $deleted_WebsiteImages = WebsiteImage::where('website_id', $website_id)->where('delete_at', '!=', null)->get();
            $this->fileservice->remove_file_attachment($deleted_WebsiteImages, config('const.website'));
            WebsiteImage::where('website_id', $website_id)->where('delete_at', '!=', null)->delete();


            Temp::where('id', $id)->forceDelete();
            TempImage::where('website_id', $id)->forceDelete();

            $message = ($website) ? "Your Website Launched Successfully" : "Something went wrong";

            DB::commit();
            return $this->responseAPI(true, $message, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof HttpResponseException) {
                return $e->getResponse();
            }
            return $this->responseAPI(false, $e->getMessage() . $e->getLine(), 200);
        }
    }

    public function uploadImages($website_image, $website, $type, $image_data = [])
    {
        if (!empty($website_image)) {
            foreach ($website_image as $key => $image) {
                if ($image instanceof UploadedFile) {
                    $fileUpload = $this->fileservice->upload($image, config('const.website'), $website->code);
                    $url = config('const.website') . "/" . $fileUpload->getBaseName();
                    $img_name = $image->getClientOriginalName();

                    $data = [
                        'website_id' => $website->id,
                        'name' => $img_name,
                        'path' => $url,
                        'type' => $type,
                        'created_by' => $website->created_by,
                        'updated_by' => $website->updated_by,
                    ];

                    $website_image = $website->websiteImages()->create($data);
                    $size = $website_image->getFileSize();
                    TempImage::where('id', $website_image->id)->update(['size' => $size]);
                } else {
                    if ($image['url'] != "" && $image['url'] != null && isset($image['public_url'])) {

                        $image_info = pathinfo($image['url']);
                        $img_name = $image_info['basename'];
                        $img_extension = $image_info['extension'];
                        $img_size = Storage::size($image['public_url']);

                        if ($type == "about") {
                            $publicPath = config('const.about') . "/" . $img_name;
                        } elseif ($type == "feature1" || $type == "feature2" || $type == "feature3") {
                            $publicPath = config('const.feature') . "/" . $img_name;
                        } else {
                            $publicPath = config('const.banner') . "/" . $img_name;
                        }

                        $img_new_name = $this->fileservice->getFilename($website->code, $img_extension);
                        $new_websitePath = config('const.website') . "/" . $img_new_name;

                        Storage::copy($publicPath, $new_websitePath);

                        $data = [
                            'website_id' => $website->id,
                            'name' => $img_name,
                            'path' => $new_websitePath,
                            'type' => $type,
                            'size' => $img_size,
                            'created_by' => $website->created_by,
                            'updated_by' => $website->updated_by,
                        ];


                        $images = TempImage::where('website_id', $website->id)->where('type', $type)->get();
                        TempImage::where('website_id', $website->id)->where('type', $type)->delete();
                        $this->fileservice->remove_file_attachment($images, config('const.website'));
                        $website_image = $website->websiteImages()->create($data);
                    }
                }
            }
        }
    }

    public function uploadImageDatas($website, $type, $image_data = [], $websiteUpdate_id = null)
    {

        $data = [
            'updated_by' => $website->updated_by,
        ];

        if ($type == "banner1" || $type == "banner2" || $type == "banner3") {
            $data['title'] = $image_data[0];
            $data['caption'] = $image_data[1];
        }

        if ($type == "about") {
            $data['detail'] = $image_data[0];
        }

        $check_temp_image = TempImage::where('website_id', $website->id)->where('type', $type)->first();
        if ($check_temp_image) {
            TempImage::where('website_id', $website->id)
                ->where('type', $type)
                ->update($data);
        } else {
            if ($websiteUpdate_id) {
                $temp_image_array = WebsiteImage::where('website_id', $websiteUpdate_id)->where('type', $type)->first();
                if ($temp_image_array) {
                    $temp_image_data = $temp_image_array->toArray();
                    $temp_image_data = array_merge($temp_image_data, $data);
                    $temp_image_data['website_id'] = $website->id;
                    $temp_image_data['type'] = $type;
                    $temp_image_data['created_by'] = Auth::user()->id;
                    $temp_image_data['updated_by'] = Auth::user()->id;
                    $TempImage = new TempImage();
                    $TempImage->fill($temp_image_data);
                    $TempImage->save();
                }
            } else {
                $TempImage = new TempImage();
                $data['website_id'] = $website->id;
                $data['type'] = $type;
                $data['created_by'] = Auth::user()->id;
                $data['updated_by'] = Auth::user()->id;
                $TempImage->fill($data);
                $TempImage->save();
            }
        }
    }

    public function getFile($url)
    {
        //get name file by url and save in object-file
        $path_parts = pathinfo($url);
        //get image info (mime, size in pixel, size in bits)
        // $newPath = $path_parts['dirname'] . '/tmp-files/';
        // if (!is_dir($newPath)) {
        //     mkdir($newPath, 0777);
        // }
        // $oldUrl = config('const.banner') . "/" . $path_parts['basename'];
        // $oldUrl = asset(config('const.banner') . "/" . $path_parts['basename']);
        // $newUrl = config('const.website') . "/new" . $path_parts['basename'];
        // storage::copy($oldUrl, $newUrl);
        // $imgSize = Storage::size($oldUrl);
        // $imgMime = Storage::mimeType($oldUrl);
        $file = new UploadedFile(
            $oldUrl,
            $path_parts['basename'],
            $imgMime,
            filesize($url),
            TRUE,
        );
        return $path_parts;
    }
}
