<?php

use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ServerController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\VoucherController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('api.token')->group(function () {
    Route::patch('/users/{user}/increment', [UserController::class, 'increment']);
    Route::patch('/users/{user}/decrement', [UserController::class, 'decrement']);
    Route::patch('/users/{user}/suspend', [UserController::class, 'suspend']);
    Route::patch('/users/{user}/unsuspend', [UserController::class, 'unsuspend']);
    Route::resource('users', UserController::class)->except(['create']);

    Route::patch('/servers/{server}/suspend', [ServerController::class, 'suspend']);
    Route::patch('/servers/{server}/unsuspend', [ServerController::class, 'unSuspend']);
    Route::resource('servers', ServerController::class)->except(['store', 'create', 'edit', 'update']);

    //    Route::get('/vouchers/{voucher}/users' , [VoucherController::class , 'users']);
    Route::resource('vouchers', VoucherController::class)->except('create', 'edit');

    Route::get('/notifications/{user}', [NotificationController::class, 'index']);
    Route::get('/notifications/{user}/{notification}', [NotificationController::class, 'view']);
    Route::post('/notifications', [NotificationController::class, 'send']);
    Route::delete('/notifications/{user}/{notification}', [NotificationController::class, 'deleteOne']);
    Route::delete('/notifications/{user}', [NotificationController::class, 'delete']);
});

Route::get("event/get_user", function(Request $request){
    if (!$request->bearerToken()||$request->bearerToken()!=env("EVENT_API_BEARER")) return response("Forbidden", 403);
    
    $user = User::select("id", "role")->where("email", json_decode($request->getContent())->email)->first();
    if ($user) return response(json_encode(["success" => true, "id" => $user->id, "client" => $user->role!="member"]));
    else return response(json_encode(["success" => false, "reason" => "user_not_found"]));
});

Route::post("event/give_credits", function(Request $request){
    if (!$request->bearerToken()||$request->bearerToken()!=env("EVENT_API_BEARER")) return response("Forbidden", 403);
    
    $json = json_decode($request->getContent());
    if(!$json) return response(json_encode(["success" => false, "reason" => "json_invalid"]));

    $user = User::where("id", $json->id)->first();
    if(!$user) return response(json_encode(["success" => false, "reason" => "user_not_found"]));
    if($user->role=="member") return response(json_encode(["success" => false, "reason" => "user_not_client"]));

    $user->increment("credits", $json->amount);
    return response(json_encode(["success" => true]));
});

require __DIR__ . '/extensions_api.php';
