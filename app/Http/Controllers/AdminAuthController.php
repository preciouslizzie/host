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
