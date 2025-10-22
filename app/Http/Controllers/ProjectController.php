<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    /**
     * Lister les projets de toutes les équipes de l'utilisateur
     */
    public function index()
    {
        $user = Auth::user();

        // Récupère les projets des équipes dont l'utilisateur est membre
        $projects = Project::whereIn('team_id', $user->teams()->pluck('teams.id'))
            ->with(['team', 'tasks'])
            ->get();

        return response()->json($projects);
    }

    /**
     * Créer un projet
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'label' => 'nullable|string|max:255',
            'deadline' => 'required|date',
            'team_id' => 'required|exists:teams,id',
        ]);

        $team = Team::findOrFail($validated['team_id']);

        // Vérifie que l'utilisateur est membre de l'équipe
        if (!$team->members->contains(Auth::id())) {
            return response()->json(['message' => 'Vous n’êtes pas membre de cette équipe.'], 403);
        }

        $project = Project::create($validated);

        return response()->json([
            'message' => 'Projet créé avec succès.',
            'project' => $project->load('team')
        ], 201);
    }

    /**
     * Voir un projet avec ses tâches et les membres de l'équipe
     */
    public function show($id)
    {
        $project = Project::with(['tasks', 'team.members'])->findOrFail($id);

        $user = Auth::user();
        if (!$project->team->members->contains($user->id)) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        return response()->json($project);
    }

    /**
     * Mettre à jour un projet
     */
    public function update(Request $request, $id)
    {
        $project = Project::findOrFail($id);

        $user = Auth::user();
        if (!$project->team->members->contains($user->id)) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'label' => 'nullable|string|max:255',
            'deadline' => 'nullable|date',
        ]);

        $project->update($validated);

        return response()->json([
            'message' => 'Projet mis à jour avec succès.',
            'project' => $project
        ]);
    }

    /**
     * Supprimer un projet
     */
    public function destroy($id)
    {
        $project = Project::findOrFail($id);

        $user = Auth::user();
        if (!$project->team->members->contains($user->id)) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        $project->delete();

        return response()->json(['message' => 'Projet supprimé avec succès.']);
    }
}
