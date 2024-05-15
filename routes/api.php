<?php

use App\Http\Controllers\Api\Admin\CustomerController;
use App\Http\Controllers\Api\Admin\WorkerController;
use App\Http\Controllers\Api\Admin\CategoryController;
use App\Http\Controllers\Api\Admin\ItemController;
use App\Http\Controllers\Api\Admin\TaskController;
use App\Http\Controllers\Api\Admin\WebsiteController;
use App\Http\Controllers\Api\Admin\EnquiryController;
use App\Http\Controllers\Api\Website\HomeController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\Admin\AdminController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\Website\Auth\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MastersController;
use App\Models\Customer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['middleware' => ['cors']], function () {

    Route::post('/admin/register', [App\Http\Controllers\Api\Auth\AuthController::class, 'register']);
    Route::post('/admin/login', [App\Http\Controllers\Api\Auth\AuthController::class, 'login']);

    Route::post('/forgot/password', [App\Http\Controllers\Api\Auth\AuthController::class, 'forgot_password'])->middleware('guest')->name('password.email');
    Route::post('/reset/password', [App\Http\Controllers\Api\Auth\AuthController::class, 'reset_password'])->middleware('guest')->name('password.update');


    // Route::get('/reset-password/{token}', function (string $token) {
    //     return view('auth.reset-password', ['token' => $token]);
    // })->middleware('guest')->name('password.reset');



    Route::group(['middleware' => ['auth:api']], function () {


        //Dashboard (superAdmin, Admin, Worker, Customer)
        Route::post('admin/dashboard', [DashboardController::class, 'dashboard']);
        Route::post('customer/dashboard', [DashboardController::class, 'dashboard']);
        Route::post('worker/dashboard', [DashboardController::class, 'dashboard']);

        //Notification (superAdmin, Admin, Worker, Customer)
        Route::post('admin/notification', [DashboardController::class, 'getNotification']);
        Route::post('customer/notification', [DashboardController::class, 'getNotification']);
        Route::post('worker/notification', [DashboardController::class, 'getNotification']);

        //Super admin
        //Customer
        Route::post('superadmin/admin/list', [AdminController::class, 'list']);
        Route::post('superadmin/admin/create', [AdminController::class, 'create']);
        Route::post('superadmin/admin/update', [AdminController::class, 'update']);
        Route::get('superadmin/admin/get/{encrypt_id}', [AdminController::class, 'get']);
        Route::get('superadmin/admin/getEncryptID/{id}', [AdminController::class, 'getEncryptID']);
        Route::get('superadmin/admin/delete/{encrypt_id}', [AdminController::class, 'delete']);


        //Admin:
        //Customer
        Route::post('admin/customer/list', [CustomerController::class, 'list']);
        Route::post('admin/customer/create', [CustomerController::class, 'create']);
        Route::post('admin/customer/update', [CustomerController::class, 'update']);
        Route::get('admin/customer/get/{encrypt_id}', [CustomerController::class, 'get']);
        Route::get('admin/customer/getEncryptID/{id}', [CustomerController::class, 'getEncryptID']);
        Route::get('admin/customer/delete/{encrypt_id}', [CustomerController::class, 'delete']);

        //Worker
        Route::post('admin/worker/list', [WorkerController::class, 'list']);
        Route::post('admin/worker/create', [WorkerController::class, 'create']);
        Route::post('admin/worker/update', [WorkerController::class, 'update']);
        Route::get('admin/worker/get/{encrypt_id}', [WorkerController::class, 'get']);
        Route::get('admin/worker/getEncryptID/{id}', [WorkerController::class, 'getEncryptID']);
        Route::get('admin/worker/delete/{encrypt_id}', [WorkerController::class, 'delete']);

        //Catgeory
        Route::post('admin/category/list', [CategoryController::class, 'list']);
        Route::post('admin/category/create', [CategoryController::class, 'create']);
        Route::post('admin/category/update', [CategoryController::class, 'update']);
        Route::get('admin/category/get/{encrypt_id}', [CategoryController::class, 'get']);
        Route::get('admin/category/getEncryptID/{id}', [CategoryController::class, 'getEncryptID']);
        Route::get('admin/category/delete/{encrypt_id}', [CategoryController::class, 'delete']);

        //Item
        Route::post('admin/product/list', [ItemController::class, 'list']);
        Route::post('admin/product/create', [ItemController::class, 'create']);
        Route::post('admin/product/update', [ItemController::class, 'update']);
        Route::get('admin/product/get/{encrypt_id}', [ItemController::class, 'get']);
        Route::get('admin/product/getEncryptID/{id}', [ItemController::class, 'getEncryptID']);
        Route::get('admin/product/category/{selectCondition}', [ItemController::class, 'getCategoryList']);
        Route::get('admin/product/delete/{encrypt_id}', [ItemController::class, 'delete']);

        //Task
        Route::post('admin/task/list', [TaskController::class, 'list']);
        Route::post('admin/task/create', [TaskController::class, 'create']);
        Route::post('admin/task/update', [TaskController::class, 'update']);
        Route::post('admin/task/update/status', [TaskController::class, 'taskStatusUpdate']);
        Route::get('admin/task/get/{encrypt_id}', [TaskController::class, 'get']);
        Route::get('admin/task/getEncryptID/{id}', [TaskController::class, 'getEncryptID']);
        Route::get('admin/task/category/{selectCondition}', [TaskController::class, 'getCategoryList']);
        Route::get('admin/task/customer/{selectCondition}', [TaskController::class, 'getCustomerList']);
        Route::get('admin/task/worker/{selectCondition}', [TaskController::class, 'getWorkerList']);
        Route::get('admin/task/delete/{encrypt_id}', [TaskController::class, 'delete']);

        //Website
        Route::post('admin/website/create', [WebsiteController::class, 'create']);
        Route::post('admin/website/update', [WebsiteController::class, 'update']);
        Route::get('admin/website/get', [WebsiteController::class, 'get']);
        Route::post('admin/website/update/launchat', [WebsiteController::class, 'launchAtUpdate']);


        //Enquiry
        Route::post('admin/enquiry/list', [EnquiryController::class, 'enquiryList']);

        //Favourite
        Route::post('admin/favourite/list', [EnquiryController::class, 'favouriteList']);

        //Subscribe
        Route::post('admin/subscribe/list', [EnquiryController::class, 'subscribeList']);

        //Message
        Route::post('admin/message/list', [EnquiryController::class, 'messageList']);


        //User
        Route::post('admin/user/create', [UserController::class, 'create']);

        Route::get('admin/profile/get/{encrypt_id}', [ProfileController::class, 'getAdmin']);
        Route::post('admin/profile/update', [ProfileController::class, 'updateAdmin']);
        //End Admin



        //Worker
        Route::post("worker/task/list", [App\Http\Controllers\Api\Worker\TaskController::class, 'list']);
        Route::post('worker/task/update', [App\Http\Controllers\Api\Worker\TaskController::class, 'update']);
        Route::post('worker/task/update/status', [App\Http\Controllers\Api\Worker\TaskController::class, 'taskStatusUpdate']);
        Route::get('worker/task/get/{encrypt_id}', [App\Http\Controllers\Api\Worker\TaskController::class, 'get']);
        Route::get('worker/task/getEncryptID/{id}', [App\Http\Controllers\Api\Worker\TaskController::class, 'getEncryptID']);
        Route::get('worker/task/category/{selectCondition}', [App\Http\Controllers\Api\Worker\TaskController::class, 'getCategoryList']);

        Route::get('worker/profile/get/{encrypt_id}', [ProfileController::class, 'getWorker']);
        Route::post('worker/profile/update', [ProfileController::class, 'updateWorker']);


        //Customer
        Route::get('customer/profile/get/{encrypt_id}', [ProfileController::class, 'getCustomer']);
        Route::post('customer/profile/update', [ProfileController::class, 'updateCustomer']);

        //Website
        Route::get('customer/profile/get/{encrypt_id}', [ProfileController::class, 'getCustomer']);

        //Profile - All role's users
        Route::get('user/profile', [ProfileController::class, 'profile']);
    });




    // Website - Frontend
    Route::post('website/category', [HomeController::class, 'getCategoryList']);

    Route::post('website/product', [HomeController::class, 'getProductlist']);
    Route::post('website/banner', [HomeController::class, 'getBannerlist']);
    Route::post('website/feature', [HomeController::class, 'getFeaturelist']);
    Route::post('website/about', [HomeController::class, 'getAboutlist']);
    Route::post('website/logo', [HomeController::class, 'getLogolist']);
    Route::post('website/contact', [HomeController::class, 'getContact']);

    Route::post('website/customer', [HomeController::class, 'getCustomerList']);
    Route::any('website/product/{encrypt_id}', [HomeController::class, 'getProduct']);

    //Admin auth
    Route::post('website/admin/get', [AuthController::class, 'getAdmin']);

    //Visitor auth
    Route::post('website/visitor/login', [App\Http\Controllers\Api\Auth\AuthController::class, 'login']);
    Route::post('website/visitor/register', [App\Http\Controllers\Api\Auth\AuthController::class, 'register']);
    Route::post('website/visitor/forgot/password', [App\Http\Controllers\Api\Auth\AuthController::class, 'forgot_password']);
    Route::post('website/visitor/reset/password', [App\Http\Controllers\Api\Auth\AuthController::class, 'reset_password']);

    Route::post('website/enquiry/save', [HomeController::class, 'enquirySave']);
    Route::post('website/enquiry', [HomeController::class, 'enquiryList']);

    Route::post('website/favourite/save', [HomeController::class, 'favouriteSave']);
    Route::post('website/favourite', [HomeController::class, 'favouriteList']);

    Route::post('website/subscribe/save', [HomeController::class, 'subscribeSave']);
    Route::post('website/message/save', [HomeController::class, 'messageSave']);


    Route::group(['middleware' => ['auth:api']], function () {

        Route::get('website/profile/get/{encrypt_id}', [CustomerController::class, 'get']);
        Route::post('website/profile/update', [CustomerController::class, 'update']);
    });
});
