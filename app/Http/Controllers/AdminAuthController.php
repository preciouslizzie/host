<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Models\User;

class AdminAuthController extends Controller
{
    public function register(Request $request)
{
    if (!$request->user() || $request->user()->role !== 'super_admin') {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|min:8|confirmed'
    ]);

    $admin = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
        'role' => 'admin'
    ]);

    return response()->json([
        'message' => 'Admin created successfully',
        'admin' => $admin
    ], 201);
}

public function getUsers()
{
    return response()->json(User::all());
}

public function getVolunteers(Request $request)
{
    $request->validate([
        'search' => 'nullable|string|max:255',
        'role_id' => 'nullable|integer|exists:volunteer_roles,id',
        'sort_by' => 'nullable|in:name,email,created_at',
        'sort_dir' => 'nullable|in:asc,desc',
        'per_page' => 'nullable|integer|min:1|max:100',
    ]);

    $query = User::query()
        ->where('role', 'volunteer')
        ->with(['volunteerRoles:id,name']);

    if ($request->filled('search')) {
        $search = trim((string) $request->input('search'));
        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
    }

    if ($request->filled('role_id')) {
        $roleId = (int) $request->input('role_id');
        $query->whereHas('volunteerRoles', function ($q) use ($roleId) {
            $q->where('volunteer_roles.id', $roleId);
        });
    }

    $sortBy = $request->input('sort_by', 'created_at');
    $sortDir = $request->input('sort_dir', 'desc');
    $perPage = (int) $request->input('per_page', 20);

    $volunteers = $query
        ->orderBy($sortBy, $sortDir)
        ->paginate($perPage);

    return response()->json($volunteers);
}

public function createAdmin(Request $request)
{
    if ($request->user()->role !== 'super_admin') {
        return response()->json([
            'message' => 'Only Super Admin can create admins'
        ], 403);
    }

    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|min:6'
    ]);

    $admin = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
        'role' => 'admin'
    ]);

    return response()->json([
        'message' => 'Admin created successfully',
        'admin' => $admin
    ]);
}

public function createUser(Request $request)
{
    if(!in_array($request->user()->role, ['admin','super_admin'])){
        return response()->json(['message'=>'Unauthorized'],403);
    }

    $request->validate([
        'name'=>'required',
        'email'=>'required|email|unique:users,email',
        'password'=>'required|min:6'
    ]);

    $user = User::create([
        'name'=>$request->name,
        'email'=>$request->email,
        'password'=>Hash::make($request->password),
        'role'=>'user'
    ]);

    return response()->json([
        'message'=>'User created successfully',
        'user'=>$user
    ]);
}

    public function login(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'password' => 'required'
    ]);

    $admin = User::where('email', $request->email)
        ->whereIn('role', ['admin', 'super_admin'])
        ->first();

    if (!$admin || !Hash::check($request->password, $admin->password)) {
        return response()->json([
            'message' => 'Invalid credentials'
        ], 401);
    }

    $token = $admin->createToken('admin-token')->plainTextToken;

    return response()->json([
        'message' => 'Admin login successful',
        'token' => $token,
        'admin' => $admin
    ]);
}

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

}
