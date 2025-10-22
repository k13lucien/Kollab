<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeamController extends Controller
{
    /**
     * Afficher les équipes de l'utilisateur connecté
     */
    public function index()
    {
        $user = Auth::user();

        $teams = $user->teams()->with('leader')->get();

        return response()->json($teams);
    }

    /**
     * Créer une nouvelle équipe
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'label' => 'nullable|string|max:255',
        ]);

        $user = Auth::user();

        $team = Team::create([
            'name' => $validated['name'],
            'label' => $validated['label'] ?? null,
            'leader_id' => $user->id,
        ]);

        // le créateur est automatiquement ajouté comme membre
        $team->members()->attach($user->id);

        return response()->json([
            'message' => 'Équipe créée avec succès.',
            'team' => $team->load('leader'),
        ], 201);
    }

    /**
     * Voir les détails d’une équipe
     */
    public function show($id)
    {
        $team = Team::with(['leader', 'members', 'projects'])->findOrFail($id);

        return response()->json($team);
    }

    /**
     * Mettre à jour une équipe (seulement par le leader)
     */
    public function update(Request $request, $id)
    {
        $team = Team::findOrFail($id);

        if ($team->leader_id !== Auth::id()) {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'label' => 'nullable|string|max:255',
        ]);

        $team->update($validated);

        return response()->json([
            'message' => 'Équipe mise à jour avec succès.',
            'team' => $team
        ]);
    }

    /**
     * Supprimer une équipe (seulement par le leader)
     */
    public function destroy($id)
    {
        $team = Team::findOrFail($id);

        if ($team->leader_id !== Auth::id()) {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }

        $team->delete();

        return response()->json(['message' => 'Équipe supprimée avec succès.']);
    }

    /**
     * Ajouter un membre à une équipe
     */
    public function addMember(Request $request, $teamId)
    {
        $team = Team::findOrFail($teamId);
    
        // Seul le leader peut ajouter des membres
        if ($team->leader_id !== Auth::id()) {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }
    
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);
    
        $user = User::where('email', $validated['email'])->first();
    
        if ($team->members()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'Cet utilisateur est déjà membre de l’équipe.'], 400);
        }
    
        $team->members()->attach($user->id);
    
        return response()->json(['message' => 'Membre ajouté avec succès.', 'user' => $user]);
    }
    

    /**
     * Retirer un membre d’une équipe
     */
    public function removeMember($teamId, $userId)
    {
        $team = Team::findOrFail($teamId);

        if ($team->leader_id !== Auth::id()) {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }

        if ($team->leader_id === $userId) {
            return response()->json(['message' => 'Le leader ne peut pas être retiré de l’équipe.'], 400);
        }

        $team->members()->detach($userId);

        return response()->json(['message' => 'Membre retiré avec succès.']);
    }
}
