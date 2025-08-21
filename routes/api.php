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
use App\Http\Controllers\EventController;
use App\Http\Controllers\UserImageController;
use App\Http\Controllers\AuctionController;
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
    Route::get('/players/{player}', [PlayerController::class, 'showPlayer']);
    
    // ⭐ ROUTE POUR LISTER TOUS LES JOUEURS (optionnel)
    Route::get('/players', [PlayerController::class, 'index']);
    
    // ⭐ ROUTES POUR LA GALERIE D'IMAGES
    Route::prefix('gallery')->group(function () {
        Route::get('/', [UserImageController::class, 'index']); // Mes images
        Route::post('/', [UserImageController::class, 'store']); // Upload image
        Route::put('/{image}', [UserImageController::class, 'update']); // Modifier image
        Route::delete('/{image}', [UserImageController::class, 'destroy']); // Supprimer image
    });
    
    // ⭐ GALERIE PUBLIQUE D'UN UTILISATEUR
    Route::get('/users/{user}/gallery', [UserImageController::class, 'userGallery']);
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
        Route::delete('/disband', [GuildController::class, 'disband']);
        
        // Invitations
        Route::prefix('invitations')->group(function () {
            Route::get('/', [GuildInvitationController::class, 'index']);
            Route::post('/', [GuildInvitationController::class, 'create']);
            
            // ✅ IMPORTANT: Routes spécifiques AVANT les routes avec paramètres
            Route::delete('/cleanup-inactive', [GuildInvitationController::class, 'cleanupInactive']);
            
            // ✅ Routes avec paramètres à la fin
            Route::delete('/{invitation}/delete', [GuildInvitationController::class, 'delete']);
            Route::delete('/{invitation}', [GuildInvitationController::class, 'deactivate']);
        });
        
        // ⭐ NOUVELLES ROUTES POUR LES ÉVÉNEMENTS
        Route::prefix('events')->group(function () {
            Route::get('/', [EventController::class, 'index']);                    // Lister les événements
            Route::post('/', [EventController::class, 'create']);                  // Créer un événement (owner)
            Route::post('/{event}/participate', [EventController::class, 'participate']); // S'inscrire
            Route::post('/{event}/confirm', [EventController::class, 'confirm']);         // Confirmer venue
            Route::post('/{event}/validate', [EventController::class, 'validateAttendance']); // Valider avec code
            Route::delete('/{event}', [EventController::class, 'delete']);
        });
        
        // ⭐ NOUVELLES ROUTES POUR LES ENCHÈRES
        Route::prefix('auctions')->group(function () {
            Route::get('/', [AuctionController::class, 'index']);                    // Lister les enchères
            Route::post('/', [AuctionController::class, 'create']);                  // Créer une enchère (owner)
            Route::get('/{auction}', [AuctionController::class, 'show']);            // Voir une enchère
            Route::post('/{auction}/bid', [AuctionController::class, 'bid']);        // Enchérir
            Route::post('/{auction}/buyout', [AuctionController::class, 'buyout']);  // Achat instantané
            Route::delete('/{auction}', [AuctionController::class, 'delete']);       // Supprimer/Annuler (owner)
        });
    });
    
    Route::get('/invite/{code}', [GuildInvitationController::class, 'join']);
});
