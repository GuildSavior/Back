<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuildMemberDkp extends Model
{
    use HasFactory;

    protected $table = 'guild_member_dkp';
    
    protected $fillable = [
        'user_id',
        'guild_id', 
        'dkp',
        'events_joined'
    ];

    protected $casts = [
        'dkp' => 'integer',
        'events_joined' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function guild()
    {
        return $this->belongsTo(Guild::class);
    }

    /**
     * Ajouter des DKP
     */
    public function addDkp(int $amount): void
    {
        $this->increment('dkp', $amount);
    }

    /**
     * Retirer des DKP
     */
    public function removeDkp(int $amount): void
    {
        $this->decrement('dkp', $amount);
    }

    /**
     * Incrémenter les événements rejoints
     */
    public function incrementEventsJoined(): void
    {
        $this->increment('events_joined');
    }
}
