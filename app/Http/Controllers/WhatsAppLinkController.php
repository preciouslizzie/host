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
        $payload = $this->normalizeStorePayload($request);

        $validated = validator($payload, [
            'title' => 'required|string|max:255',
            'link' => 'required|url|max:1000',
            'role_id' => 'nullable|exists:volunteer_roles,id',
        ])->validate();

        $link = WhatsAppLink::create([
            'title' => $validated['title'],
            'link' => $validated['link'],
            'role_id' => $validated['role_id'] ?? null,
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

    private function normalizeStorePayload(Request $request): array
    {
        $rawLink = $request->input('link', $request->input('url', $request->input('whatsapp_link')));

        return [
            'title' => $request->input('title'),
            'link' => $this->normalizeLink($rawLink),
            'role_id' => $request->input('role_id', $request->input('roleId')),
        ];
    }

    private function normalizeLink($value): ?string
    {
        if (!is_string($value)) {
            return $value;
        }

        $link = trim($value);

        if ($link === '') {
            return $link;
        }

        if (!preg_match('#^https?://#i', $link) && preg_match('#^(wa\.me|chat\.whatsapp\.com|api\.whatsapp\.com)/#i', $link)) {
            return 'https://' . $link;
        }

        return $link;
    }
}
