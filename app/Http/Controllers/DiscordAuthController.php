<?php

namespace App\Http\Controllers;

use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class DiscordAuthController extends Controller
{
    public function redirectToDiscord()
    {
        return Socialite::driver('discord')->redirect();
    }

    public function handleDiscordCallback()
    {
        try {
            // DEBUG : Log l'arrivée dans la fonction
            Log::info('Discord callback called.');

            // Récupère les infos de l'utilisateur Discord
            $discordUser = Socialite::driver('discord')->user();

            // DEBUG : Vérifie si on récupère bien les infos de Discord
            Log::info('Discord user data:', (array) $discordUser);

            // Vérifie si l'utilisateur a bien un email (parfois il est null)
            if (!$discordUser->getEmail()) {
                Log::error('Discord email is null.');
                return response()->json(['error' => 'Email Discord non disponible.'], 400);
            }

            // Vérifie si l'utilisateur existe, sinon, le créer
            $user = User::updateOrCreate(
                ['discord_id' => $discordUser->id],
                [
                    'username' => $discordUser->name ?? $discordUser->nickname,
                    'email' => $discordUser->getEmail(),
                    'avatar' => $discordUser->avatar,
                ]
            );

            // DEBUG : Vérifie si l'utilisateur est bien créé ou mis à jour
            Log::info('User saved in DB:', $user->toArray());

            // Génère un token d’authentification pour Angular
            $token = $user->createToken('authToken',['expires_in' => 7200])->plainTextToken;
            $redirectUrl = 'http://localhost:4200/discord-callback?token=' . $token . '&id=' . $discordUser->id;

            return redirect()->away($redirectUrl);

        } catch (\Exception $e) {
            // Log l'erreur pour voir ce qui se passe
            Log::error('Discord Auth Error: ' . $e->getMessage());

            return response()->json(['error' => 'Erreur lors de l\'authentification.'], 500);
        }
    }

    public function logout(Request $request){
            $request->user()->currentAccessToken()->delete();
            return response()->json(['message' => 'Déconnexion réussie.']);
        }
}
