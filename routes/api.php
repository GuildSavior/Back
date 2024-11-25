<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Controllers\DiscordAuthController;
use \App\Http\Controllers\UserController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

/*Route::middleware('auth:sanctum')->group(function () {
    Route::resource('users', PlayerController::class);
});*/
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::get('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/user', [UserController::class, 'getAuthenticatedUser']);
Route::middleware('web')->group(function () {
    Route::get('/auth/discord/redirect', [DiscordAuthController::class, 'redirectToDiscord']);
    Route::get('/auth/discord/callback', [DiscordAuthController::class, 'handleDiscordCallback']);
});



