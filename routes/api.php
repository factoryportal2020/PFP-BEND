<?php

use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\WorkerController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MastersController;
use App\Models\Customer;
use Illuminate\Http\UploadedFile;

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

    Route::group(['middleware' => ['auth:api']], function () {
        
        //Customer
        Route::post('/customer/list', [CustomerController::class, 'list']);
        Route::post('/customer/create', [CustomerController::class, 'create']);
        Route::post('/customer/update', [CustomerController::class, 'update']);
        Route::get('/customer/get/{encrypt_id}', [CustomerController::class, 'get']);
        Route::get('/customer/getEncryptID/{id}', [CustomerController::class, 'getEncryptID']);


        //Worker
        Route::post('/worker/list', [WorkerController::class, 'list']);
        Route::post('/worker/create', [WorkerController::class, 'create']);
        Route::post('/worker/update', [WorkerController::class, 'update']);
        Route::get('/worker/get/{encrypt_id}', [WorkerController::class, 'get']);
        Route::get('/worker/getEncryptID/{id}', [WorkerController::class, 'getEncryptID']);


        //Catgeory
        Route::post('/category/list', [CategoryController::class, 'list']);
        Route::post('/category/create', [CategoryController::class, 'create']);
        Route::post('/category/update', [CategoryController::class, 'update']);
        Route::get('/category/get/{encrypt_id}', [CategoryController::class, 'get']);
        Route::get('/category/getEncryptID/{id}', [CategoryController::class, 'getEncryptID']);

        //Item
        Route::post('/product/list', [ItemController::class, 'list']);
        Route::post('/product/create', [ItemController::class, 'create']);
        Route::post('/product/update', [ItemController::class, 'update']);
        Route::get('/product/get/{encrypt_id}', [ItemController::class, 'get']);
        Route::get('/product/getEncryptID/{id}', [ItemController::class, 'getEncryptID']);
        Route::get('/product/category', [ItemController::class, 'getCategoryList']);

        //Task
        Route::post('/task/list', [TaskController::class, 'list']);
        Route::post('/task/create', [TaskController::class, 'create']);
        Route::post('/task/update', [TaskController::class, 'update']);
        Route::get('/task/get/{encrypt_id}', [TaskController::class, 'get']);
        Route::get('/task/getEncryptID/{id}', [TaskController::class, 'getEncryptID']);
        Route::get('/task/category', [TaskController::class, 'getCategoryList']);
        Route::get('/task/customer', [TaskController::class, 'getCustomerList']);
        Route::get('/task/worker', [TaskController::class, 'getWorkerList']);


        Route::post('/user/create', [UserController::class, 'create']);
        Route::get('/admin/profile', function () {
            // authenticated user. Use User::find() to get the user from db by id
            return auth()->user();
        });
    });
});
