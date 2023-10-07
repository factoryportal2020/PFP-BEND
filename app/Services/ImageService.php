<?php

namespace App\Services;


use Illuminate\Support\Facades\Log;
use Illuminate\Filesystem\Filesystem;

class ImageService
{
    public function resize_images_logo($array_images,$column, $folder){
        try{
            $fileSystem = new Filesystem();
            $path_base = env('PATH_BASE');
            $folder_resized = $path_base."/resized";
            if (!$fileSystem->isDirectory($folder_resized)){
                $fileSystem->makeDirectory($folder_resized,0777);
            }
            $folder_check = $path_base."/resized/".$folder;
            if (!$fileSystem->isDirectory($folder_check)){
                $fileSystem->makeDirectory($folder_check,0777);
            }
            $destination_path_signage_check = $path_base."/resized_signage";
            if (!$fileSystem->isDirectory($destination_path_signage_check)){
                $fileSystem->makeDirectory($destination_path_signage_check,0777);
            }
            $folder_check_signage = $path_base."/resized_signage/".$folder;
            if (!$fileSystem->isDirectory($folder_check_signage)){
                $fileSystem->makeDirectory($folder_check_signage,0777);
            }

            foreach ($array_images as $image_need) {
                $image_file = $image_need[$column];
                if ($image_file) {
                    $target_path = $path_base.'/'.$image_file;
                    //check file co ton tai khong
                    if (!file_exists($target_path)) continue;
                    //$backup_path = $path_base."/original/".$image_file;
                    //signage
                    $width = 253;
                    $height = 120;
                    $destination_path_signage = $path_base."/resized_signage/".$image_file;
                    $this->process_image($target_path, $destination_path_signage, $width, $height);
                    //web
                    $width = 220;
                    $height = 176;
                    $destination_path = $path_base."/resized/".$image_file;
                    $this->process_image($target_path, $destination_path, $width, $height);
                    if (file_exists($destination_path)) {
                        copy($destination_path, $target_path);
                    }
                }
            }
        }catch (\Exception $exception){
            LogService::messageException($exception);
            return $exception;
        }
    }

    public function resize_images($array_images,$column, $folder, $width, $height){
        try{
            $fileSystem = new Filesystem();
            $path_base = env('PATH_BASE');
            $folder_resized = $path_base."/resized";
            if (!$fileSystem->isDirectory($folder_resized)){
                $fileSystem->makeDirectory($folder_resized,0777);
            }
            $folder_check = $path_base."/resized/".$folder;
            if (!$fileSystem->isDirectory($folder_check)){
                $fileSystem->makeDirectory($folder_check,0777);
            }
            foreach ($array_images as $image_need) {
                $image_file = $image_need[$column];
                if ($image_file) {
                    if ($folder == config('const.banner') ){
                        $target_path = $path_base.'/'.config('const.banner').'/'.$image_file;
                    }else{
                        $target_path = $path_base.'/'.$image_file;
                    }
                    //check file co ton tai khong
                    if (!file_exists($target_path)) continue;
                    $backup_path = $path_base."/original/".$image_file;
                    if ($folder == config('const.banner') ){
                        $destination_path = $path_base."/resized/".config('const.banner').'/'.$image_file;
                    }else{
                        $destination_path = $path_base."/resized/".$image_file;
                    }
                    $this->process_image($target_path, $destination_path, $width, $height);
                    if (file_exists($destination_path)) {
                        copy($destination_path, $target_path);
                    }
                }
            }
        }catch (\Exception $exception){
            LogService::messageException($exception);
            return $exception;
        }
    }

    public function process_image($target_path, $destination_path, $width, $height,$backup_path = null) {
        try{
            $ext = substr($target_path, strrpos($target_path, '.') + 1);
            if($backup_path && !copy($target_path, $backup_path)) {
                //echo "File copy error..\n";
                return;
            }

            $im = new \Imagick(); // Imagickオブジェクトを生成

            $im->readImage($target_path); // 入力画像パスを指定

            $width_org = $im->getImageWidth();
            $height_org = $im->getImageHeight();
            if ($width_org <= $width && $height_org <= $height) {
                //echo "small enough..\n";
                return;
            }
            //$im->adaptiveResizeImage($width, $height, true); // 縦横のピクセル指定
            $im->scaleImage($width, $height, true); // 縦横のピクセル指定
            $im->setImageResolution(72, 72);
            $im->setImageFormat($ext); // 出力フォーマットを指定
            if ($im->getImageOrientation() == 0) { //画像の向きが0の物だけ
                $im->stripImage();
            }
            $im->writeImage($destination_path);
            $im->clear();
            $im->destroy();
        }catch (\Exception $exception){
            LogService::messageException($exception);
        }

    }
}
