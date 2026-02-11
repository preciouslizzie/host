<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\VolunteerRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScheduleController extends Controller
{
    /**
     * VOLUNTEER: Get my schedule
     * GET /api/my-schedule
     */
    public function mySchedule()
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $schedules = Schedule::where('user_id', $userId)
            ->with(['role:id,name', 'attendanceLogs'])
            ->orderBy('date')
            ->get();

        return response()->json($schedules);
    }

    
    public function index()
    {
        $schedules = Schedule::with(['volunteer:id,name,email', 'role:id,name'])
            ->orderBy('date')
            ->paginate(20);

        return response()->json($schedules);
    }

    /**
     * ADMIN: Create schedule
     * POST /api/admin/schedules
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_id' => 'required|exists:volunteer_roles,id',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'location' => 'required|string|max:255'
        ]);

        $schedule = Schedule::create($request->validated());

        return response()->json([
            'message' => 'Schedule created successfully',
            'data' => $schedule->load(['volunteer:id,name,email', 'role:id,name'])
        ], 201);
    }

    /**
     * ADMIN: Update schedule
     * PUT /api/admin/schedules/{id}
     */
    public function update(Request $request, $id)
    {
        $schedule = Schedule::findOrFail($id);

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_id' => 'required|exists:volunteer_roles,id',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'location' => 'required|string|max:255'
        ]);

        $schedule->update($request->validated());

        return response()->json([
            'message' => 'Schedule updated successfully',
            'data' => $schedule->load(['volunteer:id,name,email', 'role:id,name'])
        ]);
    }

    /**
     * ADMIN: Delete schedule
     * DELETE /api/admin/schedules/{id}
     */
    public function destroy($id)
    {
        $schedule = Schedule::findOrFail($id);
        $schedule->attendanceLogs()->delete();
        $schedule->delete();

        return response()->json(['message' => 'Schedule deleted successfully']);
    }

    /**
     * ADMIN: Get schedules for a specific volunteer
     * GET /api/admin/volunteers/{userId}/schedules
     */
    public function getVolunteerSchedules($userId)
    {
        $schedules = Schedule::where('user_id', $userId)
            ->with(['role:id,name', 'attendanceLogs'])
            ->orderBy('date')
            ->get();

        return response()->json($schedules);
    }

    /**
     * ADMIN: Get schedules for a specific role
     * GET /api/admin/roles/{roleId}/schedules
     */
    public function getRoleSchedules($roleId)
    {
        VolunteerRole::findOrFail($roleId);

        $schedules = Schedule::where('role_id', $roleId)
            ->with(['volunteer:id,name,email'])
            ->orderBy('date')
            ->get();

        return response()->json($schedules);
    }
}
