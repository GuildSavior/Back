<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuildMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'guild_id',
        'user_id',
        'role',
        'joined_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
    ];

    /**
     * Relation avec la guilde
     */
    public function guild()
    {
        return $this->belongsTo(Guild::class);
    }

    /**
     * Relation avec l'utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Vérifier si c'est un officier ou plus
     */
    public function isOfficerOrHigher(): bool
    {
        return in_array($this->role, ['officer', 'leader', 'owner']);
    }

    /**
     * Vérifier si c'est le leader
     */
    public function isLeader(): bool
    {
        return $this->role === 'leader';
    }
}
