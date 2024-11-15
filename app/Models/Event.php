<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    // Définir le nom de la table si nécessaire (Laravel devine automatiquement 'events')
    protected $table = 'event';

    // Attributs pouvant être assignés en masse
    protected $fillable = [
        'name',
        'description',
        'guild_id',
        'start_time',
        'end_time',
        'access_code',
    ];

    // Relation avec la table 'Guild'
    public function guild()
    {
        return $this->belongsTo(Guild::class);
    }

    // Gestion de l'attribut 'start_time' et 'end_time' (conversion automatique en instances de Carbon)
    protected $dates = ['start_time', 'end_time'];
}
