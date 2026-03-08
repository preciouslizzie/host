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
        $input = $this->normalizeAnnouncementPayload($request);

        $validated = validator($input, [
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'volunteer_ids' => 'array',
            'volunteer_ids.*' => 'exists:users,id',
            'user_ids' => 'array',
            'user_ids.*' => 'exists:users,id',
            'target_roles' => 'nullable',
            'role_id' => 'nullable|exists:volunteer_roles,id',
        ])->validate();

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

        $input = $this->normalizeAnnouncementPayload($request);

        $validated = validator($input, [
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'volunteer_ids' => 'array',
            'volunteer_ids.*' => 'exists:users,id',
            'user_ids' => 'array',
            'user_ids.*' => 'exists:users,id',
        ])->validate();

        $announcement->update([
            'title' => $validated['title'],
            'message' => $validated['message']
        ]);

        // Update assigned users if provided (supports legacy volunteer_ids).
        if (array_key_exists('user_ids', $validated) || array_key_exists('volunteer_ids', $validated)) {
            $announcement->users()->sync($validated['user_ids'] ?? $validated['volunteer_ids'] ?? []);
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
            'volunteer_ids' => 'required|array',
            'volunteer_ids.*' => 'exists:users,id',
        ]);

        $announcement->users()->sync($request->volunteer_ids);

        return response()->json([
            'message' => 'Volunteers assigned successfully',
            'data' => $announcement->load('users')
        ]);
    }

private function normalizeAnnouncementPayload(Request $request): array
{
    $payload = [
        'title' => $request->input('title', $request->input('subject')),
        'message' => $request->input('message', $request->input('content', $request->input('body', $request->input('description')))),
        'volunteer_ids' => $request->input('volunteer_ids', $request->input('volunteerIds')),
        'user_ids' => $request->input('user_ids', $request->input('userIds')),
        'target_roles' => $request->input('target_roles', $request->input('targetRoles')),
        'role_id' => $request->input('role_id', $request->input('roleId')),
    ];

    // Keep optional fields out of validation when not provided.
    return array_filter($payload, static fn ($value) => $value !== null);
}

public function unread()
{
    $user = auth()->user();

    if (!$user) {
        return response()->json(['message' => 'Unauthenticated'], 401);
    }

    $query = Announcement::query();

    if (Schema::hasColumn('announcements', 'role_id') && !in_array($user->role, ['admin', 'super_admin'], true)) {
        $roleIds = collect();
        if (method_exists($user, 'volunteerRoles')) {
            $roleIds = $user->volunteerRoles()->pluck('volunteer_roles.id');
        }

        $query->where(function ($q) use ($roleIds) {
            $q->whereNull('role_id');
            if ($roleIds->isNotEmpty()) {
                $q->orWhereIn('role_id', $roleIds);
            }
        });
    }

    if (Schema::hasTable('announcement_reads') && Schema::hasColumn('announcement_reads', 'volunteer_id')) {
        $query->whereDoesntHave('reads', function ($q) use ($user) {
            $q->where('volunteer_id', $user->id);
        });
    }

    $announcements = $query
        ->with(['creator:id,name,email'])
        ->latest()
        ->get();

    return response()->json($announcements);
}

public function markAsRead($id)
{
    $user = auth()->user();

    if (!$user) {
        return response()->json(['message' => 'Unauthenticated'], 401);
    }

    $announcement = Announcement::find($id);
    if (!$announcement) {
        return response()->json(['message' => 'Announcement not found'], 404);
    }

    if (!Schema::hasTable('announcement_reads') || !Schema::hasColumn('announcement_reads', 'volunteer_id')) {
        return response()->json(['message' => 'Read tracking is not configured'], 500);
    }

    AnnouncementRead::firstOrCreate(
        [
            'announcement_id' => $announcement->id,
            'volunteer_id' => $user->id,
        ],
        [
            'read_at' => now(),
        ]
    );

    return response()->json(['message' => 'Marked as read']);

}
}
