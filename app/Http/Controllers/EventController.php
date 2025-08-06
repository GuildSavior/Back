<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Player;
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
                              'access_code' => $event->access_code,
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

        // ⭐ DÉLAI DE GRÂCE DE 30 MINUTES APRÈS LA FIN
        $graceTime = 30; // minutes
        $eventEndTime = $event->end_time;
        $graceEndTime = $eventEndTime->addMinutes($graceTime);
        
        if (!$event->isOngoing()) {
            if ($event->isFinished()) {
                // Vérifier si on est encore dans la période de grâce
                if (now()->isAfter($graceEndTime)) {
                    $minutesSinceEnd = now()->diffInMinutes($eventEndTime);
                    return response()->json([
                        'success' => false,
                        'message' => "Cet événement est terminé depuis {$minutesSinceEnd} minutes. Vous ne pouvez plus valider votre présence (délai de grâce de {$graceTime} minutes dépassé)."
                    ], 410);
                }
                // ⭐ ON EST DANS LA PÉRIODE DE GRÂCE - AUTORISER
                Log::info('Validation dans période de grâce:', [
                    'event_id' => $eventId,
                    'user_id' => $user->id,
                    'minutes_since_end' => now()->diffInMinutes($eventEndTime)
                ]);
            } else {
                // Événement pas encore commencé
                return response()->json([
                    'success' => false,
                    'message' => 'L\'événement n\'a pas encore commencé. Vous pourrez valider votre présence une fois qu\'il aura commencé.'
                ], 400);
            }
        }

        if (strtoupper($request->access_code) !== $event->access_code) {
            return response()->json([
                'success' => false,
                'message' => 'Code d\'accès incorrect.'
            ], 400);
        }

        // ⭐ VÉRIFIER QUE L'UTILISATEUR A UN PLAYER
        $player = $user->player;
        if (!$player) {
            return response()->json([
                'success' => false,
                'message' => 'Vous devez créer un profil joueur avant de participer aux événements.'
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

            // ⭐ NOUVEAU : AJOUTER LES DKP À LA GUILDE SPÉCIFIQUE
            $guildDkp = $user->getOrCreateGuildDkp($guild);
            $guildDkp->addDkp($event->dkp_reward);
            $guildDkp->incrementEventsJoined();

            DB::commit();

            // ⭐ RÉCUPÉRER LES NOUVELLES VALEURS
            $currentDkp = $user->getCurrentGuildDkp();
            $currentEventsJoined = $user->getCurrentGuildEventsJoined();

            $isLateValidation = $event->isFinished();
            $message = $isLateValidation 
                ? "Présence validée (période de grâce) ! Vous avez gagné {$event->dkp_reward} DKP."
                : "Présence validée ! Vous avez gagné {$event->dkp_reward} DKP.";

            Log::info('Présence validée événement:', [
                'event_id' => $event->id,
                'user_id' => $user->id,
                'guild_id' => $guild->id,
                'player_id' => $player->id,
                'dkp_earned' => $event->dkp_reward,
                'total_guild_dkp' => $currentDkp,
                'guild_events_joined' => $currentEventsJoined,
                'validated_at' => now()->toDateTimeString(),
                'is_late_validation' => $isLateValidation
            ]);

            return response()->json([
                'success' => true,
                'message' => $message,
                'dkp_earned' => $event->dkp_reward,
                'total_dkp' => $currentDkp, // ⭐ DKP DE LA GUILDE ACTUELLE
                'events_joined' => $currentEventsJoined, // ⭐ ÉVÉNEMENTS DE LA GUILDE ACTUELLE
                'is_late_validation' => $isLateValidation
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

    /**
     * Supprimer un événement (owner uniquement)
     */
    public function delete(Request $request, $eventId)
    {
        $user = Auth::user();
        $guild = $user->ownedGuilds()->first();

        if (!$guild) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne possédez aucune guilde.'
            ], 403);
        }

        $event = Event::where('id', $eventId)
                     ->where('guild_id', $guild->id)
                     ->where('created_by', $user->id) // ⭐ Seul le créateur peut supprimer
                     ->first();

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Événement introuvable ou vous n\'avez pas les permissions.'
            ], 404);
        }

        // ⭐ RÈGLES DE SUPPRESSION PLUS FLEXIBLES
        $attendedParticipants = $event->participants()
            ->wherePivot('status', 'attended')
            ->count();

        // ⭐ INTERDIRE SEULEMENT SI ÉVÉNEMENT EN COURS AVEC DES VALIDATIONS
        if ($event->isOngoing() && $attendedParticipants > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer un événement en cours avec des participants qui ont validé leur présence.'
            ], 400);
        }

        // ⭐ PERMETTRE LA SUPPRESSION APRÈS LA FIN (avec avertissement)
        if ($event->isFinished() && $attendedParticipants > 0) {
            // Optionnel : demander une confirmation côté front
            Log::warning('Suppression événement terminé avec validations:', [
                'event_id' => $eventId,
                'attended_count' => $attendedParticipants
            ]);
        }

        try {
            DB::beginTransaction();

            // ⭐ SUPPRIMER LES PARTICIPATIONS D'ABORD (clé étrangère)
            $event->participants()->detach();

            // ⭐ SUPPRIMER L'ÉVÉNEMENT
            $event->delete();

            DB::commit();

            Log::info('Événement supprimé:', [
                'event_id' => $eventId,
                'event_name' => $event->name,
                'guild_id' => $guild->id,
                'deleted_by' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Événement supprimé avec succès.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur suppression événement:', [
                'event_id' => $eventId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'événement'
            ], 500);
        }
    }
}
