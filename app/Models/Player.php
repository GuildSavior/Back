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
        'dkp',
        'guild_id',
        'user_id', // Ajouté ici
        'class',
        'events_joined',
    ];

    /**
     * Relation avec la guilde.
     */
    public function guild()
    {
        return $this->belongsTo(Guild::class);
    }

    /**
     * Relation avec l'utilisateur.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
