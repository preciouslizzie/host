<?php

namespace App\Http\Controllers;

use App\Models\VolunteerRole;
use App\Models\VolunteerApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VolunteerRoleController extends Controller
{
    /**
     * VOLUNTEER: View all available roles
     * GET /api/volunteer/roles
     */
    public function index()
    {
        return response()->json(
            VolunteerRole::all()
        );
    }

    /**
     * ADMIN: Create a new volunteer role
     * POST /api/admin/roles
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:volunteer_roles',
        ]);

        $role = VolunteerRole::create($request->only('name'));

        return response()->json($role, 201);
    }

    /**
     * ADMIN: Update volunteer role
     * PUT /api/admin/roles/{id}
     */
    public function update(Request $request, $id)
    {
        $role = VolunteerRole::findOrFail($id);

        $request->validate([
            'name' => 'required|unique:volunteer_roles,name,'.$id,
        ]);

        $role->update($request->only('name'));

        return response()->json($role);
    }

    /**
     * ADMIN: Delete volunteer role
     * DELETE /api/admin/roles/{id}
     */
    public function destroy($id)
    {
        VolunteerRole::findOrFail($id)->delete();

        return response()->json(['message' => 'Role deleted successfully']);
    }

    /**
     * VOLUNTEER: Apply for a role
     * Creates a volunteer application record for admin approval
     * POST /api/volunteer/apply/{roleId}
     */
    public function apply($roleId)
    {
        VolunteerRole::findOrFail($roleId);

        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Check if already applied
        $existingApplication = VolunteerApplication::where([
            'user_id' => $userId,
            'role_id' => $roleId
        ])->first();

        if ($existingApplication) {
            return response()->json([
                'message' => 'You have already applied for this role'
            ], 409);
        }

        // Create application with pending status
        $application = VolunteerApplication::create([
            'user_id' => $userId,
            'role_id' => $roleId,
            'status' => 'pending'
        ]);

        return response()->json([
            'message' => 'Application submitted successfully',
            'data' => $application
        ], 201);
    }

    /**
     * VOLUNTEER: Get my applications
     * GET /api/volunteer/my-applications
     */
    public function myApplications()
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $applications = VolunteerApplication::where('user_id', $userId)
            ->with('role')
            ->latest()
            ->get();

        return response()->json($applications);
    }

    /**
     * ADMIN: Get all applications
     * GET /api/admin/applications
     */
    public function getAllApplications()
    {
        $applications = VolunteerApplication::with(['volunteer', 'role'])
            ->latest()
            ->get();

        return response()->json($applications);
    }

    /**
     * ADMIN: Approve or reject application
     * PUT /api/admin/applications/{id}/approve
     */
    public function approveApplication(Request $request, $id)
    {
        $application = VolunteerApplication::findOrFail($id);

        $status = $request->input(
            'status',
            $request->input('application_status', $request->input('decision'))
        );
        if (!in_array($status, ['approved', 'rejected'], true)) {
            return response()->json([
                'message' => 'The status field is required and must be approved or rejected.',
                'accepted_payload_keys' => ['status', 'application_status', 'decision']
            ], 422);
        }

        $application->update(['status' => $status]);

        // If approved, add to volunteer_role_user pivot table
        if ($status === 'approved') {
            $application->volunteer->volunteerRoles()->syncWithoutDetaching([
                $application->role_id
            ]);
        }

        return response()->json([
            'message' => "Application {$status} successfully",
            'data' => $application
        ]);
    }

    /**
     * ADMIN: Assign volunteers to a role
     * POST /api/admin/roles/{roleId}/assign-volunteers
     */
    public function assignVolunteers(Request $request, $roleId)
    {
        $role = VolunteerRole::findOrFail($roleId);

        $request->validate([
            'user_ids' => 'required|array|exists:users,id'
        ]);

        $role->users()->attach($request->user_ids);

        return response()->json([
            'message' => 'Volunteers assigned to role successfully',
            'data' => $role->load('users')
        ]);
    }

    /**
     * ADMIN: Remove a volunteer from a role
     * DELETE /api/admin/roles/{roleId}/volunteers/{userId}
     */
    public function removeVolunteer($roleId, $userId)
    {
        $role = VolunteerRole::findOrFail($roleId);

        $role->users()->detach($userId);

        return response()->json(['message' => 'Volunteer removed from role successfully']);
    }

}
