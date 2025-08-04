<?php

namespace App\Http\Controllers;

use App\Models\Guild;
use App\Models\User;
use App\Models\GuildInvitation; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class GuildController extends Controller
{
    /**
     * Créer une nouvelle guilde (premium uniquement)
     */
    public function create(Request $request)
    {
        $user = Auth::user();

        // Vérifier si l'utilisateur est premium
        if (!$user->canCreateGuild()) {
            return response()->json([
                'success' => false,
                'message' => 'Seuls les utilisateurs premium peuvent créer une guilde.'
            ], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:guilds,name',
            'description' => 'nullable|string|max:1000',
            'region' => 'nullable|string|max:100',
        ]);

        try {
            $guild = Guild::create([
                'name' => $request->name,
                'description' => $request->description,
                'creation_date' => now(),
                'region' => $request->region,
                'owner_id' => $user->id,
                'member_count' => 1, // ⭐ INITIALISER À 1 (le créateur)
            ]);

            // Ajouter le créateur comme membre
            $guild->members()->attach($user->id, [
                'role' => 'owner',
                'joined_at' => now(),
            ]);

            // ⭐ PAS BESOIN DE METTRE À JOUR member_count car on l'a initialisé à 1

            Log::info('Guilde créée:', ['guild_id' => $guild->id, 'owner_id' => $user->id]);

            return response()->json([
                'success' => true,
                'message' => 'Guilde créée avec succès',
                'guild' => $guild->load('owner', 'members')
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur création guilde:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la guilde'
            ], 500);
        }
    }

    /**
     * Rejoindre une guilde
     */
    public function join(Request $request, Guild $guild)
    {
        $user = Auth::user();

        // Vérifier si l'utilisateur peut rejoindre une guilde
        if (!$user->canJoinGuild()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous êtes déjà dans une guilde. Quittez-la d\'abord.'
            ], 403);
        }

        // Vérifier si la guilde peut accueillir ce membre
        if (!$guild->canUserJoin($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de rejoindre cette guilde.'
            ], 403);
        }

        if ($guild->isFull()) {
            return response()->json([
                'success' => false,
                'message' => 'Cette guilde est pleine.'
            ], 403);
        }

        try {
            $guild->members()->attach($user->id, [
                'role' => 'member',
                'joined_at' => now(),
            ]);

            // ⭐ METTRE À JOUR LE COMPTEUR
            $guild->increment('member_count');

            Log::info('Utilisateur a rejoint une guilde:', [
                'user_id' => $user->id,
                'guild_id' => $guild->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Vous avez rejoint la guilde avec succès',
                'guild' => $guild->load('owner', 'members')
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur rejoindre guilde:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la tentative de rejoindre la guilde'
            ], 500);
        }
    }

    /**
     * Quitter sa guilde actuelle
     */
    public function leave(Request $request)
    {
        $user = Auth::user();

        if (!$user->isInAnyGuild()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes dans aucune guilde.'
            ], 400);
        }

        try {
            // ⭐ RÉCUPÉRER LA GUILDE AVANT DE QUITTER
            $guild = $user->getCurrentGuild();
            
            $user->leaveCurrentGuild();

            // ⭐ METTRE À JOUR LE COMPTEUR SI CE N'EST PAS LE OWNER
            if ($guild && $guild->owner_id !== $user->id) {
                $guild->decrement('member_count');
            }

            Log::info('Utilisateur a quitté sa guilde:', ['user_id' => $user->id]);

            return response()->json([
                'success' => true,
                'message' => 'Vous avez quitté la guilde avec succès'
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur quitter guilde:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la tentative de quitter la guilde'
            ], 500);
        }
    }

    /**
     * Lister toutes les guildes publiques
     */
    public function index()
    {
        try {
            $guilds = Guild::with(['owner', 'members'])
                           ->get()
                           ->map(function ($guild) {
                               return [
                                   'id' => $guild->id,
                                   'name' => $guild->name,
                                   'description' => $guild->description,
                                   'region' => $guild->region,
                                   'creation_date' => $guild->creation_date,
                                   'owner' => $guild->owner->username,
                                   'members_count' => $guild->member_count, // ⭐ UTILISER member_count directement
                                   'can_join' => Auth::user()->canJoinGuild() && $guild->canUserJoin(Auth::user())
                               ];
                           });

            return response()->json([
                'success' => true,
                'guilds' => $guilds
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur liste guildes:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des guildes'
            ], 500);
        }
    }

    /**
     * Obtenir la guilde actuelle de l'utilisateur
     */
    public function current()
    {
        $user = Auth::user();
        $guild = $user->getCurrentGuild();

        if (!$guild) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes dans aucune guilde'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'guild' => $guild->load('owner', 'members'),
            'user_role' => $guild->owner_id === $user->id ? 'owner' : 'member'
        ]);
    }
    /**
     * Récupérer les membres de ma guilde
     */
    public function getMembers()
    {
        $user = Auth::user();
        
        // ⭐ UTILISER LA NOUVELLE LOGIQUE avec getCurrentGuild()
        $guild = $user->getCurrentGuild();
        
        if (!$guild) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas dans une guilde'
            ], 404);
        }

        try {
            // ⭐ CHARGER LES MEMBRES VIA LA RELATION guild_members avec les profils joueurs
            $guild->load(['members.player']);
            
            $members = $guild->members->map(function ($member) use ($guild) {
                return [
                    'id' => $member->id,
                    'username' => $member->username,
                    'avatar' => $member->avatar,
                    'discord_id' => $member->discord_id,
                    'joined_at' => $member->pivot->joined_at, // ⭐ DEPUIS guild_members
                    'role' => $member->pivot->role, // ⭐ DEPUIS guild_members
                    'is_owner' => $guild->owner_id === $member->id, // ⭐ COMPARAISON CORRECTE
                    'player' => $member->player ? [
                        'id' => $member->player->id,
                        'name' => $member->player->name,
                        'level' => $member->player->level,
                        'class' => $member->player->class,
                        'dkp' => $member->player->dkp,
                        'events_joined' => $member->player->events_joined,
                    ] : null
                ];
            });

            return response()->json([
                'success' => true,
                'guild' => [
                    'id' => $guild->id,
                    'name' => $guild->name,
                    'description' => $guild->description,
                    'member_count' => $guild->member_count,
                    'owner_id' => $guild->owner_id,
                ],
                'members' => $members,
                'user_role' => $user->getRoleInCurrentGuild() // ⭐ RÔLE DE L'UTILISATEUR ACTUEL
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur récupération membres guilde:', [
                'user_id' => $user->id,
                'guild_id' => $guild->id ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des membres'
            ], 500);
        }
    }

    /**
     * Dissoudre/Supprimer une guilde (owner uniquement)
     */
    public function disband(Request $request)
    {
        $user = Auth::user();
        
        // Récupérer la guilde possédée par l'utilisateur
        $guild = $user->ownedGuilds()->first();

        if (!$guild) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne possédez aucune guilde à dissoudre.'
            ], 403);
        }

        try {
            $guildName = $guild->name;
            $memberCount = $guild->member_count;

            // ⭐ SUPPRIMER TOUTES LES INVITATIONS DE LA GUILDE
            GuildInvitation::where('guild_id', $guild->id)->delete();

            // ⭐ DÉTACHER TOUS LES MEMBRES (ils n'auront plus de guilde)
            $guild->members()->detach();

            // ⭐ SUPPRIMER LA GUILDE COMPLÈTEMENT
            $guild->delete();

            Log::info('Guilde dissoute par l\'owner:', [
                'owner_id' => $user->id,
                'guild_name' => $guildName,
                'members_affected' => $memberCount
            ]);

            return response()->json([
                'success' => true,
                'message' => "La guilde '{$guildName}' a été dissoute avec succès. {$memberCount} membres ont été libérés."
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur dissolution guilde:', [
                'owner_id' => $user->id,
                'guild_id' => $guild->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la dissolution de la guilde'
            ], 500);
        }
    }
}
