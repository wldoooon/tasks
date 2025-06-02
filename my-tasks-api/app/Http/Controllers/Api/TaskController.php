<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator; 

class TaskController extends Controller
{
    /**
     * Display a listing of the tasks for the authenticated user.
     * GET /api/tasks
     */
    public function index(Request $request)
    {
        $user = Auth::user(); 

        $query = $user->tasks()->orderBy('created_at', 'desc'); 

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->has('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        // Sorting (optional examples)
        if ($request->has('sort_by')) {
            $sortBy = $request->input('sort_by');
            $sortDirection = $request->input('sort_direction', 'asc'); 
            if (in_array($sortBy, ['title', 'due_date', 'priority', 'status'])) {
                $query->orderBy($sortBy, $sortDirection);
            }
        }

        $tasks = $query->paginate(10); 

        return response()->json([
            'success' => true,
            'message' => 'Tasks retrieved successfully.',
            'data' => $tasks
        ]);
    }

    /**
     * Store a newly created task in storage.
     * POST /api/tasks
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:pending,in_progress,completed', 
            'priority' => 'sometimes|in:low,medium,high',
            'due_date' => 'nullable|date_format:Y-m-d', 
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422); 
        }

        $validatedData = $validator->validated();

        $task = $user->tasks()->create([
            'title' => $validatedData['title'],
            'description' => $validatedData['description'] ?? null,
            'status' => $validatedData['status'] ?? 'pending', 
            'priority' => $validatedData['priority'] ?? 'medium', 
            'due_date' => $validatedData['due_date'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Task created successfully.',
            'data' => $task
        ], 201); 
    }

    /**
     * Display the specified task.
     * GET /api/tasks/{task}
     * {task} is the ID of the task. Laravel's route-model binding automatically fetches it.
     */
    public function show(Task $task) 
    {
        if (Auth::id() !== $task->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You do not own this task.'
            ], 403); 
        }

        return response()->json([
            'success' => true,
            'message' => 'Task retrieved successfully.',
            'data' => $task
        ]);
    }

    /**
     * Update the specified task in storage.
     * PUT/PATCH /api/tasks/{task}
     */
    public function update(Request $request, Task $task)
    {
        if (Auth::id() !== $task->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You do not own this task.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255', 
            'description' => 'nullable|string',
            'status' => 'sometimes|in:pending,in_progress,completed',
            'priority' => 'sometimes|in:low,medium,high',
            'due_date' => 'nullable|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $validatedData = $validator->validated();
        $task->update($validatedData); 

        return response()->json([
            'success' => true,
            'message' => 'Task updated successfully.',
            'data' => $task->fresh(), 
        ]);
    }

    /**
     * Remove the specified task from storage.
     * DELETE /api/tasks/{task}
     */
    public function destroy(Task $task)
    {
        if (Auth::id() !== $task->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You do not own this task.'
            ], 403);
        }

        $task->delete();

        return response()->json([
            'success' => true,
            'message' => 'Task deleted successfully.'
        ], 200); 
    }
}
