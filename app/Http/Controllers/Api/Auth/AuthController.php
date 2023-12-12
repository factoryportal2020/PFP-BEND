<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\BaseController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Carbon;

class AuthController extends BaseController
{

    protected $usercontroller;

    public function __construct()
    {
        $this->usercontroller = new UserController();
    }

    public function login(LoginRequest $request)
    {
        try {

            $login = $request->input('email');
            $user = $userInfo = User::where('email', $login)->orWhere('username', $login)->first();

            if (!$user) {
                $message = "Username or Password not Valid";
                return $this->responseAPI(false, $message, 200);
            }

            if (
                !Auth::attempt(['email' => $user->email, 'password' => $request->password]) &&
                !Auth::attempt(['username' => $user->username, 'password' => $request->password])
            ) {
                $message = "Invalid login credentials";
                return $this->responseAPI(false, $message, 200);
            }

            $user = $request->user();
            $tokenResult = $user->createToken('Personal Access Token');
            $token = $tokenResult->token;

            if ($request->remember_me) :
                $token->expires_at = Carbon::now()->addWeeks(1);
            endif;
            $token->save();

            $message = "Login Successfully";

            $userInfo = [
                'role' => $userInfo->role->name,
                'username' => $userInfo->username
            ];

            $data = [
                'access_token' => $tokenResult->accessToken,
                'token_type' => 'Bearer',
                'expires_at' => Carbon::parse($tokenResult->token->expires_at)->toDateTimeString(),
                'userInfo' => $userInfo
            ];


            return $this->responseAPI(true, $message, 200, $data);
        } catch (\Exception $e) {
            return $this->responseAPI(false, $e->getMessage(), 200);
        }
    }


    public function register(Request $request)
    {
        try {
            DB::beginTransaction();

            $request->merge([
                'domain_id' => 1,
                'role_id' => 2,
            ]);

            $user = $this->usercontroller->createUser($request);
            if ($user->status() == 200) {
                $message = "Login Details Registered Successfully";
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

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        $message = "Logout Successfully";
        return $this->responseAPI(true, $message, 200);
    }
}
