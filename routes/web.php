<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\DiscussionController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\VolunteerReportController;
use App\Http\Controllers\VolunteerRoleController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\EventController;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\AudioController;
use App\Http\Controllers\MemberController;

// Prefix all routes with "api"
Route::prefix('api')->group(function () {
    Route::get('/check-db', function () {
            return DB::connection()->getDatabaseName();
        });

    // ------------------- AUTH ROUTES -------------------
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->post('/admin/create-admin', [AdminAuthController::class, 'createAdmin']);
    Route::middleware('auth:sanctum')->get('/admin/users', [AdminAuthController::class, 'getUsers']);
    //Route::post('/admin/register', [AdminAuthController::class, 'register']);
    Route::middleware('auth:sanctum')->post('/admin/register', [AdminAuthController::class, 'register']);
    Route::post('/admin/login', [AdminAuthController::class, 'login']);
    Route::post('/admin/create-user', [AdminAuthController::class, 'createUser']);
    Route::post('/admin/logout', [AdminAuthController::class, 'logout']);

    // ------------------- PASSWORD RESET -------------------
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink']);
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);
    Route::post('/login-with-token', [PasswordResetController::class, 'loginWithToken']);

    // ------------------- PUBLIC ROUTES -------------------
    Route::get('/blogs', [BlogController::class, 'index']);
    Route::get('/blog/{id}', [BlogController::class, 'show']);
    Route::get('/event', [EventController::class, 'index']);
    Route::get('/payment/callback', [PaymentController::class, 'callback'])->name('payment.callback');

    // ------------------- PROTECTED ROUTES -------------------
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/dashboard', [AuthController::class, 'dashboard']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::post('/update-profile', [AuthController::class, 'updateProfile']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);


        // Blogs
        Route::post('/blogs', [BlogController::class, 'store']);
        Route::put('/blog/{id}', [BlogController::class, 'update']);
        Route::delete('/blog/{id}', [BlogController::class, 'destroy']);

        // Events

        Route::get('/event', [EventController::class, 'index']);
        Route::post('/event', [EventController::class, 'store']);
        Route::put('/event/{id}', [EventController::class, 'update']);
        Route::delete('/event/{id}', [EventController::class, 'destroy']);

        //Payment Gatway
    Route::post('/pay', [PaymentController::class, 'initialize']);

        //Audio connect
    Route::get('/audio/upload-url', [AudioController::class, 'getUploadUrl']);
    Route::post('/audio', [AudioController::class, 'store']);
    Route::get('/audio', [AudioController::class, 'index']);
        Route::get('/audio/{id}', [AudioController::class, 'show']);
    Route::put('/audio/{id}', [AudioController::class, 'update']);
    Route::delete('/audio/{id}', [AudioController::class, 'destroy']);

        // members

    Route::get('/members', [MemberController::class, 'index']);
    Route::post('/members', [MemberController::class, 'index']);
    Route::put('/members/{id}', [MemberController::class, 'update']);
    Route::delete('/members/{id}', [MemberController::class, 'destroy']);

        // Volunteer Features
        Route::get('/volunteer/roles',[VolunteerRoleController::class,'index']);
        Route::post('/volunteer/apply/{roleId}',[VolunteerRoleController::class,'apply']);
        Route::get('/volunteer/my-applications',[VolunteerRoleController::class,'myApplications']);
        Route::get('/my-schedule',[ScheduleController::class,'mySchedule']);
        Route::get('/announcements',[AnnouncementController::class,'myAnnouncements']);
        Route::get('/volunteer/groups',[DiscussionController::class,'groups']);
        Route::get('/volunteer/groups/{id}/posts',[DiscussionController::class,'posts']);
        Route::post('/volunteer/groups/{id}/posts',[DiscussionController::class,'store']);
        Route::post('/availability',[VolunteerReportController::class,'availability']);
        Route::get('/volunteer/availability',[VolunteerReportController::class,'getMyAvailability']);
        Route::get('/volunteer/hours-worked',[VolunteerReportController::class,'hours']);
        Route::get('/volunteer/hours-summary',[VolunteerReportController::class,'hoursSummary']);
        Route::get('/volunteer/announcements', [AnnouncementController::class, 'unread']);
        Route::post('/volunteer/announcements/{id}/read', [AnnouncementController::class, 'markAsRead']);

    });
    
    Route::middleware('auth:sanctum', 'admin')->group(function () {
        // Volunteer Role Management
        Route::post('/admin/roles', [VolunteerRoleController::class, 'store']);
        Route::get('/admin/roles', [VolunteerRoleController::class, 'index']);
        Route::put('/admin/roles/{id}', [VolunteerRoleController::class, 'update']);
        Route::delete('/admin/roles/{id}', [VolunteerRoleController::class, 'destroy']);

        // Volunteer Application Management
        Route::get('/admin/applications', [VolunteerRoleController::class, 'getAllApplications']);
        Route::put('/admin/applications/{id}/approve', [VolunteerRoleController::class, 'approveApplication']);

        // Schedule Management
        Route::get('/admin/schedules', [ScheduleController::class, 'index']);
        Route::post('/admin/schedules', [ScheduleController::class, 'store']);
        Route::put('/admin/schedules/{id}', [ScheduleController::class, 'update']);
        Route::delete('/admin/schedules/{id}', [ScheduleController::class, 'destroy']);
        Route::get('/admin/roles/{roleId}/schedules', [ScheduleController::class, 'getRoleSchedules']);
        Route::get('/admin/volunteers/{userId}/schedules', [ScheduleController::class, 'getVolunteerSchedules']);

        // Announcement Management
        Route::get('/admin/announcements', [AnnouncementController::class, 'index']);
        Route::post('/admin/announcements', [AnnouncementController::class, 'store']);
        Route::put('/admin/announcements/{id}', [AnnouncementController::class, 'update']);
        Route::delete('/admin/announcements/{id}', [AnnouncementController::class, 'destroy']);
        Route::post('/admin/announcements/{id}/assign-volunteers', [AnnouncementController::class, 'assignVolunteers']);

        // Discussion Group Management
        Route::get('/admin/groups', [DiscussionController::class, 'groups']);
        Route::post('/admin/groups', [DiscussionController::class, 'createGroup']);
        Route::delete('/admin/groups/{groupId}/posts/{postId}', [DiscussionController::class, 'deletePost']);
        Route::put('/admin/groups/{id}', [DiscussionController::class, 'updateGroup']);
        Route::delete('/admin/groups/{id}', [DiscussionController::class, 'deleteGroup']);
        Route::post('/admin/groups/{id}/add-members', [DiscussionController::class, 'addMembers']);
        Route::delete('/admin/groups/{id}/members', [DiscussionController::class, 'removeMembers']);

        // Volunteer Attendance & Reports
        Route::get('/admin/volunteers', [AdminAuthController::class, 'getVolunteers']);
        Route::post('/admin/attendance', [VolunteerReportController::class, 'logAttendance']);
        Route::get('/admin/attendance', [VolunteerReportController::class, 'allAttendance']);
        Route::get('/admin/volunteers/{userId}/attendance', [VolunteerReportController::class, 'volunteerAttendance']);
        Route::get('/admin/volunteers/availability-report', [VolunteerReportController::class, 'availabilityReport']);
        Route::get('/admin/hours-summary', [VolunteerReportController::class, 'hoursReport']);

        Route::post('/admin/logout', [AdminAuthController::class, 'logout']);
    });
});

