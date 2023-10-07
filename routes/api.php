<?php

use App\Http\Controllers\Api\CustomerController;
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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });


Route::group(['middleware' => ['cors']], function () {
    Route::post('/customer/create', [CustomerController::class, 'create']);

    // Route::post('/customer/create', function (Request $request) {
 
    //     echo "<pre>"; print_r($request->profile_image);
    // });
});
