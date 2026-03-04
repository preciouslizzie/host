<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Member;
use App\Models\User;

class MemberController extends Controller
{
    // List all members
    public function index()
    {
        $members = Member::select('id', 'name', 'email', 'role', 'position')
            ->latest()
            ->get()
            ->map(function ($member) {
                $member->full_name = $member->name;
                return $member;
            });

        return response()->json($members);
    }

    // Create member (admin only)
    public function store(Request $request)
    {
        if (!in_array($request->user()->role, ['admin', 'super_admin'], true)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id|unique:members,user_id',
            'role' => 'nullable|string|max:50',
            'position' => 'nullable|string|max:100',
            'ministry' => 'nullable|string|max:100',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $position = $validated['position'] ?? $validated['ministry'] ?? null;

        $member = Member::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $validated['role'] ?? 'member',
            'position' => $position,
        ]);

        return response()->json([
            'message' => 'Member added successfully',
            'member' => $member,
        ], 201);
    }

    // Update role & position
    public function update(Request $request, $id)
    {
        if (!in_array($request->user()->role, ['admin', 'super_admin'], true)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $member = Member::find($id);

        if (!$member) {
            return response()->json(['message' => 'Member not found'], 404);
        }

        $validated = $request->validate([
            'role' => 'nullable|string|max:50',
            'position' => 'nullable|string|max:100',
            'ministry' => 'nullable|string|max:100',
        ]);

        if (array_key_exists('ministry', $validated) && !array_key_exists('position', $validated)) {
            $validated['position'] = $validated['ministry'];
        }
        unset($validated['ministry']);

        $member->update($validated);

        return response()->json([
            'message' => 'Member updated successfully',
            'member' => $member
        ], 200);
    }

    // Delete member
    
    public function destroy($id)
    {
        if (!in_array(auth()->user()->role, ['admin', 'super_admin'], true)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $member = Member::find($id);

        if (!$member) {
            return response()->json(['message' => 'Member not found'], 404);
        }

        DB::transaction(function () use ($member) {
            $user = User::find($member->user_id);
            $member->delete();
            if ($user) {
                $user->delete();
            }
        });

        return response()->json(['message' => 'Member deleted'], 200);
    }
    
    // volunteer
    
public function volunteers()
{
    $volunteers = User::where('role', 'volunteer')
        ->select('id', 'name', 'email')
        ->orderBy('name')
        ->get();

    return response()->json($volunteers);
}
}
