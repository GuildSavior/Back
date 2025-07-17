<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
{
    // Messages personnalisés pour les erreurs de validation
    $messages = [
        'username.required' => __('lang.username.required'),
        'password.required' => __('lang.password.required'),
        'password.min' => __('lang.password.min'),
    ];

    // Validation des champs
    $validator = Validator::make($request->all(), [
        'username' => 'required',
        'password' => 'required',
    ], $messages);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 422);
    }

    // Chercher l'utilisateur par username
    $user = User::where('username', $request->input('username'))->first();
    if (!$user) {
        return response()->json([
            'message' => __('lang.login.bad_credentials'),
        ], 401);
    }

    // Vérifier si le mot de passe est correct
    if (!Hash::check($request->input('password'), $user->password)) {
        return response()->json([
            'message' => __('lang.login.bad_credentials'),
        ], 401);
    }

    // Créer un token
    $token = $user->createToken('auth_token')->plainTextToken;
    $user->refresh_token = $token;
    $user->remember_token = $token;
    // Tu peux aussi gérer un device_token ici, si nécessaire
    //$user->device_token = $request->input('device_token');
    $user->save();

    // Retourner la réponse avec le token
    return response()->json([
        'access_token' => $token,
        'token_type' => 'Bearer',
        'user' => $user,  // Retourner les détails de l'utilisateur si nécessaire
    ]);
}

public function redirectToDiscord()
{
    $discordUrl = 'https://discord.com/api/oauth2/authorize?' . http_build_query([
        'client_id' => env('DISCORD_CLIENT_ID'),
        'redirect_uri' => env('DISCORD_REDIRECT_URI'),
        'response_type' => 'code',
        'scope' => 'identify email',
        'prompt' => 'select_account' // ⭐ AJOUTER ÇA
    ]);

    return response()->json(['url' => $discordUrl]);
}


}
