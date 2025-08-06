<?php

namespace App\Http\Controllers;

use App\Models\UserImage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Facades\Image;

class UserImageController extends Controller
{
    /**
     * Lister les images de l'utilisateur connecté
     */
    public function index()
    {
        $user = Auth::user();
        
        $images = $user->images()
                      ->latest()
                      ->get()
                      ->map(function ($image) {
                          return [
                              'id' => $image->id,
                              'title' => $image->title,
                              'description' => $image->description,
                              'url' => $image->url,
                              'original_name' => $image->original_name,
                              'file_size' => $image->formatted_size,
                              'width' => $image->width,
                              'height' => $image->height,
                              'is_public' => $image->is_public,
                              'created_at' => $image->created_at,
                          ];
                      });

        return response()->json([
            'success' => true,
            'images' => $images
        ]);
    }

    /**
     * Lister les images publiques d'un utilisateur
     */
    public function userGallery($userId)
    {
        $user = User::findOrFail($userId);
        
        $images = $user->publicImages()
                      ->latest()
                      ->get()
                      ->map(function ($image) {
                          return [
                              'id' => $image->id,
                              'title' => $image->title,
                              'description' => $image->description,
                              'url' => $image->url,
                              'original_name' => $image->original_name,
                              'width' => $image->width,
                              'height' => $image->height,
                              'created_at' => $image->created_at,
                          ];
                      });

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'avatar' => $user->avatar,
            ],
            'images' => $images
        ]);
    }

    /**
     * Uploader une nouvelle image
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240', // Max 10MB
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_public' => 'boolean'
        ]);

        try {
            $file = $request->file('image');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = 'public/user-images/' . $user->id . '/' . $filename; // AJOUTER 'public/'

            // Stocker le fichier
            Storage::putFileAs('public/user-images/' . $user->id, $file, $filename);

            // Obtenir les dimensions de l'image
            $imageInfo = getimagesize($file->getPathname());
            $width = $imageInfo[0] ?? null;
            $height = $imageInfo[1] ?? null;

            // Créer l'enregistrement en base
            $userImage = UserImage::create([
                'user_id' => $user->id,
                'title' => $request->title,
                'description' => $request->description,
                'filename' => $filename,
                'original_name' => $file->getClientOriginalName(),
                'path' => $path, // ⭐ MAINTENANT AVEC 'public/'
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'width' => $width,
                'height' => $height,
                'is_public' => $request->boolean('is_public', true),
            ]);

            Log::info('Image uploadée:', [
                'user_id' => $user->id,
                'image_id' => $userImage->id,
                'filename' => $filename,
                'size' => $file->getSize()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Image uploadée avec succès',
                'image' => [
                    'id' => $userImage->id,
                    'title' => $userImage->title,
                    'description' => $userImage->description,
                    'url' => $userImage->url,
                    'original_name' => $userImage->original_name,
                    'file_size' => $userImage->formatted_size,
                    'width' => $userImage->width,
                    'height' => $userImage->height,
                    'is_public' => $userImage->is_public,
                    'created_at' => $userImage->created_at,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur upload image:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'upload de l\'image'
            ], 500);
        }
    }

    /**
     * Mettre à jour une image
     */
    public function update(Request $request, $imageId)
    {
        $user = Auth::user();
        $image = UserImage::where('id', $imageId)
                         ->where('user_id', $user->id)
                         ->firstOrFail();

        $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_public' => 'boolean'
        ]);

        $image->update([
            'title' => $request->title,
            'description' => $request->description,
            'is_public' => $request->boolean('is_public'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Image mise à jour avec succès',
            'image' => [
                'id' => $image->id,
                'title' => $image->title,
                'description' => $image->description,
                'url' => $image->url,
                'is_public' => $image->is_public,
            ]
        ]);
    }

    /**
     * Supprimer une image
     */
    public function destroy($imageId)
    {
        $user = Auth::user();
        $image = UserImage::where('id', $imageId)
                         ->where('user_id', $user->id)
                         ->firstOrFail();

        try {
            // Supprimer le fichier physique
            $image->deleteFile();
            
            // Supprimer l'enregistrement
            $image->delete();

            Log::info('Image supprimée:', [
                'user_id' => $user->id,
                'image_id' => $imageId,
                'filename' => $image->filename
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Image supprimée avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur suppression image:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'image'
            ], 500);
        }
    }
}
