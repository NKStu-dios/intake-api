<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SavedPosition;
use App\Models\Position;
use Illuminate\Http\Request;

class SavedPositionController extends Controller
{
    public function index(Request $request)
    {
        if ($request->user()->role !== 'student') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $profile = $request->user()->studentProfile;

        $saved = SavedPosition::where('student_id', $profile->id)
            ->with('position.company')
            ->latest()
            ->get();

        return response()->json($saved);
    }

    public function store(Request $request, Position $position)
    {
        if ($request->user()->role !== 'student') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $profile = $request->user()->studentProfile;

        $already = SavedPosition::where('student_id', $profile->id)
            ->where('position_id', $position->id)
            ->exists();

        if ($already) {
            return response()->json(['message' => 'Position already saved.'], 409);
        }

        $saved = SavedPosition::create([
            'student_id'  => $profile->id,
            'position_id' => $position->id,
        ]);

        return response()->json($saved, 201);
    }

    public function destroy(Request $request, Position $position)
    {
        if ($request->user()->role !== 'student') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $profile = $request->user()->studentProfile;

        SavedPosition::where('student_id', $profile->id)
            ->where('position_id', $position->id)
            ->delete();

        return response()->json(['message' => 'Position removed from saved.']);
    }
}