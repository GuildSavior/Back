<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    // Définir le nom de la table si nécessaire (Laravel devine automatiquement 'items')
    protected $table = 'item';

    // Attributs pouvant être assignés en masse
    protected $fillable = [
        'item_name',
        'starting_price',
        'start_time',
        'end_time',
    ];

    // Gestion des dates (convertir start_time et end_time en instances Carbon)
    protected $dates = ['start_time', 'end_time'];
}
