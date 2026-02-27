<?php

namespace App\Http\Controllers;

use App\Models\VolunteerRole;
use App\Models\VolunteerApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
        $hasAvailabilityRequiredColumn = Schema::hasColumn('volunteer_roles', 'availability_required');
        $hasAvailabilityColumn = Schema::hasColumn('volunteer_roles', 'availability');
        $expectsAvailability = $hasAvailabilityRequiredColumn || $hasAvailabilityColumn;
        $availabilityColumn = $this->getAvailabilityColumnName($hasAvailabilityRequiredColumn, $hasAvailabilityColumn);
        $allowedAvailabilityValues = $this->getEnumValues('volunteer_roles', $availabilityColumn);

        $rules = [
            'name' => 'required|unique:volunteer_roles',
        ];
        if ($expectsAvailability) {
            $rules['availability_required'] = 'nullable|string';
            $rules['availability'] = 'nullable|string';
        }
        $request->validate($rules);

        $availabilityValue = $request->input('availability_required', $request->input('availability'));
        if ($expectsAvailability && ($availabilityValue === null || $availabilityValue === '')) {
            return response()->json([
                'message' => 'The availability field is required.'
            ], 422);
        }
        if ($expectsAvailability) {
            $availabilityValue = $this->normalizeAvailabilityValue($availabilityValue, $allowedAvailabilityValues);
            if ($availabilityValue === null) {
                return response()->json([
                    'message' => 'Invalid availability value.',
                    'allowed_values' => $allowedAvailabilityValues
                ], 422);
            }
        }

        $payload = $request->only('name');
        if ($hasAvailabilityRequiredColumn) {
            $payload['availability_required'] = $availabilityValue;
        }
        if ($hasAvailabilityColumn) {
            $payload['availability'] = $availabilityValue;
        }

        $role = VolunteerRole::create($payload);

        return response()->json($role, 201);
    }

    /**
     * ADMIN: Update volunteer role
     * PUT /api/admin/roles/{id}
     */
    public function update(Request $request, $id)
    {
        $role = VolunteerRole::findOrFail($id);

        $hasAvailabilityRequiredColumn = Schema::hasColumn('volunteer_roles', 'availability_required');
        $hasAvailabilityColumn = Schema::hasColumn('volunteer_roles', 'availability');
        $expectsAvailability = $hasAvailabilityRequiredColumn || $hasAvailabilityColumn;
        $availabilityColumn = $this->getAvailabilityColumnName($hasAvailabilityRequiredColumn, $hasAvailabilityColumn);
        $allowedAvailabilityValues = $this->getEnumValues('volunteer_roles', $availabilityColumn);

        $rules = [
            'name' => 'required|unique:volunteer_roles,name,'.$id,
        ];
        if ($expectsAvailability) {
            $rules['availability_required'] = 'nullable|string';
            $rules['availability'] = 'nullable|string';
        }
        $request->validate($rules);

        $availabilityValue = $request->input('availability_required', $request->input('availability'));
        if ($expectsAvailability && ($availabilityValue === null || $availabilityValue === '')) {
            return response()->json([
                'message' => 'The availability field is required.'
            ], 422);
        }
        if ($expectsAvailability) {
            $availabilityValue = $this->normalizeAvailabilityValue($availabilityValue, $allowedAvailabilityValues);
            if ($availabilityValue === null) {
                return response()->json([
                    'message' => 'Invalid availability value.',
                    'allowed_values' => $allowedAvailabilityValues
                ], 422);
            }
        }

        $payload = $request->only('name');
        if ($hasAvailabilityRequiredColumn) {
            $payload['availability_required'] = $availabilityValue;
        }
        if ($hasAvailabilityColumn) {
            $payload['availability'] = $availabilityValue;
        }

        $role->update($payload);

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

        $request->validate([
        'status' => 'required|in:approved,rejected'
    ]);

    $application->status = $request->status;
    $application->save();

    return response()->json([
        'message' => 'Application updated successfully',
        'application' => $application
    ]);

        $application->update(['status' => $request->status]);

        // If approved, add to volunteer_role_user pivot table
        if ($request->status === 'approved') {
            $application->volunteer->volunteerRoles()->attach(
                $application->role_id
            );
        }

        return response()->json([
            'message' => "Application {$request->status} successfully",
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

    private function getAvailabilityColumnName(bool $hasAvailabilityRequiredColumn, bool $hasAvailabilityColumn): ?string
    {
        if ($hasAvailabilityColumn) {
            return 'availability';
        }
        if ($hasAvailabilityRequiredColumn) {
            return 'availability_required';
        }

        return null;
    }

    private function getEnumValues(string $table, ?string $column): array
    {
        if (!$column) {
            return [];
        }

        $database = DB::getDatabaseName();
        $row = DB::table('information_schema.columns')
            ->select('COLUMN_TYPE')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->first();

        $columnType = $row->COLUMN_TYPE ?? '';
        if (!is_string($columnType) || strpos($columnType, 'enum(') !== 0) {
            return [];
        }

        preg_match_all("/'((?:[^'\\\\]|\\\\.)*)'/", $columnType, $matches);
        $values = array_map(static function ($v) {
            return stripslashes($v);
        }, $matches[1] ?? []);

        return array_values(array_filter($values, static function ($v) {
            return $v !== '';
        }));
    }

    private function normalizeAvailabilityValue(?string $value, array $allowedValues): ?string
    {
        $raw = is_string($value) ? trim($value) : '';
        if ($raw === '') {
            return null;
        }

        if (empty($allowedValues)) {
            return $raw;
        }

        $rawLower = strtolower($raw);
        foreach ($allowedValues as $allowed) {
            if (strtolower($allowed) === $rawLower) {
                return $allowed;
            }
        }

        $aliasMap = [
            'filled' => ['filled', 'full', 'occupied', 'closed', 'unavailable', 'not_available', 'not available'],
            'full' => ['filled', 'full', 'occupied', 'closed', 'unavailable', 'not_available', 'not available'],
            'open' => ['open', 'available', 'vacant', 'free'],
            'available' => ['available', 'open', 'vacant', 'free'],
            'vacant' => ['vacant', 'available', 'open', 'free'],
            'unavailable' => ['unavailable', 'not_available', 'not available', 'closed', 'filled', 'full', 'occupied'],
        ];

        $candidates = $aliasMap[$rawLower] ?? [$rawLower];
        foreach ($candidates as $candidate) {
            foreach ($allowedValues as $allowed) {
                if (strtolower($allowed) === strtolower($candidate)) {
                    return $allowed;
                }
            }
        }

        return null;
    }
}
