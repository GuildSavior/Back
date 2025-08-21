<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class Auction extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_name',
        'description',
        'guild_id',
        'created_by',
        'starting_price',
        'buyout_price',
        'current_bid',
        'current_winner_id',
        'start_time',
        'end_time',
        'status',
        'is_active',
        'winner_id',
        'final_price',
        'ended_at'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'ended_at' => 'datetime',
        'is_active' => 'boolean',
        'is_buyout' => 'boolean',
    ];

    /**
     * Vérifier si l'enchère est à venir
     */
    public function isUpcoming(): bool
    {
        return now() < $this->start_time && $this->status === 'upcoming';
    }

    /**
     * Vérifier si l'enchère est active
     */
    public function isActive(): bool
    {
        $now = now();
        return $this->start_time <= $now && 
               $now <= $this->end_time && 
               $this->status === 'active' && 
               $this->is_active;
    }

    /**
     * Vérifier si l'enchère est terminée
     */
    public function isEnded(): bool
    {
        return now() > $this->end_time || $this->status === 'ended';
    }

    /**
     * Vérifier si l'enchère est annulée
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Mettre à jour le statut automatiquement
     */
    public function updateStatus(): void
    {
        if ($this->status === 'cancelled' || $this->status === 'ended') {
            return; // Ne pas changer si annulé ou terminé
        }

        $now = now();

        if ($now < $this->start_time) {
            $this->status = 'upcoming';
        } elseif ($now >= $this->start_time && $now <= $this->end_time) {
            $this->status = 'active';
        } else {
            $this->status = 'ended';
            if (!$this->ended_at) {
                $this->ended_at = $this->end_time;
            }
        }

        $this->save();
    }

    /**
     * Obtenir le prix minimum pour la prochaine enchère
     */
    public function getMinimumBid(): int
    {
        return max($this->current_bid + 1, $this->starting_price);
    }

    /**
     * Vérifier si un montant peut être enchéri
     */
    public function canBid(int $amount): bool
    {
        return $this->isActive() && $amount >= $this->getMinimumBid();
    }

    /**
     * Vérifier si l'achat instantané est possible
     */
    public function canBuyout(): bool
    {
        return $this->isActive() && $this->buyout_price > 0;
    }

    /**
     * Relations
     */
    public function guild()
    {
        return $this->belongsTo(Guild::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function currentWinner()
    {
        return $this->belongsTo(User::class, 'current_winner_id');
    }

    public function winner()
    {
        return $this->belongsTo(User::class, 'winner_id');
    }

    public function bids()
    {
        return $this->hasMany(AuctionBid::class)->orderBy('bid_amount', 'desc');
    }

    public function latestBids()
    {
        return $this->hasMany(AuctionBid::class)->orderBy('created_at', 'desc');
    }

    public function closeIfEnded()
{
    $now = now();
    // Exécuter si l'enchère est terminée et le DKP pas encore débité
    if (
        $now > $this->end_time &&
        $this->status === 'ended' &&
        $this->winner_id === null &&
        $this->current_winner_id &&
        $this->current_bid > 0
    ) {
        Log::info('Clôture automatique de l\'enchère', [
            'auction_id' => $this->id,
            'current_winner_id' => $this->current_winner_id,
            'current_bid' => $this->current_bid
        ]);
        $this->winner_id = $this->current_winner_id;
        $this->final_price = $this->current_bid;
        $this->is_active = false;
        $this->save();

        // Débiter les DKP du gagnant
        $winner = \App\Models\User::find($this->current_winner_id);
        $guild = $this->guild;
        if ($winner && $guild) {
            $guildDkp = $winner->getOrCreateGuildDkp($guild);
            $guildDkp->removeDkp($this->current_bid);
        }
    }
}
}
