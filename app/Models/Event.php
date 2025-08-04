<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Event extends Model
{
    use HasFactory;

    // Définir le nom de la table si nécessaire (Laravel devine automatiquement 'events')
    protected $table = 'events';

    // Attributs pouvant être assignés en masse
    protected $fillable = [
        'name',
        'description',
        'guild_id',
        'created_by',
        'start_time',
        'end_time',
        'dkp_reward',
        'access_code',
        'is_active'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Générer un code d'accès unique
     */
    public static function generateAccessCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (self::where('access_code', $code)->exists());

        return $code;
    }

    /**
     * Vérifier si l'événement est en cours
     */
    public function isOngoing(): bool
    {
        $now = now();
        return $this->start_time <= $now && $now <= $this->end_time;
    }

    /**
     * Vérifier si l'événement est terminé
     */
    public function isFinished(): bool
    {
        return now() > $this->end_time;
    }

    /**
     * Vérifier si l'événement est à venir
     */
    public function isUpcoming(): bool
    {
        return now() < $this->start_time;
    }

    /**
     * Relation avec la guilde
     */
    public function guild()
    {
        return $this->belongsTo(Guild::class);
    }

    /**
     * Relation avec le créateur
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relation avec les participants
     */
    public function participants()
    {
        return $this->belongsToMany(User::class, 'event_participants')
                    ->withPivot('status', 'confirmed_at', 'attended_at', 'dkp_earned')
                    ->withTimestamps();
    }

    /**
     * Participants qui ont confirmé leur venue
     */
    public function confirmedParticipants()
    {
        return $this->participants()->wherePivot('status', '!=', 'interested');
    }

    /**
     * Participants qui ont validé leur présence
     */
    public function attendedParticipants()
    {
        return $this->participants()->wherePivot('status', 'attended');
    }
}
