<?php

namespace App\Http\Controllers;

use App\Models\Player;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class PlayerController extends Controller
{
    /**
     * Récupérer le profil joueur de l'utilisateur connecté
     */
    public function show()
    {
        $user = Auth::user();
        $player = $user->player;

        if (!$player) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun profil joueur trouvé',
                'player' => null
            ]);
        }

        return response()->json([
            'success' => true,
            'player' => [
                'id' => $player->id,
                'name' => $player->name,
                'level' => $player->level,
                'class' => $player->class,
                'dkp' => $player->dkp, // ⭐ LECTURE SEULE
                'events_joined' => $player->events_joined, // ⭐ LECTURE SEULE
                'created_at' => $player->created_at,
                'updated_at' => $player->updated_at,
            ]
        ]);
    }

    /**
     * Créer ou mettre à jour le profil joueur
     */
    public function createOrUpdate(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|min:2|max:50|regex:/^[a-zA-Z0-9\s\-_.]+$/',
            'level' => 'required|integer|min:1|max:100',
            'class' => ['required', 'string', Rule::in([
                'dps', 'tank', 'support', 'mage', 'range'
            ])],
        ], [
            'name.required' => 'Le nom du personnage est obligatoire',
            'name.regex' => 'Le nom ne peut contenir que des lettres, chiffres, espaces, tirets et underscores',
            'level.min' => 'Le niveau minimum est 1',
            'level.max' => 'Le niveau maximum est 100',
            'class.in' => 'Cette classe n\'est pas valide',
        ]);

        try {
            $player = Player::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'name' => $request->name,
                    'level' => $request->level,
                    'class' => $request->class,
                    // ⭐ NE PAS PERMETTRE LA MODIFICATION DES DKP ET EVENTS
                ]
            );

            Log::info('Profil joueur mis à jour:', [
                'user_id' => $user->id,
                'player_id' => $player->id,
                'name' => $player->name
            ]);

            return response()->json([
                'success' => true,
                'message' => $player->wasRecentlyCreated ? 
                    'Profil joueur créé avec succès' : 
                    'Profil joueur mis à jour avec succès',
                'player' => [
                    'id' => $player->id,
                    'name' => $player->name,
                    'level' => $player->level,
                    'class' => $player->class,
                    'dkp' => $player->dkp,
                    'events_joined' => $player->events_joined,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur mise à jour profil joueur:', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du profil'
            ], 500);
        }
    }

    /**
     * Supprimer le profil joueur
     */
    public function destroy()
    {
        $user = Auth::user();
        $player = $user->player;

        if (!$player) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun profil joueur à supprimer'
            ], 404);
        }

        try {
            $player->delete();

            Log::info('Profil joueur supprimé:', [
                'user_id' => $user->id,
                'player_name' => $player->name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Profil joueur supprimé avec succès'
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur suppression profil joueur:', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du profil'
            ], 500);
        }
    }

    /**
     * Lister tous les profils joueurs (pour les admins/guildes)
     */
    public function index()
    {
        try {
            $players = Player::with('user')
                            ->orderBy('dkp', 'desc')
                            ->get()
                            ->map(function ($player) {
                                return [
                                    'id' => $player->id,
                                    'name' => $player->name,
                                    'level' => $player->level,
                                    'class' => $player->class,
                                    'dkp' => $player->dkp,
                                    'events_joined' => $player->events_joined,
                                    'username' => $player->user->username,
                                ];
                            });

            return response()->json([
                'success' => true,
                'players' => $players
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur liste joueurs:', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des joueurs'
            ], 500);
        }
    }

    /**
     * Afficher le profil d'un joueur spécifique (pour les autres utilisateurs)
     */
    public function showPlayer(Player $player)
    {
        try {
            // ⭐ CHARGER LES RELATIONS NÉCESSAIRES
            $player->load('user:id,username,avatar');

            return response()->json([
                'success' => true,
                'player' => [
                    'id' => $player->id,
                    'name' => $player->name,
                    'level' => $player->level,
                    'class' => $player->class,
                    'dkp' => $player->dkp,
                    'events_joined' => $player->events_joined,
                    'created_at' => $player->created_at,
                    'updated_at' => $player->updated_at,
                    // ⭐ INFOS UTILISATEUR (sans données sensibles)
                    'user' => [
                        'username' => $player->user->username,
                        'avatar' => $player->user->avatar,
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur affichage profil joueur:', [
                'player_id' => $player->id ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du profil'
            ], 500);
        }
    }
}
