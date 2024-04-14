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
use App\Models\Admin;
use App\Models\Customer;
use Illuminate\Support\Carbon;
use App\Http\Requests\AdminRequest;
use App\Http\Requests\CustomerRequest;
use App\Models\Website;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Password;
use Illuminate\Mail\Message;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;

use Illuminate\Support\Str;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Contracts\Session\Session;

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

            // //check with status 1 
            // $user = $userInfo = User::where(function ($query) use ($login) { //  use function to pass data inside
            //     $query->where('email', $login)->orWhere('username', $login);
            // })->where('status', 0)->first();

            // if (!$user) {
            //     $message = "Username or Email not Registered";
            //     return $this->responseAPI(false, $message, 200);
            // }

            //check with deleted_at 
            $userWT = User::where(function ($query) use ($login) { //  use function to pass data inside
                $query->where('email', $login)->orWhere('username', $login);
            })->first();

            if (!$userWT) {
                $message = "Email or Username Not Registered";
                errorLog("login", "Email_check", "User", null, $message);
                return $this->responseAPI(false, $message, 200);
            }

            //check with status 0 
            $user = $userInfo = User::where(function ($query) use ($login) { //  use function to pass data inside
                $query->where('email', $login)->orWhere('username', $login);
            })->where('status', 1)->withTrashed()->first();

            if (!$user) {
                $message = "Your Account have suspended, Contact Your Admin!";
                errorLog("login", "Auth_withTrashed", "User", null, $message);
                return $this->responseAPI(false, $message, 200);
            }
            Auth::attempt(['email' => $user->email, 'password' => $request->password]);
            if (
                !Auth::attempt(['email' => $user->email, 'password' => $request->password]) &&
                !Auth::attempt(['username' => $user->username, 'password' => $request->password])
            ) {
                $message = "Invalid login credentials";
                errorLog("login", "Auth_attempt", "User", null, $message);
                return $this->responseAPI(false, $message, 200);
            }

            $user = $request->user();
            $tokenResult = $user->createToken('Admin Access Token');
            $token = $tokenResult->token;

            if ($request->remember_me) :
                $token->expires_at = Carbon::now()->addWeeks(1);
            endif;
            $token->save();

            $message = "Login Successfully";

            $userInfo = [
                'role' => $userInfo->role->name,
                'username' => $userInfo->username,
                'email' => $userInfo->email
            ];

            $data = [
                'access_token' => $tokenResult->accessToken,
                'token_type' => 'Bearer',
                'expires_at' => Carbon::parse($tokenResult->token->expires_at)->toDateTimeString(),
                'userInfo' => $userInfo,
                'permissions' => $request->user()->permissions()
            ];

            successLog("login", "Auth_attempt", "User", $user->id, $message);

            return $this->responseAPI(true, $message, 200, $data);
        } catch (\Exception $e) {
            errorLog("login", "Exception", "User", null, $message);
            return $this->responseAPI(false, $e->getMessage(), 200);
        }
    }


    public function register(Request $request)
    {
        try {
            DB::beginTransaction();
            $admin_id = encryptID($request->adminToken, 'd');
            $request->merge([
                'domain_id' => 1,
                'role_id' => ($request->role) ? $request->role : 2,
            ]);

            if ($request->role == 3) {
                //Customer
                $CustomerRequest = new CustomerRequest();
                $validator = Validator::make($request->all(), $CustomerRequest->rules($request), $CustomerRequest->messages());
                if ($validator->fails()) {
                    $CustomerRequest->failedValidation($validator);
                }
            } else {
                //Admin
                $AdminRequest = new AdminRequest();
                $validator = Validator::make($request->all(), $AdminRequest->rules($request), $AdminRequest->messages());
                if ($validator->fails()) {
                    $AdminRequest->failedValidation($validator);
                }
            }

            if ($request->role == 3) { //customer
                $data = [
                    "email" => $request->email,
                    "phone_no" => $request->phone_no,
                    'domain_id' => 1,
                    "admin_id" => $admin_id,
                    "code" => ($request->role == 3) ? Customer::getCode() : Admin::getCode()
                ];

                $customer = Customer::create($data);
                $customer->save();

                $request->merge([
                    'admin_id' => $admin_id,
                ]);
                $user = $this->usercontroller->createUser($request);
                // return $this->responseAPI(false, $user, 200);

                if ($user->status() == 200) {
                    $customer->update(['user_id' => $user->getData()->id]);
                    $message = "Login Details Registered Successfully";
                }
                $action = "NewRegisterCustomer";
            } else {
                $data = [
                    "email" => $request->email,
                    "phone_no" => $request->phone_no,
                    "domain_id" => $request->domain_id,
                    "code" => ($request->role == 3) ? Customer::getCode() : Admin::getCode()
                ];
                $admin = Admin::create($data);
                $admin->save();
                $user = $this->usercontroller->createUser($request);

                if ($user->status() == 200) {
                    $admin->update(['user_id' => $user->getData()->id]);
                    $message = "Login Details Registered Successfully";
                }
                $action = "NewRegisterAdmin";
            }

            DB::commit();
            successLog("Register", $action, "User",  $user->getData()->id, $message);
            return $this->responseAPI(true, $message, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof HttpResponseException) {
                errorLog("Register", "HttpResponseException", "User", null, $e->getMessage());
                return $e->getResponse();
            }
            errorLog("Register", "Exception", "User", null, $e->getMessage());
            // print_r($e);
            return $this->responseAPI(false, $e->getMessage(), 200);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        $message = "Logout Successfully";
        successLog("logout", "token-revoke", "User", $request->user()->id, $message);
        return $this->responseAPI(true, $message, 200);
    }


    public function forgot_password(Request $request)
    {
        $input = $request->all();
        $rules = array(
            'email' => "required|email",
        );
        if ($request->header('Admin-EncryptId')) {
            $admin_id = encryptID($request->header('Admin-EncryptId'), 'd');
            $website = Website::where("admin_id", $admin_id)->first();
            session(['AdminName' => $website->site_url]);
        }
        
        $arr = [];
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            $arr = array("status" => 200, "message" => $validator->errors()->first());
            throw new HttpResponseException(
                response()->json($arr, 200)
            );
        } else {
            try {
                $response = Password::sendResetLink($request->only('email'));
                switch ($response) {
                    case Password::RESET_LINK_SENT:
                        successLog("forgot_password", Password::RESET_LINK_SENT, "User", $request->email, trans($response));
                        return $this->responseAPI(true, trans($response), 200);
                        // return \Response::json(array("status" => 200, "message" => trans($response), "data" => array()));
                    case Password::INVALID_USER:
                        errorLog("forgot_password", Password::INVALID_USER, "User", $request->email, trans($response));
                        return $this->responseAPI(false, trans($response), 200);
                        // return \Response::json(array("status" => 400, "message" => trans($response), "data" => array()));
                }
            } catch (\Swift_TransportException $ex) {
                // $this->responseAPI(false, $ex->getMessage(), 200);
                errorLog("forgot_password", "Swift_TransportException", "User", $request->email, $ex->getMessage());
                $arr = array("status" => 200, "message" => $ex->getMessage(), "data" => []);
            } catch (\Exception $ex) {
                //$this->responseAPI(false, $ex->getMessage(), 200);
                errorLog("forgot_password", "Exception", "User", $request->email, $ex->getMessage());
                $arr = array("status" => 200, "message" => $ex->getMessage(), "data" => []);
            }
            throw new HttpResponseException(
                response()->json($arr, 200)
            );
        }
    }



    public function reset_password(Request $request)
    {
        $input = $request->all();
        $rules = array(
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        );
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            $arr = array("status" => 200, "message" => $validator->errors()->first());
            throw new HttpResponseException(
                response()->json($arr, 200)
            );
        }
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if (session('AdminName')) {
            session(['AdminName' => ""]);
        }

        if ($status === Password::PASSWORD_RESET) {
            return $this->responseAPI(true, trans($status), 200);
        } else {
            return $this->responseAPI(false, trans($status), 200);
        }
    }
}
