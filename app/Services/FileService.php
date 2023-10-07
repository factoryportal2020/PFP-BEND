<?php

namespace App\Services;

class FileService
{
    public function upload($file, $pathFolder, $name, $index = '0')
    {
        $info = [];
        if (!is_null($file)) {
            $tmp = explode('.', $file->getClientOriginalName());
            $pathFile = config('app.path_base') . DIRECTORY_SEPARATOR . $pathFolder;
            // $nameRandom = round(microtime(true) * 1000) . $index .'_'. rand(10000,99999) . '.' . end($tmp);
            $nameRandom = round(microtime(true) * 1000) . $index . '_' . $name;
            $info = $file->move($pathFile, $nameRandom);
        }
        return $info;
    }

    public function upload_file_attachment($file, $index = '0')
    {
        $info = [];
        if (!is_null($file)) {
            $tmp = explode('.', $file->getClientOriginalName());
            $pathFile = config('app.path_base') . DIRECTORY_SEPARATOR . config('const.folder_upload_default') . DIRECTORY_SEPARATOR;
            $nameRandom = round(microtime(true) * 1000) . $index . '_' . rand(10000, 99999) . '.' . end($tmp);
            $info = $file->move($pathFile, $nameRandom);
        }
        return $info;
    }

    public function remove_file_attachment($files)
    {
        if (count($files)) {
            foreach ($files as $file) {
                $pathFile = config('app.path_base') . DIRECTORY_SEPARATOR . config('const.folder_upload_default') . DIRECTORY_SEPARATOR;
                if (file_exists($pathFile . DIRECTORY_SEPARATOR . $file->path)) {
                    unlink($pathFile . DIRECTORY_SEPARATOR . $file->path);
                }
            }
        }
    }
}
