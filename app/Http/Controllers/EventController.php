<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class EventController extends Controller
{
    /**
     * Lister les événements de ma guilde
     */
    public function index()
    {
        $user = Auth::user();
        $guild = $user->getCurrentGuild();

        if (!$guild) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes dans aucune guilde.'
            ], 403);
        }

        $events = Event::where('guild_id', $guild->id)
                      ->with(['creator', 'participants'])
                      ->orderBy('start_time', 'desc')
                      ->get()
                      ->map(function ($event) use ($user) {
                          $userParticipation = $event->participants()
                              ->where('user_id', $user->id)
                              ->first();

                          return [
                              'id' => $event->id,
                              'name' => $event->name,
                              'description' => $event->description,
                              'start_time' => $event->start_time,
                              'end_time' => $event->end_time,
                              'dkp_reward' => $event->dkp_reward,
                              'is_active' => $event->is_active,
                              'status' => $event->isUpcoming() ? 'upcoming' : 
                                         ($event->isOngoing() ? 'ongoing' : 'finished'),
                              'created_by' => $event->creator->username,
                              'participant_count' => $event->participants->count(),
                              'confirmed_count' => $event->confirmedParticipants->count(),
                              'attended_count' => $event->attendedParticipants->count(),
                              'user_participation' => $userParticipation ? [
                                  'status' => $userParticipation->pivot->status,
                                  'dkp_earned' => $userParticipation->pivot->dkp_earned,
                                  'attended_at' => $userParticipation->pivot->attended_at,
                              ] : null,
                              'created_at' => $event->created_at,
                          ];
                      });

        return response()->json([
            'success' => true,
            'events' => $events
        ]);
    }

    /**
     * Créer un événement (owner uniquement)
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'start_time' => 'required|date|after:now',
            'end_time' => 'required|date|after:start_time',
            'dkp_reward' => 'required|integer|min:1|max:1000',
        ]);

        try {
            $event = Event::create([
                'name' => $request->name,
                'description' => $request->description,
                'guild_id' => $guild->id,
                'created_by' => $user->id,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'dkp_reward' => $request->dkp_reward,
                'access_code' => Event::generateAccessCode(),
            ]);

            Log::info('Événement créé:', [
                'event_id' => $event->id,
                'guild_id' => $guild->id,
                'created_by' => $user->id,
                'access_code' => $event->access_code
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Événement créé avec succès',
                'event' => [
                    'id' => $event->id,
                    'name' => $event->name,
                    'description' => $event->description,
                    'start_time' => $event->start_time,
                    'end_time' => $event->end_time,
                    'dkp_reward' => $event->dkp_reward,
                    'access_code' => $event->access_code, // ⭐ SEUL L'OWNER VOIT LE CODE
                    'created_at' => $event->created_at,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur création événement:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'événement'
            ], 500);
        }
    }

    /**
     * Participer à un événement (marquer comme intéressé)
     */
    public function participate(Request $request, $eventId)
    {
        $user = Auth::user();
        $guild = $user->getCurrentGuild();

        if (!$guild) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes dans aucune guilde.'
            ], 403);
        }

        $event = Event::where('id', $eventId)
                     ->where('guild_id', $guild->id)
                     ->first();

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Événement introuvable.'
            ], 404);
        }

        if ($event->isFinished()) {
            return response()->json([
                'success' => false,
                'message' => 'Cet événement est terminé.'
            ], 410);
        }

        try {
            // Ajouter ou mettre à jour la participation
            $event->participants()->syncWithoutDetaching([
                $user->id => [
                    'status' => 'interested',
                    'confirmed_at' => null,
                    'attended_at' => null,
                    'dkp_earned' => 0,
                ]
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Vous êtes maintenant marqué comme intéressé par cet événement.'
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur participation événement:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'inscription à l\'événement'
            ], 500);
        }
    }

    /**
     * Confirmer sa venue à un événement
     */
    public function confirm(Request $request, $eventId)
    {
        $user = Auth::user();
        $guild = $user->getCurrentGuild();

        if (!$guild) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes dans aucune guilde.'
            ], 403);
        }

        $event = Event::where('id', $eventId)
                     ->where('guild_id', $guild->id)
                     ->first();

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Événement introuvable.'
            ], 404);
        }

        if ($event->isFinished()) {
            return response()->json([
                'success' => false,
                'message' => 'Cet événement est terminé.'
            ], 410);
        }

        try {
            $event->participants()->syncWithoutDetaching([
                $user->id => [
                    'status' => 'confirmed',
                    'confirmed_at' => now(),
                ]
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Votre venue à l\'événement a été confirmée.'
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur confirmation événement:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la confirmation'
            ], 500);
        }
    }

    /**
     * Valider sa présence avec le code d'accès et recevoir les DKP
     */
    public function validateAttendance(Request $request, $eventId)
    {
        $user = Auth::user();
        $guild = $user->getCurrentGuild();

        if (!$guild) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes dans aucune guilde.'
            ], 403);
        }

        $request->validate([
            'access_code' => 'required|string|size:8'
        ]);

        $event = Event::where('id', $eventId)
                     ->where('guild_id', $guild->id)
                     ->first();

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Événement introuvable.'
            ], 404);
        }

        if (!$event->isOngoing() && !$event->isFinished()) {
            return response()->json([
                'success' => false,
                'message' => 'L\'événement n\'a pas encore commencé.'
            ], 400);
        }

        if (strtoupper($request->access_code) !== $event->access_code) {
            return response()->json([
                'success' => false,
                'message' => 'Code d\'accès incorrect.'
            ], 400);
        }

        // Vérifier si l'utilisateur participe à l'événement
        $participation = $event->participants()->where('user_id', $user->id)->first();
        
        if (!$participation) {
            return response()->json([
                'success' => false,
                'message' => 'Vous devez d\'abord vous inscrire à cet événement.'
            ], 400);
        }

        if ($participation->pivot->status === 'attended') {
            return response()->json([
                'success' => false,
                'message' => 'Vous avez déjà validé votre présence à cet événement.'
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Mettre à jour la participation
            $event->participants()->updateExistingPivot($user->id, [
                'status' => 'attended',
                'attended_at' => now(),
                'dkp_earned' => $event->dkp_reward,
            ]);

            // Ajouter les DKP à l'utilisateur
            $user->increment('dkp', $event->dkp_reward);

            DB::commit();

            Log::info('Présence validée événement:', [
                'event_id' => $event->id,
                'user_id' => $user->id,
                'dkp_earned' => $event->dkp_reward
            ]);

            return response()->json([
                'success' => true,
                'message' => "Présence validée ! Vous avez gagné {$event->dkp_reward} DKP.",
                'dkp_earned' => $event->dkp_reward,
                'total_dkp' => $user->fresh()->dkp
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur validation présence:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la validation de présence'
            ], 500);
        }
    }
}
