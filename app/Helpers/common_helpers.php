<?php

use Illuminate\Support\Facades\Log;

function encryptID($string, $action = 'e')
{
    // you may change these values to your own
    $secret_key = 'my_simple_secret_key_pocket_poche_admin';
    $secret_iv = 'my_simple_secret_iv__pocket_poche_admin';

    $output = false;
    $encrypt_method = "AES-256-CBC";
    $key = hash('sha256', $secret_key);
    $iv = substr(hash('sha256', $secret_iv), 0, 16);

    if ($action == 'e') {
        $output = base64_encode(openssl_encrypt($string, $encrypt_method, $key, 0, $iv));
    } else if ($action == 'd') {
        $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
    }

    return $output;
}

//createlog("customer","add","Customer",1)
function successLog($menu, $action, $model = null, $id = null, $message = null)
{
    $content = $menu . "-" . $model . "-" . $action . "-" . $id;

    Log::build([
        'driver' => 'daily',
        'path' => storage_path('logs' . env('SUCCESS_LOG_FOLDER') . env('SUCCESS_LOG_FILE_NAME'))
    ])->info($content);
}

//createlog("customer","add","Customer",1,"something went wrong");
function errorLog($menu, $action, $model = null, $id = null, $message = null)
{
    $content = $menu . "-" . $model . "-" . $action . "-" . $id . "-" . $message;
    
    Log::build([
        'driver' => 'daily',
        'path' => storage_path('logs' . env('ERROR_LOG_FOLDER') . env('ERROR_LOG_FILE_NAME'))
    ])->error($content);
}
