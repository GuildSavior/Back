<?php

namespace App\Http\Controllers;

use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;

class DiscordAuthController extends Controller
{
    public function redirectToDiscord()
    {
        return Socialite::driver('discord')->redirect();
    }

    public function handleDiscordCallback(Request $request)
{
    $userController = new UserController();
    try {
        $discordUser = Socialite::driver('discord')->user();

        $user = User::where('discord_id', $discordUser->getId())->first();
        if ($user) {
            $user = $userController->updateUserFromDiscord($user, $discordUser);
        } else {
            $user = $userController->createUserFromDiscord($discordUser);
        }

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Utilisateur introuvable ou non créé.',
            ], 500);
        }

        // Générer un token pour l'utilisateur
        $token = $user->createToken('DiscordLoginToken')->plainTextToken;
        $user->refresh_token = $token;
        $user->remember_token = $token;
        $user->save();

        return redirect()->away('http://localhost:4200/toto?token=' . $token);

    } catch (\Exception $e) {
        dump("error");
        if ($request->query('response_type') === 'json') {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        } else {
            return redirect()->away('http://localhost:4200/error?message=' . urlencode($e->getMessage()));
        }
    }
}

    /*Route::get('/auth/discord/callback', function () {
    $user = Socialite::driver('discord')->user();

    // Logique de connexion ou création d'utilisateur
    $token = $user->token;
    $discordUser = [
        'id' => $user->getId(),
        'name' => $user->getName(),
        'avatar' => $user->getAvatar(),
        'email' => $user->getEmail(),
    ];
    // Générez un JWT ou autre token pour Angular
    return response()->json([
        'token' => $token,
        'user' => $discordUser,
        'redirectUrl' => '/dashboard',
    ]);
})->middleware('web');*/
}
