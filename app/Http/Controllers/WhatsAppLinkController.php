<?php

namespace App\Http\Controllers;

use App\Models\WhatsAppLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WhatsAppLinkController extends Controller
{
    /**
     * ADMIN: Create a WhatsApp link
     * POST /api/admin/whatsapp-links
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'link' => 'required|url|max:1000',
            'role_id' => 'required|exists:volunteer_roles,id',
        ]);

        $link = WhatsAppLink::create([
            'title' => $request->title,
            'link' => $request->link,
            'role_id' => $request->role_id,
            'created_by' => Auth::id(),
        ]);

        return response()->json([
            'message' => 'WhatsApp link created successfully',
            'data' => $link->load(['creator:id,name,email', 'role:id,name']),
        ], 201);
    }

    /**
     * USER: View all WhatsApp links created by admins
     * GET /api/whatsapp-links
     */
    public function index()
    {
        $user = Auth::user();

        $query = WhatsAppLink::with(['creator:id,name,email', 'role:id,name']);

        // Admins can view all department links.
        if (!in_array($user->role, ['admin', 'super_admin'], true)) {
            $roleIds = $user->volunteerRoles()->pluck('volunteer_roles.id');

            $query->where(function ($q) use ($roleIds) {
                $q->whereNull('role_id')
                  ->orWhereIn('role_id', $roleIds);
            });
        }

        $links = $query->latest()->get();

        return response()->json($links);
    }
}
