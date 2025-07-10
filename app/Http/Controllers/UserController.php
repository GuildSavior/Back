<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Guild;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use function Ramsey\Uuid\v1;

class UserController extends Controller
{
    public function getAuthenticatedUserInfos(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Utilisateur non authentifié.',
            ], 401);
        }

        // Charger les relations
        $user->load(['guild', 'role', 'subscription']);

        return response()->json([
            'status' => 'success',
            'user' => [
                'id' => $user->id,
                'discord_id' => $user->discord_id,
                'username' => $user->username,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'guild_id' => $user->guild_id,
                'role_id' => $user->role_id,
                'statut' => $user->statut,
                'total_dkp' => $user->total_dkp,
                'subscription' => $user->subscription,
                'is_premium' => $user->isPremium(),
                'guild' => $user->guild,
                'role' => $user->role,
            ],
        ], 200);
    }

    public function updateUserFromDiscord($user, $discordUser)
    {

        // Mettre à jour les informations de l'utilisateur
        $user->update([
            'username' => $discordUser->name,
            'email' => $discordUser->email,
            'avatar' => $discordUser->avatar,
        ]);
        return $user;
    }

     public function createUserFromDiscord($discordUser)
    {
        // Créer un nouvel utilisateur dans la base de données
        $user = new User();
        $user->email = $discordUser->email;
        $user->username = $discordUser->name;
        $user->avatar = $discordUser->avatar;
        $user-> discord_id = $discordUser->id;
        $user->save();
        return $user;
    }

     // Enregistre un nouveau joueur dans la base de données
     public function store(Request $request)
     {
         // Valide les données de la requête
         $request->validate([
             'username' => 'required|string|unique:user,username',  // Validation du nom d'utilisateur unique
             'guild_id' => 'required|exists:guilds,id',  // Validation de l'ID de la guilde
             'role_id' => 'nullable|exists:roles,id',  // Validation de l'ID du rôle (peut être null)
             'total_dkp' => 'nullable|integer',  // Validation des points DKP
         ]);

         // Crée un nouveau joueur
         $user = User::create([
             'username' => $request->username,
             'guild_id' => $request->guild_id,
             'role_id' => $request->role_id,
             'total_dkp' => $request->total_dkp ?? 0,  // Défaut à 0 si aucun total_dkp n'est fourni
         ]);

         // Redirige vers une autre page avec un message de succès
         return redirect()->route('user.index')->with('success', 'Joueur créé avec succès !');
     }

     // Affiche un joueur spécifique
     public function show($id)
     {
         // Trouve un joueur par son ID
         $user = User::findOrFail($id);

         return response()->json($user, Response::HTTP_OK);
     }

     // Affiche le formulaire pour modifier un joueur
     public function edit($id)
     {


     }

     // Met à jour les informations d'un joueur
     public function update(Request $request, $id)
     {
         // Trouve le joueur à mettre à jour
         $user = User::findOrFail($id);

         // Valide les données de la requête
         $request->validate([
             'username' => 'required|string|unique:user,username,' . $user->id,  // Validation du nom d'utilisateur unique (en ignorant l'ID actuel)
             'guild_id' => 'required|exists:guilds,id',
             'role_id' => 'nullable|exists:roles,id',
             'total_dkp' => 'nullable|integer',
         ]);

         // Met à jour les informations du joueur
         $user->update([
             'username' => $request->username,
             'guild_id' => $request->guild_id,
             'role_id' => $request->role_id,
             'total_dkp' => $request->total_dkp ?? 0,
         ]);

         // Redirige vers la page du joueur avec un message de succès
         return redirect()->route('user.show', $user->id)->with('success', 'Joueur mis à jour avec succès !');
     }

     // Supprime un joueur
     public function destroy($id)
     {
         // Trouve le joueur à supprimer
         $user = User::findOrFail($id);

         // Supprime le joueur
         $user->delete();

         // Redirige vers la liste des joueurs avec un message de succès
         return redirect()->route('users.index')->with('success', 'Joueur supprimé avec succès !');
     }
}
