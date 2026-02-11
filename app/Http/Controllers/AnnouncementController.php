<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnnouncementController extends Controller
{
    public function myAnnouncements()
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $announcements = Announcement::whereHas('users', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })
        ->with(['creator:id,name,email'])
        ->latest()
        ->get();

        return response()->json($announcements);
    }

    
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'volunteer_ids' => 'array|exists:users,id'
        ]);

        $userId = Auth::id();

        if (!$userId) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $announcement = Announcement::create([
            'title' => $request->title,
            'message' => $request->message,
            'created_by' => $userId
        ]);

        // Attach to specific volunteers or all volunteers
        if ($request->has('volunteer_ids') && count($request->volunteer_ids) > 0) {
            $announcement->users()->attach($request->volunteer_ids);
        } else {
            // If no specific volunteers selected, attach to all volunteers
            $volunteerIds = User::where('role', 'volunteer')->pluck('id');
            $announcement->users()->attach($volunteerIds);
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
            'volunteer_ids' => 'array|exists:users,id'
        ]);

        $announcement->update([
            'title' => $request->title,
            'message' => $request->message
        ]);

        // Update assigned volunteers if provided
        if ($request->has('volunteer_ids')) {
            $announcement->users()->sync($request->volunteer_ids);
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
}
