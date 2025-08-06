<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class UserImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'filename',
        'original_name',
        'path',
        'mime_type',
        'file_size',
        'width',
        'height',
        'is_public'
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'file_size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
    ];

    /**
     * Relation avec l'utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtenir l'URL complète de l'image
     */
    public function getUrlAttribute(): string
    {
        // ⭐ RETOURNER L'URL COMPLÈTE AVEC LE DOMAINE LARAVEL
        return url(Storage::url($this->path));
        //     ↑ Ajouter url() pour avoir l'URL complète
    }

    /**
     * Obtenir la taille formatée
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
    }

    /**
     * Supprimer le fichier physique
     */
    public function deleteFile(): bool
    {
        return Storage::delete($this->path);
    }
}
