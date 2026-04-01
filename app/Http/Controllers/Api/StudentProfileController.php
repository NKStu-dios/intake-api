<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StudentProfileController extends Controller
{
    public function store(Request $request)
    {
        if ($request->user()->role !== 'student') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($request->user()->studentProfile) {
            return response()->json(['message' => 'Profile already exists. Use PUT to update.'], 409);
        }

        $validated = $request->validate([
            'full_name'     => 'required|string|max:255',
            'phone'         => 'required|string|max:20',
            'university'    => 'required|string|max:255',
            'department'    => 'required|string|max:255',
            'field_of_study'=> 'required|string|max:255',
            'current_level' => 'required|string|max:20',
            'cgpa'          => 'nullable|numeric|min:0|max:5',
        ]);

        $profile = $request->user()->studentProfile()->create($validated);

        return response()->json($profile, 201);
    }

    public function show(Request $request)
    {
        if ($request->user()->role !== 'student') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $profile = $request->user()->studentProfile;

        if (!$profile) {
            return response()->json(['message' => 'Profile not found.'], 404);
        }

        return response()->json($profile);
    }

    public function update(Request $request)
    {
        if ($request->user()->role !== 'student') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $profile = $request->user()->studentProfile;

        if (!$profile) {
            return response()->json(['message' => 'Profile not found. Use POST to create.'], 404);
        }

        $validated = $request->validate([
            'full_name'     => 'sometimes|string|max:255',
            'phone'         => 'sometimes|string|max:20',
            'university'    => 'sometimes|string|max:255',
            'department'    => 'sometimes|string|max:255',
            'field_of_study'=> 'sometimes|string|max:255',
            'current_level' => 'sometimes|string|max:20',
            'cgpa'          => 'nullable|numeric|min:0|max:5',
        ]);

        $profile->update($validated);

        return response()->json($profile);
    }
}
