<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CompanyProfileController extends Controller
{
    public function store(Request $request)
    {
        if ($request->user()->role !== 'company') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($request->user()->companyProfile) {
            return response()->json(['message' => 'Profile already exists. Use PUT to update.'], 409);
        }

        $validated = $request->validate([
            'company_name'            => 'required|string|max:255',
            'industry'                => 'required|string|max:255',
            'state'                   => 'required|string|max:100',
            'city'                    => 'required|string|max:100',
            'contact_email'           => 'required|email',
            'contact_phone'           => 'required|string|max:20',
            'contact_whatsapp'        => 'nullable|string|max:20',
            'website'                 => 'nullable|url',
            'description'             => 'nullable|string',
            'past_projects'           => 'nullable|string',
            'linkedin_url'            => 'nullable|url',
            'instagram_url'           => 'nullable|url',
            'accommodation_available' => 'boolean',
        ]);

        $profile = $request->user()->companyProfile()->create($validated);

        return response()->json($profile, 201);
    }

    public function show(Request $request)
    {
        if ($request->user()->role !== 'company') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $profile = $request->user()->companyProfile;

        if (!$profile) {
            return response()->json(['message' => 'Profile not found.'], 404);
        }

        return response()->json($profile);
    }

    public function update(Request $request)
    {
        if ($request->user()->role !== 'company') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $profile = $request->user()->companyProfile;

        if (!$profile) {
            return response()->json(['message' => 'Profile not found. Use POST to create.'], 404);
        }

        $validated = $request->validate([
            'company_name'            => 'sometimes|string|max:255',
            'industry'                => 'sometimes|string|max:255',
            'state'                   => 'sometimes|string|max:100',
            'city'                    => 'sometimes|string|max:100',
            'contact_email'           => 'sometimes|email',
            'contact_phone'           => 'sometimes|string|max:20',
            'contact_whatsapp'        => 'nullable|string|max:20',
            'website'                 => 'nullable|url',
            'description'             => 'nullable|string',
            'past_projects'           => 'nullable|string',
            'linkedin_url'            => 'nullable|url',
            'instagram_url'           => 'nullable|url',
            'accommodation_available' => 'boolean',
        ]);

        $profile->update($validated);

        return response()->json($profile);
    }
}