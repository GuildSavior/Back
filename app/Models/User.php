<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Jakyeru\Larascord\Traits\InteractsWithDiscord;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, InteractsWithDiscord;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'id',
        'username',
        'global_name',
        'discord_id', 
        'name', 
        'email', 
        'avatar',
        'verified',
        'banner',
        'banner_color',
        'accent_color',
        'locale',
        'mfa_enabled',
        'premium_type',
        'public_flags',
        'roles',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'username' => 'string',
        'global_name' => 'string',
        'discriminator' => 'string',
        'email' => 'string',
        'avatar' => 'string',
        'verified' => 'boolean',
        'banner' => 'string',
        'banner_color' => 'string',
        'accent_color' => 'string',
        'locale' => 'string',
        'mfa_enabled' => 'boolean',
        'premium_type' => 'integer',
        'public_flags' => 'integer',
        'roles' => 'json',
    ];

    public function subscription()
    {
        return $this->hasOne(Subscription::class);
    }

    public function isPremium()
    {
        return $this->subscription && $this->subscription->isPremium();
    }

    public function hasFeature($feature)
    {
        $features = [
            'freemium' => [
                'max_guilds' => 1,
                'basic_stats' => true,
                'max_players' => 10,
            ],
            'premium' => [
                'max_guilds' => 1,
                'basic_stats' => true,
                'advanced_stats' => true,
                'custom_reports' => true,
                'max_players' => 50,
            ],
        ];

        $planType = $this->subscription ? $this->subscription->plan_type : 'freemium';
        return $features[$planType][$feature] ?? false;
    }

    /**
     * Get the guild that the user belongs to.
     */
    public function guild(): BelongsTo
    {
        return $this->belongsTo(Guild::class);
    }

    /**
     * Get the role that the user belongs to.
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Guildes possédées (premium seulement)
     */
    public function ownedGuilds()
    {
        return $this->hasMany(Guild::class, 'owner_id');
    }

    /**
     * Guilde où l'utilisateur est membre (toutes les guildes)
     */
    public function guilds()
    {
        return $this->belongsToMany(Guild::class, 'guild_members')
                    ->withPivot('role', 'joined_at')
                    ->withTimestamps();
    }

    /**
     * Guilde où l'utilisateur est membre (une seule - la première)
     */
    public function memberGuild()
    {
        return $this->belongsToMany(Guild::class, 'guild_members')
                    ->withPivot('role', 'joined_at')
                    ->withTimestamps()
                    ->take(1); // ⭐ Utiliser take(1) au lieu de limit(1)
    }

    /**
     * Récupérer LA guilde de l'utilisateur (membre ou propriétaire)
     */
    public function getCurrentGuild()
    {
        // D'abord vérifier s'il possède une guilde (priorité au owner)
        $ownedGuild = $this->ownedGuilds()->first();
        if ($ownedGuild) {
            return $ownedGuild;
        }

        // Sinon vérifier s'il est membre d'une guilde
        return $this->memberGuild()->first();
    }

    /**
     * Vérifier si l'utilisateur est dans une guilde
     */
    public function isInAnyGuild(): bool
    {
        return $this->memberGuild()->exists() || $this->ownedGuilds()->exists();
    }

    /**
     * Vérifier si l'utilisateur peut créer une guilde
     */
    public function canCreateGuild(): bool
    {
        // Doit être premium ET ne pas déjà posséder une guilde
        return $this->isPremium() && !$this->ownedGuilds()->exists();
    }

    /**
     * Vérifier si l'utilisateur peut rejoindre une guilde
     */
    public function canJoinGuild(): bool
    {
        return !$this->isInAnyGuild();
    }

    /**
     * Quitter sa guilde actuelle
     */
    public function leaveCurrentGuild(): bool
    {
        $memberGuild = $this->memberGuild()->first();
        if ($memberGuild) {
            $this->memberGuild()->detach();
            return true;
        }
        return false;
    }

    /**
     * Obtenir le rôle de l'utilisateur dans sa guilde actuelle
     */
    public function getRoleInCurrentGuild(): ?string
    {
        // Si il possède une guilde, il est owner
        if ($this->ownedGuilds()->exists()) {
            return 'owner';
        }

        // Sinon récupérer son rôle en tant que membre
        $memberGuild = $this->memberGuild()->first();
        return $memberGuild ? $memberGuild->pivot->role : null;
    }
}
