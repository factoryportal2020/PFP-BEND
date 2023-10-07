<?php

namespace App\Services;

use DateTimeZone;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

class LogService
{
    static function messageException($e){
        $service = new FolderService();
        $now = \DateTime::createFromFormat('U.u', microtime(true));
        $now->setTimezone(new DateTimeZone('Asia/Tokyo'));
        if(Auth::check()){
            Log::error( '['.substr($now->format("Y-m-d H:i:s.u"),0,23) . '] '. getmypid() .' ' . $service->getIp() . ' ' . substr(session()->getId(),0,10) . ' ' . Auth::user()->login_id . ' ' . Auth::user()->group->id . ' ERROR ' . str_replace('@','::',Route::getCurrentRoute()->getActionName())."\n".json_encode($e->getTrace(),JSON_UNESCAPED_UNICODE));
        }else{
            Log::error( '['.substr($now->format("Y-m-d H:i:s.u"),0,23) . '] '. getmypid() .' ' . $service->getIp() . ' ' . substr(session()->getId(),0,10) . ' ' . ' ERROR ' . str_replace('@','::',Route::getCurrentRoute()->getActionName())."\n".json_encode($e->getTrace(),JSON_UNESCAPED_UNICODE));
        }
    }

    static function logSendMail($form_submit_id, $workflow_id, $workflow_step_id, $group_id, $user_id){
        $service = new FolderService();
        $now = \DateTime::createFromFormat('U.u', microtime(true));
        $now->setTimezone(new DateTimeZone('Asia/Tokyo'));

        Log::info( '['.substr($now->format("Y-m-d H:i:s.u"),0,23) . '] '. getmypid() .' ' . $service->getIp() . ' ' . substr(session()->getId(),0,10) . ' ' . Auth::user()->login_id . ' ' . Auth::user()->group->id . ' INFO ' . str_replace('@','::',Route::getCurrentRoute()->getActionName()). ' [承認依頼] ' . ' form_submit_id=' .$form_submit_id. ' workflow_id=' .$workflow_id . ' workflow_step_id=' .$workflow_step_id . ' group_id=' .$group_id . ' user_id=' .$user_id);

    }


    static function messageExceptionCode($e,$line){
        if (gettype($e) == 'array'){
            $e = json_encode($e,JSON_UNESCAPED_UNICODE);
        }
        $service = new FolderService();
        $now = \DateTime::createFromFormat('U.u', microtime(true));
        $now->setTimezone(new DateTimeZone('Asia/Tokyo'));
        if(Auth::check()){
            Log::error( '['.substr($now->format("Y-m-d H:i:s.u"),0,23) . '] '. getmypid() .' ' . $service->getIp() . ' ' . substr(session()->getId(),0,10) . ' ' . Auth::user()->login_id . ' ' . Auth::user()->group->id . ' ERROR ' . str_replace('@','::',Route::getCurrentRoute()->getActionName())."".' line='.$line.' message='.$e);
        }else{
            Log::error( '['.substr($now->format("Y-m-d H:i:s.u"),0,23) . '] '. getmypid() .' ' . $service->getIp() . ' ' . substr(session()->getId(),0,10) . ' ' . ' ERROR ' . str_replace('@','::',Route::getCurrentRoute()->getActionName())."".' line='.$line.' message='.$e);
        }
    }
    static function logDebug($e){
        if (gettype($e) == 'array'){
            $e = json_encode($e,JSON_UNESCAPED_UNICODE);
        }
        $service = new FolderService();
        $now = \DateTime::createFromFormat('U.u', microtime(true));
        $now->setTimezone(new DateTimeZone('Asia/Tokyo'));
        if(Auth::check()){
            Log::error( '['.substr($now->format("Y-m-d H:i:s.u"),0,23) . '] '. getmypid() .' ' . $service->getIp() . ' ' . substr(session()->getId(),0,10) . ' ' . Auth::user()->login_id . ' ' . Auth::user()->group->id . ' DEBUG ' . str_replace('@','::',Route::getCurrentRoute()->getActionName()). ' message='.$e);
        }else{
            Log::error( '['.substr($now->format("Y-m-d H:i:s.u"),0,23) . '] '. getmypid() .' ' . $service->getIp() . ' ' . substr(session()->getId(),0,10) . ' ' . ' DEBUG ' . str_replace('@','::',Route::getCurrentRoute()->getActionName()). ' message='.$e);
        }
    }
}
