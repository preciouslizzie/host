<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\VolunteerAvailability;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VolunteerReportController extends Controller
{
    /**
     * VOLUNTEER: Submit availability
     * POST /api/availability
     */
    public function availability(Request $request)
    {
        $request->validate([
            'day' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time'
        ]);

        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $availability = VolunteerAvailability::create([
            'user_id' => $userId,
            'day' => $request->day,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time
        ]);

        return response()->json([
            'message' => 'Availability saved successfully',
            'data' => $availability
        ], 201);
    }

    /**
     * VOLUNTEER: Get my availability
     * GET /api/volunteer/availability
     */
    public function getMyAvailability()
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $availability = VolunteerAvailability::where('user_id', $userId)
            ->orderByRaw("FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')")
            ->get();

        return response()->json($availability);
    }

    /**
     * VOLUNTEER: Get total hours worked
     * GET /api/volunteer/hours-worked
     */
    public function hours()
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $totalHours = AttendanceLog::where('user_id', $userId)
            ->sum('hours_worked');

        $attendance = AttendanceLog::where('user_id', $userId)
            ->with(['schedule:id,date,start_time,end_time,location'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'total_hours' => $totalHours,
            'attendance_records' => $attendance
        ]);
    }

    /**
     * VOLUNTEER: Get hours summary by month
     * GET /api/volunteer/hours-summary
     */
    public function hoursSummary()
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $summary = AttendanceLog::where('user_id', $userId)
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(hours_worked) as total_hours')
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->get();

        return response()->json($summary);
    }

    /**
     * ADMIN: Log attendance for a volunteer
     * POST /api/admin/attendance
     */
    public function logAttendance(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'schedule_id' => 'required|exists:schedules,id',
            'hours_worked' => 'required|numeric|min:0|max:24'
        ]);

        $attendance = AttendanceLog::create($request->validated());

        return response()->json([
            'message' => 'Attendance logged successfully',
            'data' => $attendance->load(['schedule:id,date,location', 'user:id,name'])
        ], 201);
    }

    /**
     * ADMIN: Get all volunteers attendance
     * GET /api/admin/attendance
     */
    public function allAttendance(Request $request)
    {
        $query = AttendanceLog::with(['user:id,name,email', 'schedule:id,date,location'])
            ->orderBy('created_at', 'desc');

        if ($request->boolean('paginate')) {
            $perPage = (int) $request->input('per_page', 20);
            return response()->json($query->paginate($perPage));
        }

        return response()->json($query->get());
    }

    /**
     * ADMIN: Get attendance report for a specific volunteer
     * GET /api/admin/volunteers/{userId}/attendance
     */
    public function volunteerAttendance($userId)
    {
        $totalHours = AttendanceLog::where('user_id', $userId)->sum('hours_worked');

        $attendance = AttendanceLog::where('user_id', $userId)
            ->with(['schedule:id,date,start_time,end_time,location'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'total_hours' => $totalHours,
            'attendance_records' => $attendance
        ]);
    }

    /**
     * ADMIN: Get availability report for all volunteers
     * GET /api/admin/volunteers/availability-report
     */
    public function availabilityReport()
    {
        $report = VolunteerAvailability::with(['user:id,name,email'])
            ->orderBy('day')
            ->get()
            ->groupBy('user_id');

        return response()->json($report);
    }

    /**
     * ADMIN: Get hours worked summary for all volunteers
     * GET /api/admin/hours-summary
     */
    public function hoursReport()
    {
        $report = AttendanceLog::selectRaw('user_id, SUM(hours_worked) as total_hours')
            ->with(['user:id,name,email'])
            ->groupBy('user_id')
            ->orderBy('total_hours', 'desc')
            ->get();

        return response()->json($report);
    }
}
