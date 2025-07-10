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
        'nationality',
    ];

    // Les attributs qui doivent être castés
    protected $casts = [
        'creation_date' => 'date',
    ];
}
