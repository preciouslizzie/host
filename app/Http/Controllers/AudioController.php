<?php
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Models\Audio;
use Illuminate\Http\Request;

class AudioController extends Controller
{
    // CREATE
    public function store(Request $request)
    {
        $request->validate([
            'title'     => 'required|string',
            'audio_url' => 'required|url',
        ]);

        $audio = Audio::create([
            'title'     => $request->title,
            'audio_url' => $request->audio_url,
            'file_path' => $request->file_path,
            'etag'      => $request->etag,
        ]);

        return response()->json([
            'message' => 'Audio created successfully',
            'data' => $audio,
        ], 201);
    }

    // READ ALL
    public function index()
    {
        return response()->json(Audio::latest()->get());
    }

    // READ ONE
    public function show($id)
    {
        $audio = Audio::findOrFail($id);
        return response()->json($audio);
    }

    // UPDATE
    public function update(Request $request, $id)
    {
        $audio = Audio::findOrFail($id);

        $audio->update($request->only([
            'title',
            'audio_url',
            'file_path',
            'etag',
        ]));

        return response()->json([
            'message' => 'Audio updated successfully',
            'data' => $audio,
        ]);
    }

    // DELETE
    public function destroy($id)
    {
        $audio = Audio::findOrFail($id);
        $audio->delete();

        return response()->json([
            'message' => 'Audio deleted successfully'
        ]);
    }
}
