<?php

namespace App\Http\Controllers;

use App\Models\Guild;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class GuildController extends Controller
{
    /**
     * Affiche la liste de toutes les guildes
     */
    public function index(): JsonResponse
    {
        try {
            $guilds = Guild::orderBy('name')->get();
            
            return response()->json([
                'success' => true,
                'data' => $guilds,
                'message' => 'Guildes récupérées avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des guildes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Affiche une guilde spécifique
     */
    public function show($id): JsonResponse
    {
        try {
            $guild = Guild::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $guild,
                'message' => 'Guilde récupérée avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Guilde non trouvée',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Crée une nouvelle guilde
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255|unique:guilds,name',
                'description' => 'nullable|string',
                'creation_date' => 'nullable|date',
                'nationality' => 'nullable|string'
            ]);

            $guild = Guild::create($validatedData);

            return response()->json([
                'success' => true,
                'data' => $guild,
                'message' => 'Guilde créée avec succès'
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la guilde',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Met à jour une guilde existante
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $guild = Guild::findOrFail($id);

            $validatedData = $request->validate([
                'name' => [
                    'sometimes',
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('guilds', 'name')->ignore($guild->id)
                ],
                'description' => 'sometimes|nullable|string',
                'creation_date' => 'sometimes|nullable|date',
                'nationality' => 'sometimes|nullable|string'
            ]);

            $guild->update($validatedData);

            return response()->json([
                'success' => true,
                'data' => $guild->fresh(),
                'message' => 'Guilde mise à jour avec succès'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la guilde',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprime une guilde
     */
    public function destroy($id): JsonResponse
    {
        try {
            $guild = Guild::findOrFail($id);
            $guildName = $guild->name;
            
            $guild->delete();

            return response()->json([
                'success' => true,
                'message' => "Guilde '{$guildName}' supprimée avec succès"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la guilde',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recherche des guildes par nom
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $searchTerm = $request->input('q');
            
            if (!$searchTerm) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terme de recherche requis'
                ], 400);
            }

            $guilds = Guild::where('name', 'LIKE', "%{$searchTerm}%")
                          ->orWhere('description', 'LIKE', "%{$searchTerm}%")
                          ->orderBy('name')
                          ->get();

            return response()->json([
                'success' => true,
                'data' => $guilds,
                'message' => 'Recherche effectuée avec succès',
                'search_term' => $searchTerm
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la recherche',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
