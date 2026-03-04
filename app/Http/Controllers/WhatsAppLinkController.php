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
        ]);

        $link = WhatsAppLink::create([
            'title' => $request->title,
            'link' => $request->link,
            'created_by' => Auth::id(),
        ]);

        return response()->json([
            'message' => 'WhatsApp link created successfully',
            'data' => $link->load('creator:id,name,email'),
        ], 201);
    }

    /**
     * USER: View all WhatsApp links created by admins
     * GET /api/whatsapp-links
     */
    public function index()
    {
        $links = WhatsAppLink::with('creator:id,name,email')
            ->latest()
            ->get();

        return response()->json($links);
    }
}
