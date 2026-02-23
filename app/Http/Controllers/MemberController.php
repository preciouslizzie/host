<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class MemberController extends Controller
{
    // List all members
    public function index()
    {
        return response()->json(
            Member::select('id', 'full_name', 'role')->get());
    }

    // Update role & position
    public function update(Request $request, $id)
    {
        $member = User::find($id);

        if (!$member) {
            return response()->json(['message' => 'Member not found'], 404);
        }

        $validated = $request->validate([
            'role' => 'nullable|string|max:50',
            'position' => 'nullable|string|max:100',
            'status' => 'nullable|string|max:20',
        ]);

        $member->update($validated);

        return response()->json([
            'message' => 'Member updated successfully',
            'member' => $member
        ], 200);
    }

    // Delete member
    
    public function destroy($id)
    {
        $member = User::find($id);

        if (!$member) {
            return response()->json(['message' => 'Member not found'], 404);
        }

        $member->delete();

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
    // create member
    Member::create([
  'full_name' => 'New Volunteer',
  'role' => 'Volunteer',
]);
}
}
