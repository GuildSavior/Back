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
Route::get('/auth/discord/callback', [DiscordAuthController::class, 'handleDiscordCallback']);
Route::post('/stripe/webhook', [StripeController::class, 'webhook']);
Route::middleware('auth:sanctum')->post('/stripe/create-checkout-session', [StripeController::class, 'createCheckoutSession']);

Route::group(['middleware' => 'auth:sanctum'], function() {
    Route::get('/user', [UserController::class, 'getAuthenticatedUserInfos']);
    Route::get('/logout', [DiscordAuthController::class, 'logout']);
    
    // ⭐ ROUTES PLAYER
    Route::prefix('player')->group(function () {
        Route::get('/', [PlayerController::class, 'show']); // Mon profil joueur
        Route::post('/', [PlayerController::class, 'createOrUpdate']); // Créer/modifier profil
        Route::delete('/', [PlayerController::class, 'destroy']); // Supprimer profil
    });
    
    // ⭐ ROUTE POUR LISTER TOUS LES JOUEURS (optionnel)
    Route::get('/players', [PlayerController::class, 'index']);
});

Route::middleware('auth:sanctum')->group(function () {
    // Guildes
    Route::prefix('guilds')->group(function () {
        Route::get('/', [GuildController::class, 'index']);
        Route::post('/', [GuildController::class, 'create']);
        Route::get('/current', [GuildController::class, 'current']);
        Route::post('/{guild}/join', [GuildController::class, 'join']);
        Route::get('/members', [GuildController::class, 'getMembers']);
        Route::post('/leave', [GuildController::class, 'leave']);
        
        // ⭐ NOUVELLE ROUTE POUR DISSOUDRE LA GUILDE
        Route::delete('/disband', [GuildController::class, 'disband']);
        
        // Invitations
        Route::prefix('invitations')->group(function () {
            Route::get('/', [GuildInvitationController::class, 'index']);
            Route::post('/', [GuildInvitationController::class, 'create']);
            Route::delete('/{invitation}', [GuildInvitationController::class, 'deactivate']);
        });
    });
    
    Route::get('/invite/{code}', [GuildInvitationController::class, 'join']);
});
