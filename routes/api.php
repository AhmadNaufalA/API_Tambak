<?php

use App\Http\Controllers\Api\PassportAuthController;
use App\Http\Controllers\Api\KualitasAirController;
use App\Http\Controllers\Api\TambakController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;


// Route::post('register', [PassportAuthController::class , 'register']);
// Route::post('login', [PassportAuthController::class , 'login']);


// Route::middleware('auth:api')->group(function () {
Route::get('get-user', [PassportAuthController::class , 'userInfo']);


// Route::get('kualitas-air/between', [KualitasAirController::class , 'between']);
// Route::get('kualitas-air/single', [KualitasAirController::class , 'single']);
Route::resource('kualitas-air', KualitasAirController::class);

Route::get('tambak/between/{id}', [TambakController::class , 'between']);
Route::get('tambak/logs/{id}', [TambakController::class , 'logs']);
Route::post('tambak/{tambak}', [TambakController::class, "update"]);
Route::get('tambak/user/{id}',[ TambakController::class, "userOwned"]);
Route::resource('tambak', TambakController::class);

Route::post('user/register', [UserController::class, 'register']);
Route::post('user/login', [UserController::class, 'login']);
Route::get('user/{id}', [UserController::class, "show"]);
Route::get('user/username/{username}', [UserController::class, "showUsername"]);
Route::post('user/check/{id}', [UserController::class, "checkAnswer"]);
Route::post('user/reset/{id}', [UserController::class, 'reset']);

// });