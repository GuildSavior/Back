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

    
public function logout(Request $request): JsonResponse
{
    $user = Auth::user();  // Récupérer l'utilisateur authentifié via le token
    // Vérifier si l'utilisateur est authentifié
    if ($user) {
        // Révoquer le token actuellement utilisé par l'utilisateur
        $user->currentAccessToken()->delete();  // Supprime le token actuel
        return response()->json([
            'message' => 'Successfully logged out.',
        ]);
    } else {
        return response()->json([
            'message' => 'No user authenticated.',
        ], 401);
    }
}

public function register(Request $request){
    try {
        $request->validate([
            'user_name' => 'required|string',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'phone_number' => 'required|string',
            'password' => 'required|string|min:8'
        ], [
            'user_name.required' => 'Le champ pseudo est requis.',
            'first_name.required' => 'Le champ prénom est requis.',
            'last_name.required' => 'Le champ nom est requis.',
            'email.required' => 'Le champ email est requis.',
            'email.email' => 'L\'adresse email doit être valide.',
            'email.unique' => 'L\'adresse email est déjà utilisée.',
            'phone_number.required' => 'Le champ numéro de téléphone est requis.',
            'password.required' => 'Le mot de passe est requis.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.'
        ]);

        $user = User::create([
            'user_name' => $request->user_name,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'password' => bcrypt($request->password),
            'is_deleted' => false
        ]);

        $tokenResult = $user->createToken('auth_token', ['expires_in' => 7200]);
        $token = $tokenResult->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'user_name' => $user->user_name,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone_number' => $user->phone_number
            ]
        ]);

    } catch (ValidationException $e) {
        return response()->json(['errors' => $e->validator->errors()], 422);
    }
}


}
