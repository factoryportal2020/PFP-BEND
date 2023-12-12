<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\UserRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;


class UserController extends BaseController
{
    //
    public function createUser(Request $request)
    {

        // try {
        $UserRequest = new UserRequest();
        $validator = Validator::make($request->all(), $UserRequest->rules($request), $UserRequest->messages());
        if ($validator->fails()) {
            $UserRequest->failedValidation($validator);
        }
        $data = [
            "name" => $request->first_name . " " . $request->last_name,
            "email" => $request->email,
            "username" => $request->username,
            "password" => $request->password,
            "phone_no" => $request->phone_no,
            "role_id" => $request->role_id,
            "domain_id" => $request->domain_id
        ];
        if ($request->user_id != null && $request->user_id != "") {
            $user = User::updateOrCreate(["id" => $request->user_id], $data);
        } else {
            $user = User::create($data);
        }

        return response()->json($user, 200);
    }


    public function getUser($id)
    {

        $user = User::findOrFail($id);

        return response()->json($user, 200);
    }
}
