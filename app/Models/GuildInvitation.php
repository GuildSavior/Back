<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class GuildInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'guild_id',
        'created_by',
        'code',
        'max_uses',
        'uses_count',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function guild()
    {
        return $this->belongsTo(Guild::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Générer un code d'invitation unique
     */
    public static function generateCode(): string
    {
        do {
            $code = Str::random(8);
        } while (self::where('code', $code)->exists());

        return $code;
    }

    /**
     * Vérifier si l'invitation est valide
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // Vérifier l'expiration
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        // Vérifier le nombre d'utilisations
        if ($this->max_uses && $this->uses_count >= $this->max_uses) {
            return false;
        }

        return true;
    }

    /**
     * Incrémenter le compteur d'utilisations
     */
    public function incrementUses(): void
    {
        $this->increment('uses_count');
    }
}