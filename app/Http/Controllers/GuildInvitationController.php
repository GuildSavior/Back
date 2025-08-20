<?php

namespace App\Http\Controllers;

use App\Models\Guild;
use App\Models\GuildInvitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class GuildInvitationController extends Controller
{
    /**
     * Créer une invitation pour la guilde
     */
    public function create(Request $request)
    {
        $user = Auth::user();
        $guild = $user->ownedGuilds()->first();

        if (!$guild) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne possédez aucune guilde.'
            ], 403);
        }

        $request->validate([
            'max_uses' => 'nullable|integer|min:1|max:100',
            'expires_in_hours' => 'nullable|integer|min:1|max:168',
        ]);

        try {
            $expiresAt = null;
            if ($request->expires_in_hours) {
                $expiresAt = now()->addHours($request->expires_in_hours);
            }

            $invitation = GuildInvitation::create([
                'guild_id' => $guild->id,
                'created_by' => $user->id,
                'code' => GuildInvitation::generateCode(),
                'max_uses' => $request->max_uses,
                'expires_at' => $expiresAt,
            ]);

            // ⭐ UTILISER LA MÊME LOGIQUE QUE STRIPE
            $backendUrl = config('services.app.backend_url') ?: env('BACKEND_URL', env('APP_URL', 'http://127.0.0.1:8000'));
            $inviteUrl = $backendUrl . "/invite/{$invitation->code}";

            Log::info('Invitation créée:', [
                'guild_id' => $guild->id,
                'code' => $invitation->code,
                'created_by' => $user->id,
                'url' => $inviteUrl
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Invitation créée avec succès',
                'invitation' => [
                    'id' => $invitation->id,
                    'code' => $invitation->code,
                    'url' => $inviteUrl,
                    'max_uses' => $invitation->max_uses,
                    'uses_count' => $invitation->uses_count,
                    'expires_at' => $invitation->expires_at,
                    'is_active' => $invitation->is_active,
                    'is_valid' => $invitation->isValid(),
                    'created_at' => $invitation->created_at,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur création invitation:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'invitation'
            ], 500);
        }
    }

    /**
     * Lister les invitations de ma guilde
     */
    public function index()
    {
        $user = Auth::user();
        $guild = $user->ownedGuilds()->first();

        if (!$guild) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne possédez aucune guilde.'
            ], 403);
        }

        $invitations = GuildInvitation::where('guild_id', $guild->id)
                                     ->with('creator')
                                     ->orderBy('created_at', 'desc')
                                     ->get()
                                     ->map(function ($invitation) {
                                         // ⭐ UTILISER LA MÊME LOGIQUE QUE STRIPE
                                         $backendUrl = config('services.app.backend_url') ?: env('BACKEND_URL', env('APP_URL', 'http://127.0.0.1:8000'));
                                         
                                         return [
                                             'id' => $invitation->id,
                                             'code' => $invitation->code,
                                             'url' => $backendUrl . "/invite/{$invitation->code}",
                                             'max_uses' => $invitation->max_uses,
                                             'uses_count' => $invitation->uses_count,
                                             'expires_at' => $invitation->expires_at,
                                             'is_active' => $invitation->is_active,
                                             'is_valid' => $invitation->isValid(),
                                             'created_at' => $invitation->created_at,
                                         ];
                                     });

        return response()->json([
            'success' => true,
            'invitations' => $invitations
        ]);
    }

    /**
     * Rejoindre une guilde via invitation
     */
    public function join($code)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Vous devez être connecté pour rejoindre une guilde.',
                'redirect_to_login' => true
            ], 401);
        }

        if ($user->isInAnyGuild()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous êtes déjà dans une guilde.'
            ], 403);
        }

        $invitation = GuildInvitation::where('code', $code)->first();

        if (!$invitation) {
            return response()->json([
                'success' => false,
                'message' => 'Invitation invalide ou expirée.'
            ], 404);
        }

        if (!$invitation->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'Cette invitation n\'est plus valide.'
            ], 410);
        }

        $guild = $invitation->guild;

        if ($guild->isFull()) {
            return response()->json([
                'success' => false,
                'message' => 'Cette guilde est pleine.'
            ], 403);
        }

        try {
            // ⭐ AJOUTER L'UTILISATEUR À LA GUILDE
            $guild->members()->attach($user->id, [
                'role' => 'member',
                'joined_at' => now(),
            ]);

            // ⭐ INCRÉMENTER LE COMPTEUR DE LA GUILDE
            $guild->increment('member_count');

            // ⭐ INCRÉMENTER LE COMPTEUR D'UTILISATION DE L'INVITATION
            $invitation->increment('uses_count');

            Log::info('Utilisateur a rejoint une guilde via invitation:', [
                'user_id' => $user->id,
                'guild_id' => $guild->id,
                'invitation_code' => $code,
                'new_member_count' => $guild->fresh()->member_count
            ]);

            return response()->json([
                'success' => true,
                'message' => "Vous avez rejoint la guilde '{$guild->name}' avec succès !",
                'guild' => $guild->load('owner', 'members')
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur rejoindre guilde via invitation:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la tentative de rejoindre la guilde'
            ], 500);
        }
    }

    /**
     * Désactiver une invitation
     */
    public function deactivate($invitation)
    {
        $user = Auth::user();
        $guild = $user->ownedGuilds()->first();

        if (!$guild) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne possédez aucune guilde.'
            ], 403);
        }

        $invitationRecord = GuildInvitation::where('id', $invitation)
                                ->where('guild_id', $guild->id)
                                ->first();

        if (!$invitationRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Invitation introuvable.'
            ], 404);
        }

        $invitationRecord->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Invitation désactivée avec succès.'
        ]);
    }
    /**
 * Supprimer définitivement une invitation
 */
public function delete($invitation)
{
    $user = Auth::user();
    $guild = $user->ownedGuilds()->first();

    if (!$guild) {
        return response()->json([
            'success' => false,
            'message' => 'Vous ne possédez aucune guilde.'
        ], 403);
    }

    $invitationRecord = GuildInvitation::where('id', $invitation)
                            ->where('guild_id', $guild->id)
                            ->first();

    if (!$invitationRecord) {
        return response()->json([
            'success' => false,
            'message' => 'Invitation introuvable.'
        ], 404);
    }

    // ✅ Suppression définitive
    $invitationRecord->delete();

    return response()->json([
        'success' => true,
        'message' => 'Invitation supprimée définitivement.'
    ]);
}

/**
 * Nettoyer toutes les invitations inactives
 */
public function cleanupInactive()
{
    $user = Auth::user();
    $guild = $user->ownedGuilds()->first();

    if (!$guild) {
        return response()->json([
            'success' => false,
            'message' => 'Vous ne possédez aucune guilde.'
        ], 403);
    }

    // ✅ Compter et supprimer toutes les invitations inactives
    $inactiveCount = GuildInvitation::where('guild_id', $guild->id)
                                   ->where('is_active', false)
                                   ->count();

    if ($inactiveCount === 0) {
        return response()->json([
            'success' => false,
            'message' => 'Aucune invitation inactive à supprimer.'
        ], 404);
    }

    GuildInvitation::where('guild_id', $guild->id)
                   ->where('is_active', false)
                   ->delete();

    return response()->json([
        'success' => true,
        'message' => "{$inactiveCount} invitation(s) inactive(s) supprimée(s)."
    ]);
}
}
