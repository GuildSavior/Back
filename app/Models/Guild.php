<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Guild extends Model
{
    use HasFactory;

    // Les attributs qui peuvent être remplis en masse
    protected $fillable = [
        'name',
        'description',
        'creation_date',
        'region',
        'owner_id',
        'member_count', // ⭐ AJOUTER ÇA
    ];

    // Les attributs qui doivent être castés
    protected $casts = [
        'creation_date' => 'date',
    ];

    /**
     * Propriétaire de la guilde
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Membres de la guilde (relation many-to-many)
     */
    public function members()
    {
        return $this->belongsToMany(User::class, 'guild_members')
                    ->withPivot('role', 'joined_at')
                    ->withTimestamps();
    }

    /**
     * Vérifier si la guilde est pleine
     */
    public function isFull(): bool
    {
        return $this->member_count >= 50; // ⭐ UTILISER member_count au lieu de count()
    }

    /**
     * Vérifier si un utilisateur peut rejoindre
     */
    public function canUserJoin(User $user): bool
    {
        return !$this->members()->where('user_id', $user->id)->exists();
    }

    /**
     * ⭐ SYNCHRONISER LE COMPTEUR AVEC LA RÉALITÉ
     */
    public function syncMemberCount(): void
    {
        $realCount = $this->members()->count();
        $this->update(['member_count' => $realCount]);
    }

    /**
     * ⭐ OBTENIR LE NOMBRE RÉEL DE MEMBRES
     */
    public function getRealMemberCount(): int
    {
        return $this->members()->count();
    }

    /**
     * ⭐ VÉRIFIER SI LE COMPTEUR EST CORRECT
     */
    public function isMemberCountAccurate(): bool
    {
        return $this->member_count === $this->getRealMemberCount();
    }
}
