<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Controllers\DiscordAuthController;
use \App\Http\Controllers\UserController;
use App\Http\Controllers\GuildController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\PlayerController;
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
Route::get('/auth/discord', [DiscordAuthController::class, 'redirectToDiscord']);
Route::get('/auth/discord/callback', [DiscordAuthController::class, 'handleDiscordCallback']);

// Routes publiques pour les guildes (lecture seulement)
Route::get('/guilds', [GuildController::class, 'index']);
Route::get('/guilds/search', [GuildController::class, 'search']);
Route::get('/guilds/{id}', [GuildController::class, 'show']);

// Routes protégées par authentification
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [UserController::class, 'getAuthenticatedUserInfos']);
    Route::get('/logout', [DiscordAuthController::class, 'logout']);
    
    // Routes Stripe (accessible aux utilisateurs connectés)
    Route::post('/stripe/create-checkout-session', [StripeController::class, 'createCheckoutSession']);
});

// Routes protégées par authentification + abonnement Premium
Route::middleware(['auth:sanctum', 'premium'])->group(function () {
    Route::post('/guilds', [GuildController::class, 'store']);
    Route::put('/guilds/{id}', [GuildController::class, 'update']);
    Route::patch('/guilds/{id}', [GuildController::class, 'update']);
    Route::delete('/guilds/{id}', [GuildController::class, 'destroy']);
});

// Webhook Stripe (pas d'authentification)
Route::post('/stripe/webhook', [StripeController::class, 'webhook']);
Route::post('/stripe/webhook', [StripeController::class, 'webhook']);
Route::middleware('auth:sanctum')->post('/stripe/create-checkout-session', [StripeController::class, 'createCheckoutSession']);
Route::group(['middleware' => 'auth:sanctum'], function()
    {
        Route::get('/user', [UserController::class, 'getAuthenticatedUserInfos']);
        Route::get('/logout', [DiscordAuthController::class, 'logout']);
        
    }
);
/*Route::middleware('web')->group(function () {
    Route::get('/auth/discord/redirect', [DiscordAuthController::class, 'redirectToDiscord']);
    Route::get('/auth/discord/callback', [DiscordAuthController::class, 'handleDiscordCallback']);
});*/
