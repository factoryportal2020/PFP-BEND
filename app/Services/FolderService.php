<?php

namespace App\Services;

use App\Models\Folder;
use Illuminate\Filesystem\Filesystem;

class FolderService
{
    public function created()
    {
        $fileSystem = new Filesystem();
        $name = round(microtime(true) * 1000);
        $path = config('app.path_base') . DIRECTORY_SEPARATOR . $name;
        if (!$fileSystem->isDirectory($path)) {
            $fileSystem->makeDirectory($path);
            return $name;
        } else {
            return false;
        }
    }
    public function delete($name)
    {
        $fileSystem = new Filesystem();
        $directory = config('app.path_base') . DIRECTORY_SEPARATOR . $name;
        return $fileSystem->deleteDirectory($directory);
    }
    public function checkIp(){

        $ip_client = $this->getIp();
        $string_ip = env('IP_ADDRESS_FILEBOX');
        $arr_ip = explode(',',$string_ip);
        $arr_check = [];
        foreach ($arr_ip as $key=>$range){
            if ( strpos( $range, '/' ) == false ) {
                $range .= '/32';
            }
            // $range is in IP/CIDR format eg 127.0.0.1/24
            list( $range, $netmask ) = explode( '/', $range, 2 );
            $range_decimal = ip2long( $range );
            $ip_decimal = ip2long( $ip_client );
            $wildcard_decimal = pow( 2, ( 32 - $netmask ) ) - 1;
            $netmask_decimal = ~ $wildcard_decimal;
            if ( ( $ip_decimal & $netmask_decimal ) == ( $range_decimal & $netmask_decimal ) ){
                return true;
            }
        }
        return false;
    }
    public function getIp()
    {
        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP']))
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if (isset($_SERVER['HTTP_X_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        else if (isset($_SERVER['HTTP_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        else if (isset($_SERVER['HTTP_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        else if (isset($_SERVER['REMOTE_ADDR']))
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        else
            $ipaddress = 'UNKNOWN';
        return $ipaddress;
    }
}
