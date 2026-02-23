<?php

namespace App\Http\Controllers;

use App\Models\DiscussionGroup;
use App\Models\DiscussionPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DiscussionController extends Controller
{
    /**
     * VOLUNTEER: Get all discussion groups
     * GET /api/volunteer/groups
     */
    public function groups()
    {
        $groups = DiscussionGroup::with(['posts' => function ($q) {
            $q->latest()->limit(5);
        }, 'members:id,name'])
        ->latest()
        ->get();

        return response()->json($groups);
    }

    /**
     * VOLUNTEER: Get posts in a group
     * GET /api/volunteer/groups/{id}/posts
     */
    public function posts($id)
    {
        $group = DiscussionGroup::findOrFail($id);

        $posts = DiscussionPost::where('group_id', $id)
            ->with(['user:id,name,email'])
            ->latest()
            ->paginate(20);

        return response()->json([
            'group' => $group,
            'posts' => $posts
        ]);
    }

    /**
     * VOLUNTEER: Create a new post in a group
     * POST /api/volunteer/groups/{id}/posts
     */
    public function store(Request $request, $id)
    {
        DiscussionGroup::findOrFail($id);

        $request->validate([
            'message' => 'required|string|max:5000'
        ]);

        $post = DiscussionPost::create([
            'group_id' => $id,
            'user_id' => Auth::id(),
            'message' => $request->message
        ]);

        return response()->json([
            'message' => 'Post created successfully',
            'data' => $post->load('user:id,name,email')
        ], 201);
    }

    /**
     * ADMIN: Create a new discussion group
     * POST /api/admin/groups
     */
    public function createGroup(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:discussion_groups',
            'member_ids' => 'array|exists:users,id'
        ]);

        $group = DiscussionGroup::create([
            'name' => $request->name
        ]);

        // Attach members if provided
        if ($request->has('member_ids')) {
            $group->members()->attach($request->member_ids);
        }

        return response()->json([
            'message' => 'Discussion group created successfully',
            'data' => $group->load('members')
        ], 201);
    }

    /**
     * ADMIN: Update discussion group
     * PUT /api/admin/groups/{id}
     */
    public function updateGroup(Request $request, $id)
    {
        $group = DiscussionGroup::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:discussion_groups,name,'.$id,
            'member_ids' => 'array|exists:users,id'
        ]);

        $group->update(['name' => $request->name]);

        if ($request->has('member_ids')) {
            $group->members()->sync($request->member_ids);
        }

        return response()->json([
            'message' => 'Discussion group updated successfully',
            'data' => $group->load('members')
        ]);
    }

    /**
     * ADMIN: Delete discussion group
     * DELETE /api/admin/groups/{id}
     */
    public function deleteGroup($id)
    {
        $group = DiscussionGroup::findOrFail($id);

        // Delete all posts in the group
        DiscussionPost::where('group_id', $id)->delete();

        // Detach members
        $group->members()->detach();

        // Delete group
        $group->delete();

        return response()->json(['message' => 'Discussion group deleted successfully']);
    }

    /**
     * ADMIN: Delete a post
     * DELETE /api/admin/groups/{groupId}/posts/{postId}
     */
    public function deletePost($groupId, $postId)
    {
        DiscussionGroup::findOrFail($groupId);

        $post = DiscussionPost::findOrFail($postId);

        if ($post->group_id != $groupId) {
            return response()->json(['message' => 'Post not found in this group'], 404);
        }

        $post->delete();

        return response()->json(['message' => 'Post deleted successfully']);
    }

    /**
     * ADMIN: Add members to group
     * POST /api/admin/groups/{id}/add-members
     */
    public function addMembers(Request $request, $id)
    {
        $group = DiscussionGroup::findOrFail($id);

        $request->validate([
            'member_ids' => 'required|array|exists:users,id'
        ]);

        $group->members()->attach($request->member_ids);

        return response()->json([
            'message' => 'Members added successfully',
            'data' => $group->load('members')
        ]);
    }

    /**
     * ADMIN: Remove members from group
     * DELETE /api/admin/groups/{id}/members
     */
    public function removeMembers(Request $request, $id)
    {
        $group = DiscussionGroup::findOrFail($id);

        $request->validate([
            'member_ids' => 'required|array'
        ]);

        $group->members()->detach($request->member_ids);

        return response()->json([
            'message' => 'Members removed successfully',
            'data' => $group->load('members')
        ]);
    }
}
