<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Blog; 
use Illuminate\Support\Facades\Storage;

class BlogController extends Controller
{
    public function index()
    {
        return response()->json(Blog::all());
    }
    
    public function show($id)
    {
        $blog = Blog::findOrFail($id);
        return response()->json($blog);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'post' => 'required|string',
            'image' => 'nullable|image|mimes:jpg,png,jpeg,gif|max:2048',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('blogs', 'public');
        }

        $blog = Blog::create([
            'title' => $request->title,
            'post' => $request->post,
            'image' => $imagePath,
        ]);

        return response()->json($blog, 201);
    }
    
    //  UPDATE existing blog
    public function update(Request $request, $id)
    {
        $blog = Blog::findOrFail($id);

        $request->validate([
            'title' => 'sometimes|string|max:255',
            'post' => 'sometimes|string',
            'image' => 'nullable|image|mimes:jpg,png,jpeg,gif|max:2048',
        ]);

        if ($request->hasFile('image')) {
            // delete old image if exists
            if ($blog->image) {
                Storage::disk('public')->delete($blog->image);
            }
            $imagePath = $request->file('image')->store('blogs', 'public');
            $blog->image = $imagePath;
        }

        if ($request->has('title')) $blog->title = $request->title;
        if ($request->has('post')) $blog->post = $request->post;

        $blog->save();

        return response()->json($blog);
    }

    //  DELETE a blog
    public function destroy($id)
    {
        $blog = Blog::findOrFail($id);

        if ($blog->image && Storage:: exists ($blog->image)) {
            Storage::disk('public')->delete($blog->image);
        }

        $blog->delete();

        return response()->json(['message' => 'Blog deleted successfully'], 200);
    }
}


