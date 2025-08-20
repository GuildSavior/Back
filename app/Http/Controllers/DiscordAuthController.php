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

    public function redirectToDiscordWithLogout()
    {
        // URL de déconnexion Discord + redirection vers auth
        $logoutUrl = 'https://discord.com/api/auth/logout';
        $authUrl = url('/api/auth/discord/select-account');
        
        // Rediriger vers une page intermédiaire
        return view('discord-logout', compact('logoutUrl', 'authUrl'));
    }

    public function redirectToDiscordWithSelect()
    {
        return Socialite::driver('discord')
            ->with([
                'prompt' => 'consent',
                'approval_prompt' => 'force',
            ])
            ->redirect();
    }

    public function handleDiscordCallback()
    {
        try {
            Log::info('Discord callback called.');
            
            $discordUser = Socialite::driver('discord')->user();
            Log::info('Discord user data:', (array) $discordUser);

            if (!$discordUser->getEmail()) {
                Log::error('Discord email is null.');
                return response()->json(['error' => 'Email Discord non disponible.'], 400);
            }

            $user = User::updateOrCreate(
                ['discord_id' => $discordUser->id],
                [
                    'username' => $discordUser->name ?? $discordUser->nickname,
                    'email' => $discordUser->getEmail(),
                    'avatar' => $discordUser->avatar,
                ]
            );

            Log::info('User saved in DB:', $user->toArray());

            // Génère un token d'authentification
            $token = $user->createToken('authToken', ['expires_in' => 7200])->plainTextToken;

            // ⭐ CORRIGER L'URL AVEC LE SLASH FINAL
            $frontUrl = rtrim(env('FRONT_URL', 'http://127.0.0.1:4200'), '/');
            $redirectUrl = $frontUrl . '/discord-callback/'; // ⭐ AVEC SLASH FINAL

            Log::info('Redirecting to frontend:', [
                'front_url' => $frontUrl,
                'full_redirect' => $redirectUrl
            ]);

            return redirect($redirectUrl)
                ->withCookie(cookie(
                    'auth_token',    
                    $token,          
                    120,             // 2 heures
                    '/',             // path (tout le site)
                    $this->getCookieDomain(), // ⭐ DOMAINE DYNAMIQUE
                    false,           // secure (false pour http)
                    false,           // httpOnly (false pour JS)
                    'Lax'            // ⭐ sameSite Lax pour éviter les problèmes
                ));

        } catch (\Exception $e) {
            Log::error('Discord Auth Error: ' . $e->getMessage());
            return response()->json(['error' => 'Erreur lors de l\'authentification.'], 500);
        }
    }

    public function logout(Request $request) 
    {
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
        }
        
        return response()->json(['message' => 'Déconnexion réussie.'])
            ->withCookie(cookie()->forget('auth_token')); // Supprime le cookie
    }

    /**
     * ⭐ CORRIGER getCookieDomain pour ton nouveau domaine
     */
    private function getCookieDomain(): ?string
    {
        $frontUrl = env('FRONT_URL', 'http://127.0.0.1:4200');
        
        // Si c'est localhost/127.0.0.1, pas de domaine spécifique
        if (str_contains($frontUrl, '127.0.0.1') || str_contains($frontUrl, 'localhost')) {
            return null;
        }
        
        // ⭐ AJOUTER LE SUPPORT DE TON NOUVEAU DOMAINE
        if (str_contains($frontUrl, 'guildsavior.online')) {
            return '.guildsavior.online'; // Cookie partagé entre sous-domaines
        }
        
        // Si c'est une IP de production
        if (str_contains($frontUrl, '82.112.255.241')) {
            return null;
        }
        
        return null;
    }
}
