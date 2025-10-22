<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    /**
     *  Afficher les tâches de l'utilisateur connecté
     */
    public function index()
    {
        $user = Auth::user();
        $tasks = Task::where('assigned_to', $user->id)
                     ->orderBy('created_at', 'desc')
                     ->get();

        return response()->json($tasks);
    }

    /**
     *  Créer une nouvelle tâche (planifier une tâche)
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'label' => 'nullable|string',
            'status' => 'in:pending,in_progress,completed',
            'priority' => 'in:low,medium,high',
            'deadline' => 'required|date',
            'project_id' => 'nullable|exists:projects,id',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        // Si non spécifié, assigner à l'utilisateur connecté
        $data['assigned_to'] = $data['assigned_to'] ?? Auth::id();

        $task = Task::create($data);

        return response()->json([
            'message' => 'Tâche créée avec succès',
            'task' => $task
        ], 201);
    }

    /**
     *  Voir une tâche spécifique
     */
    public function show(Task $task)
    {
        $user = Auth::user();

        if ($task->assigned_to !== $user->id && $task->project->team->leader_id !== $user->id) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        return response()->json($task);
    }

    /**
     *  Mettre à jour une tâche
     */
    public function update(Request $request, Task $task)
    {
        $user = Auth::user();

        if ($task->assigned_to !== $user->id) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        $data = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'label' => 'sometimes|nullable|string',
            'status' => 'sometimes|in:pending,in_progress,completed',
            'priority' => 'sometimes|in:low,medium,high',
            'deadline' => 'sometimes|nullable|date',
            'assigned_to' => 'sometimes|nullable|exists:users,id',
        ]);

        $task->update($data);

        return response()->json([
            'message' => 'Tâche mise à jour avec succès',
            'task' => $task
        ]);
    }

    /**
     *  Mettre fin à une tâche
     */
    public function complete(Task $task)
    {
        $user = Auth::user();

        if ($task->assigned_to !== $user->id) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        $task->update([
            'status' => 'completed',
            'completed_at' => now()
        ]);

        return response()->json(['message' => 'Tâche marquée comme terminée']);
    }

    /**
     *  Suspendre une tâche
     */
    public function suspend(Task $task)
    {
        $user = Auth::user();

        if ($task->assigned_to !== $user->id) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        $task->update(['status' => 'pending']);

        return response()->json(['message' => 'Tâche suspendue']);
    }

    /**
     *  Supprimer une tâche
     */
    public function destroy(Task $task)
    {
        $user = Auth::user();

        if ($task->assigned_to !== $user->id) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        $task->delete();

        return response()->json(['message' => 'Tâche supprimée avec succès']);
    }
}
