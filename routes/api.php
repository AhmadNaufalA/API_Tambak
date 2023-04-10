<?php

use App\Http\Controllers\Api\PassportAuthController;
use App\Http\Controllers\Api\KualitasAirController;
use App\Http\Controllers\Api\TambakController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\LogController;
// use Illuminate\Support\Facades\Request;
use App\Models\LogMQTT;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

Route::post('user/register', [UserController::class, 'register']);
Route::post('user/login', [UserController::class, 'login']);
Route::post('user/login_admin', [UserController::class, 'login_admin']);
Route::resource('kualitas-air', KualitasAirController::class);
Route::post('user/check/{id}', [UserController::class, "checkAnswer"]);
Route::post('user/reset/{id}', [UserController::class, 'reset']);
Route::get('user/username/{username}', [UserController::class, "showUsername"]);

Route::middleware(['auth'])->group(
    function () {


        Route::get('tambak/ids/{user_id}', [TambakController::class, 'idList']);
        Route::get('tambak/between/{id}', [TambakController::class, 'between']);
        Route::get('tambak/logs/{id}', [LogController::class, 'getLogsByTambakId']);
        Route::get('tambak/logrusaks/{id}', [LogController::class, 'getLogRusaksByTambakId']);
        Route::post('tambak/{tambak}', [TambakController::class, "update"]);
        Route::get('tambak/user/{id}', [TambakController::class, "userOwned"]);
        Route::resource('tambak', TambakController::class)->middleware('auth');
        Route::get('logs', [LogController::class, "allLogs"]);
        Route::get('logrusaks', [LogController::class, "allLogRusaks"]);

        Route::get('user/check_token', [UserController::class, "check_token"])->middleware('auth');
        Route::get('user/{id}', [UserController::class, "show"]);
        Route::post('user/{user}', [UserController::class, "update"]);
        Route::get('all_users', [UserController::class, "show_all_users"]);
        Route::delete('destroyUser/{id}', [UserController::class, "destroy"]);
        Route::post('user/save-device-token', [UserController::class, 'save_device_token']);
        // });
    }
);