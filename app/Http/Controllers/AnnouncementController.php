<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class AnnouncementController extends Controller
{
    public function myAnnouncements()
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $announcements = Announcement::where(function ($query) use ($userId) {
            $query->whereHas('users', function ($q) use ($userId) {
                $q->where('users.id', $userId);
            })->orWhereDoesntHave('users');
        })
        ->with(['creator:id,name,email'])
        ->latest()
        ->get();

        return response()->json($announcements);
    }

    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'volunteer_ids' => 'array|exists:users,id',
            'user_ids' => 'array|exists:users,id',
            'target_roles' => 'nullable',
            'role_id' => 'nullable|exists:volunteer_roles,id',
            'priority' => 'nullable|in:low,normal,high',
        ]);

        $userId = Auth::id();

        if (!$userId) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $payload = [
            'title' => $validated['title'],
            'message' => $validated['message'],
        ];
        if (Schema::hasColumn('announcements', 'created_by')) {
            $payload['created_by'] = $userId;
        }
        if (Schema::hasColumn('announcements', 'role_id') && array_key_exists('role_id', $validated)) {
            $payload['role_id'] = $validated['role_id'];
        }
        if (Schema::hasColumn('announcements', 'priority') && array_key_exists('priority', $validated)) {
            $payload['priority'] = $validated['priority'] ?? 'normal';
        }
        if (Schema::hasColumn('announcements', 'target_roles')) {
            $targetRoles = $validated['target_roles'] ?? null;

            if (is_array($targetRoles)) {
                $payload['target_roles'] = json_encode($targetRoles);
            } elseif ($targetRoles === null || $targetRoles === '') {
                $payload['target_roles'] = json_encode(['all']);
            } else {
                $payload['target_roles'] = json_encode([(string) $targetRoles]);
            }
        }

        $announcement = Announcement::create($payload);

        // Attach to specific users (supports legacy volunteer_ids) or all non-admin users.
        $recipientIds = $validated['user_ids'] ?? $validated['volunteer_ids'] ?? [];
        if (count($recipientIds) > 0) {
            $announcement->users()->attach($recipientIds);
        } else {
            $defaultRecipientIds = User::whereNotIn('role', ['admin', 'super_admin'])->pluck('id');
            if ($defaultRecipientIds->isNotEmpty()) {
                $announcement->users()->attach($defaultRecipientIds);
            }
        }

        return response()->json([
            'message' => 'Announcement created successfully',
            'data' => $announcement->load('users')
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $announcement = Announcement::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'volunteer_ids' => 'array|exists:users,id',
            'user_ids' => 'array|exists:users,id',
        ]);

        $announcement->update([
            'title' => $request->title,
            'message' => $request->message
        ]);

        // Update assigned users if provided (supports legacy volunteer_ids).
        if ($request->has('user_ids') || $request->has('volunteer_ids')) {
            $announcement->users()->sync($request->input('user_ids', $request->input('volunteer_ids', [])));
        }

        return response()->json([
            'message' => 'Announcement updated successfully',
            'data' => $announcement->load('users')
        ]);
    }

    public function destroy($id)
    {
        $announcement = Announcement::findOrFail($id);
        $announcement->users()->detach();
        $announcement->delete();

        return response()->json(['message' => 'Announcement deleted successfully']);
    }

    public function index()
    {
        $announcements = Announcement::with(['creator:id,name,email', 'users'])
            ->latest()
            ->paginate(15);

        return response()->json($announcements);
    }

    
    public function assignVolunteers(Request $request, $id)
    {
        $announcement = Announcement::findOrFail($id);

        $request->validate([
            'volunteer_ids' => 'required|array|exists:users,id'
        ]);

        $announcement->users()->sync($request->volunteer_ids);

        return response()->json([
            'message' => 'Volunteers assigned successfully',
            'data' => $announcement->load('users')
        ]);
    }

    public function unread()
{
    $user = auth()->user();

    $announcements = Announcement::where(function ($query) use ($user) {
        $query->whereNull('role_id')
              ->orWhere('role_id', $user->role_id);
    })
    ->whereDoesntHave('reads', function ($q) use ($user) {
        $q->where('volunteer_id', $user->id);
    })
    ->latest()
    ->get();

    return response()->json($announcements);
}

public function markAsRead($id)
{
    $user = auth()->user();

    AnnouncementRead::firstOrCreate(
        [
            'announcement_id' => $id,
            'volunteer_id' => $user->id,
        ],
        [
            'read_at' => now(),
        ]
    );

    return response()->json(['message' => 'Marked as read']);

}
}
