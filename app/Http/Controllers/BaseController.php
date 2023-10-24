<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;

class BaseController extends Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected function responseAPI($status, $message, $status_code, $data = [], $time_update = null)
    {
        $array = ['status' => $status, 'message' => $message];

        if ($time_update != null) {
            $array['time_update'] = $time_update;
        }

        if ($data != null) {
            $array['data'] = $data;
        }

        return response()->json($array, $status_code);
    }
}
