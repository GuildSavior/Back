<?php

namespace App\Http\Controllers;

use App\Models\Auction;
use App\Models\AuctionBid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AuctionController extends Controller
{
    /**
     * Lister les enchères de ma guilde
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $guild = $user->getCurrentGuild();

        if (!$guild) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes dans aucune guilde.'
            ], 403);
        }

        $filter = $request->query('filter', 'all'); // all, active, upcoming, ended

        $query = Auction::where('guild_id', $guild->id)
                       ->with(['creator', 'currentWinner', 'winner']);

        // ⭐ FILTRES
        switch ($filter) {
            case 'active':
                $query->where('status', 'active');
                break;
            case 'upcoming':
                $query->where('status', 'upcoming');
                break;
            case 'ended':
                $query->whereIn('status', ['ended', 'cancelled']);
                break;
            case 'my_bids':
                $query->whereHas('bids', function($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
                break;
        }

        $auctions = $query->orderBy('end_time', 'asc')
                         ->get()
                         ->map(function ($auction) use ($user) {
                             // ⭐ METTRE À JOUR LE STATUT
                                $auction->updateStatus();
                                $auction->closeIfEnded();
                             
                             $userBid = $auction->bids()->where('user_id', $user->id)
                                               ->orderBy('bid_amount', 'desc')
                                               ->first();

                             return [
                                 'id' => $auction->id,
                                 'item_name' => $auction->item_name,
                                 'description' => $auction->description,
                                 'starting_price' => $auction->starting_price,
                                 'buyout_price' => $auction->buyout_price,
                                 'current_bid' => $auction->current_bid,
                                 'minimum_bid' => $auction->getMinimumBid(),
                                 'start_time' => $auction->start_time,
                                 'end_time' => $auction->end_time,
                                 'status' => $auction->status,
                                 'is_active' => $auction->isActive(),
                                 'can_bid' => $auction->isActive(),
                                 'can_buyout' => $auction->canBuyout(),
                                 'time_remaining' => $auction->isActive() ? 
                                     $auction->end_time->diffForHumans() : null,
                                 'creator' => $auction->creator->username,
                                 'is_owner' => $auction->created_by === $user->id,
                                 'current_winner' => $auction->currentWinner ? 
                                     $auction->currentWinner->username : null,
                                    'current_winner_id' => $auction->current_winner_id,
                                 'is_current_winner' => $auction->current_winner_id === $user->id,
                                 'winner' => $auction->winner ? 
                                     $auction->winner->username : null,
                                 'final_price' => $auction->final_price,
                                 'user_highest_bid' => $userBid ? $userBid->bid_amount : null,
                                 'total_bids' => $auction->bids()->count(),
                                 'created_at' => $auction->created_at,
                             ];
                         });

        return response()->json([
            'success' => true,
            'auctions' => $auctions,
            'user_dkp' => $user->getCurrentGuildDkp() // ⭐ DKP DISPONIBLES
        ]);
    }

    /**
     * Créer une enchère (owner uniquement)
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
            'item_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'starting_price' => 'required|integer|min:1|max:10000',
            'buyout_price' => 'nullable|integer|min:1|max:50000',
            'start_time' => 'required|date|after:now',
            'end_time' => 'required|date|after:start_time',
        ]);

        // ⭐ VALIDATION : buyout_price > starting_price
        if ($request->buyout_price && $request->buyout_price <= $request->starting_price) {
            return response()->json([
                'success' => false,
                'message' => 'Le prix d\'achat instantané doit être supérieur au prix de départ.'
            ], 400);
        }

        // ⭐ VALIDATION : durée maximum (par exemple 7 jours)
        $startTime = \Carbon\Carbon::parse($request->start_time);
        $endTime = \Carbon\Carbon::parse($request->end_time);
        $maxDurationHours = 168; // 7 jours
        
        if ($endTime->diffInHours($startTime) > $maxDurationHours) {
            return response()->json([
                'success' => false,
                'message' => "La durée maximale d'une enchère est de {$maxDurationHours} heures (7 jours)."
            ], 400);
        }

        try {
            $auction = Auction::create([
                'item_name' => $request->item_name,
                'description' => $request->description,
                'guild_id' => $guild->id,
                'created_by' => $user->id,
                'starting_price' => $request->starting_price,
                'buyout_price' => $request->buyout_price,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'status' => 'upcoming'
            ]);

            Log::info('Enchère créée:', [
                'auction_id' => $auction->id,
                'guild_id' => $guild->id,
                'created_by' => $user->id,
                'item_name' => $auction->item_name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Enchère créée avec succès',
                'auction' => [
                    'id' => $auction->id,
                    'item_name' => $auction->item_name,
                    'description' => $auction->description,
                    'starting_price' => $auction->starting_price,
                    'buyout_price' => $auction->buyout_price,
                    'start_time' => $auction->start_time,
                    'end_time' => $auction->end_time,
                    'status' => $auction->status,
                    'created_at' => $auction->created_at,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur création enchère:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'enchère'
            ], 500);
        }
    }

    /**
     * Voir les détails d'une enchère
     */
    public function show($auctionId)
    {
        $user = Auth::user();
        $guild = $user->getCurrentGuild();

        if (!$guild) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes dans aucune guilde.'
            ], 403);
        }

        $auction = Auction::where('id', $auctionId)
                         ->where('guild_id', $guild->id)
                         ->with(['creator', 'currentWinner', 'winner'])
                         ->first();

        if (!$auction) {
            return response()->json([
                'success' => false,
                'message' => 'Enchère introuvable.'
            ], 404);
        }

        // ⭐ METTRE À JOUR LE STATUT
        $auction->updateStatus();
        $auction->closeIfEnded();

        // ⭐ RÉCUPÉRER L'HISTORIQUE DES ENCHÈRES
        $bids = $auction->latestBids()
                       ->with('user')
                       ->take(20) // Dernières 20 enchères
                       ->get()
                       ->map(function($bid) {
                           return [
                               'id' => $bid->id,
                               'username' => $bid->user->username,
                               'bid_amount' => $bid->bid_amount,
                               'is_buyout' => $bid->is_buyout,
                               'created_at' => $bid->created_at,
                           ];
                       });

        $userBid = $auction->bids()->where('user_id', $user->id)
                          ->orderBy('bid_amount', 'desc')
                          ->first();

        return response()->json([
            'success' => true,
            'auction' => [
                'id' => $auction->id,
                'item_name' => $auction->item_name,
                'description' => $auction->description,
                'starting_price' => $auction->starting_price,
                'buyout_price' => $auction->buyout_price,
                'current_bid' => $auction->current_bid,
                'minimum_bid' => $auction->getMinimumBid(),
                'start_time' => $auction->start_time,
                'end_time' => $auction->end_time,
                'status' => $auction->status,
                'is_active' => $auction->isActive(),
                'can_bid' => $auction->canBid($auction->getMinimumBid()),
                'can_buyout' => $auction->canBuyout(),
                'time_remaining' => $auction->isActive() ? 
                    $auction->end_time->diffForHumans() : null,
                'creator' => $auction->creator->username,
                'is_owner' => $auction->created_by === $user->id,
                'current_winner' => $auction->currentWinner ? 
                    $auction->currentWinner->username : null,
                    'current_winner_id' => $auction->current_winner_id,
                'is_current_winner' => $auction->current_winner_id === $user->id,
                'winner' => $auction->winner ? $auction->winner->username : null,
                'final_price' => $auction->final_price,
                'user_highest_bid' => $userBid ? $userBid->bid_amount : null,
                'total_bids' => $auction->bids()->count(),
                'created_at' => $auction->created_at,
            ],
            'bids' => $bids,
            'user_dkp' => $user->getCurrentGuildDkp()
        ]);
    }

    /**
     * Enchérir sur un objet
     */
    public function bid(Request $request, $auctionId)
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
            'bid_amount' => 'required|integer|min:1'
        ]);

        $auction = Auction::where('id', $auctionId)
                         ->where('guild_id', $guild->id)
                         ->lockForUpdate() // ⭐ LOCK POUR ÉVITER LES RACE CONDITIONS
                         ->first();

        if (!$auction) {
            return response()->json([
                'success' => false,
                'message' => 'Enchère introuvable.'
            ], 404);
        }

        // ⭐ METTRE À JOUR LE STATUT
        $auction->updateStatus();

        if (!$auction->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Cette enchère n\'est pas active.'
            ], 400);
        }

        // ⭐ VÉRIFIER LE MONTANT MINIMUM
        if (!$auction->canBid($request->bid_amount)) {
            return response()->json([
                'success' => false,
                'message' => "Le montant minimum est de {$auction->getMinimumBid()} DKP."
            ], 400);
        }

        // ⭐ VÉRIFIER LES DKP DISPONIBLES
        $userDkp = $user->getCurrentGuildDkp();
        if ($userDkp < $request->bid_amount) {
            return response()->json([
                'success' => false,
                'message' => "Vous n'avez que {$userDkp} DKP disponibles."
            ], 400);
        }

        try {
            DB::beginTransaction();

            // ⭐ CRÉER L'ENCHÈRE
            $bid = AuctionBid::create([
                'auction_id' => $auction->id,
                'user_id' => $user->id,
                'bid_amount' => $request->bid_amount,
                'is_buyout' => false
            ]);

            // ⭐ METTRE À JOUR L'ENCHÈRE ACTUELLE
            $auction->update([
                'current_bid' => $request->bid_amount,
                'current_winner_id' => $user->id
            ]);

            DB::commit();

            Log::info('Enchère placée:', [
                'auction_id' => $auction->id,
                'user_id' => $user->id,
                'bid_amount' => $request->bid_amount,
                'user_dkp_before' => $userDkp
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Enchère placée avec succès !',
                'bid' => [
                    'amount' => $bid->bid_amount,
                    'is_current_winner' => true,
                    'user_dkp_remaining' => $userDkp - $request->bid_amount
                ],
                'auction' => [
                    'current_bid' => $auction->current_bid,
                    'minimum_bid' => $auction->getMinimumBid()
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur enchère:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enchère'
            ], 500);
        }
    }

    /**
     * Achat instantané
     */
    public function buyout(Request $request, $auctionId)
    {
        $user = Auth::user();
        $guild = $user->getCurrentGuild();

        if (!$guild) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes dans aucune guilde.'
            ], 403);
        }

        $auction = Auction::where('id', $auctionId)
                         ->where('guild_id', $guild->id)
                         ->lockForUpdate()
                         ->first();

        if (!$auction) {
            return response()->json([
                'success' => false,
                'message' => 'Enchère introuvable.'
            ], 404);
        }

        // ⭐ METTRE À JOUR LE STATUT
        $auction->updateStatus();

        if (!$auction->canBuyout()) {
            return response()->json([
                'success' => false,
                'message' => 'L\'achat instantané n\'est pas disponible pour cette enchère.'
            ], 400);
        }


        // ⭐ VÉRIFIER LES DKP DISPONIBLES
        $userDkp = $user->getCurrentGuildDkp();
        if ($userDkp < $auction->buyout_price) {
            return response()->json([
                'success' => false,
                'message' => "Vous n'avez que {$userDkp} DKP disponibles. L'achat instantané coûte {$auction->buyout_price} DKP."
            ], 400);
        }

        try {
            DB::beginTransaction();

            // ⭐ CRÉER L'ENCHÈRE DE BUYOUT
            $bid = AuctionBid::create([
                'auction_id' => $auction->id,
                'user_id' => $user->id,
                'bid_amount' => $auction->buyout_price,
                'is_buyout' => true
            ]);

            // ⭐ TERMINER L'ENCHÈRE
            $auction->update([
                'current_bid' => $auction->buyout_price,
                'current_winner_id' => $user->id,
                'winner_id' => $user->id,
                'final_price' => $auction->buyout_price,
                'status' => 'ended',
                'ended_at' => now()
            ]);

            // ⭐ DÉDUIRE LES DKP DU GAGNANT
            $guildDkp = $user->getOrCreateGuildDkp($guild);
            $guildDkp->removeDkp($auction->buyout_price);

            DB::commit();

            Log::info('Achat instantané:', [
                'auction_id' => $auction->id,
                'user_id' => $user->id,
                'buyout_price' => $auction->buyout_price,
                'user_dkp_before' => $userDkp,
                'user_dkp_after' => $userDkp - $auction->buyout_price
            ]);

            return response()->json([
                'success' => true,
                'message' => "Félicitations ! Vous avez acheté '{$auction->item_name}' pour {$auction->buyout_price} DKP !",
                'auction' => [
                    'item_name' => $auction->item_name,
                    'final_price' => $auction->buyout_price,
                    'user_dkp_remaining' => $userDkp - $auction->buyout_price
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur achat instantané:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'achat instantané'
            ], 500);
        }
    }

    /**
     * Supprimer une enchère (owner uniquement)
     */
    public function delete($auctionId)
    {
        $user = Auth::user();
        $guild = $user->ownedGuilds()->first();

        if (!$guild) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne possédez aucune guilde.'
            ], 403);
        }

        $auction = Auction::where('id', $auctionId)
                         ->where('guild_id', $guild->id)
                         ->where('created_by', $user->id)
                         ->first();

        if (!$auction) {
            return response()->json([
                'success' => false,
                'message' => 'Enchère introuvable ou vous n\'avez pas les permissions.'
            ], 404);
        }

        try {
            DB::beginTransaction();

            // ⭐ MARQUER COMME FINIE AU LIEU DE SUPPRIMER
            $auction->update([
                'status' => 'ended',
                'is_active' => false,
                'ended_at' => now()
            ]);

            // ⭐ LOG POUR AUDIT
            Log::info('Enchère annulée:', [
                'auction_id' => $auction->id,
                'item_name' => $auction->item_name,
                'cancelled_by' => $user->id,
                'had_bids' => $auction->bids()->count() > 0
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Enchère annulée avec succès.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur annulation enchère:', [
                'auction_id' => $auctionId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'annulation de l\'enchère'
            ], 500);
        }
    }
}
