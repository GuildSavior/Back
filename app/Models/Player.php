<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'level',
        'class',
        'user_id',
        // ⭐ NE PAS INCLURE 'dkp' et 'events_joined' dans fillable
    ];

    protected $casts = [
        'level' => 'integer',
        'dkp' => 'integer',
        'events_joined' => 'integer',
    ];

    /**
     * Relation avec l'utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Classes disponibles
     */
    public static function getAvailableClasses(): array
    {
        return [
            'Warrior', 'Paladin', 'Hunter', 'Rogue', 'Priest',
            'Shaman', 'Mage', 'Warlock', 'Druid', 'Death Knight'
        ];
    }

    /**
     * Ajouter des DKP (méthode admin uniquement)
     */
    public function addDkp(int $amount): void
    {
        $this->increment('dkp', $amount);
    }

    /**
     * Retirer des DKP (méthode admin uniquement)
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
