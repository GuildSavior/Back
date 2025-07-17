<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Controllers\DiscordAuthController;
use \App\Http\Controllers\UserController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\GuildController;
use App\Http\Controllers\GuildInvitationController;
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
Route::get('/auth/discord/select-account', [DiscordAuthController::class, 'redirectToDiscordWithSelect']); // ⭐ AJOUTER SEULEMENT CETTE LIGNE
Route::get('/auth/discord/callback', [DiscordAuthController::class, 'handleDiscordCallback']);
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
Route::middleware('auth:sanctum')->group(function () {
    // Guildes
    Route::prefix('guilds')->group(function () {
        Route::get('/', [GuildController::class, 'index']); // Lister toutes les guildes
        Route::post('/', [GuildController::class, 'create']); // Créer une guilde (premium only)
        Route::get('/current', [GuildController::class, 'current']); // Ma guilde actuelle
        Route::post('/{guild}/join', [GuildController::class, 'join']); // Rejoindre une guilde
        Route::post('/leave', [GuildController::class, 'leave']); // Quitter ma guilde
        
        // ⭐ INVITATIONS
        Route::prefix('invitations')->group(function () {
            Route::get('/', [GuildInvitationController::class, 'index']); // Mes invitations
            Route::post('/', [GuildInvitationController::class, 'create']); // Créer invitation
            Route::delete('/{invitation}', [GuildInvitationController::class, 'deactivate']); // Désactiver
        });
    });
    
    // Rejoindre via invitation (public mais auth required)
    Route::get('/invite/{code}', [GuildInvitationController::class, 'join']);
});
