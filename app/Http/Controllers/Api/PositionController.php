<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Position;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    // Public - no auth needed
    public function browse(Request $request)
    {
        $query = Position::with('company')
            ->where('status', 'open');

        if ($request->has('state')) {
            $query->whereHas('company', function ($q) use ($request) {
                $q->where('state', $request->state);
            });
        }

        if ($request->has('field_required')) {
            $query->where('field_required', $request->field_required);
        }

        if ($request->has('department')) {
            $query->where('department', $request->department);
        }

        if ($request->has('work_type')) {
            $query->where('work_type', $request->work_type);
        }

        if ($request->has('duration')) {
            $query->where('duration', $request->duration);
        }

        $positions = $query->latest()->paginate(20);

        return response()->json($positions);
    }

    // Public - single position detail
    public function show(Position $position)
    {
        $position->load('company');
        return response()->json($position);
    }

    // Company only - create position
    public function store(Request $request)
    {
        if ($request->user()->role !== 'company') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $profile = $request->user()->companyProfile;

        if (!$profile) {
            return response()->json(['message' => 'Complete your company profile first.'], 403);
        }

        $validated = $request->validate([
            'title'                  => 'required|string|max:255',
            'department'             => 'required|string|max:255',
            'field_required'         => 'required|string|max:255',
            'slots_total'            => 'required|integer|min:1',
            'duration'               => 'required|in:3_months,6_months',
            'work_days'              => 'required|string|max:100',
            'work_type'              => 'required|in:onsite,hybrid,remote',
            'work_style'             => 'required|in:hands_on,desk,field,mixed',
            'mentor_available'       => 'boolean',
            'allowance_available'    => 'boolean',
            'allowance_amount'       => 'nullable|numeric|min:0',
            'requirements'           => 'nullable|string',
            'next_steps'             => 'nullable|array',
            'application_deadline'   => 'nullable|date|after:today',
            'acceptance_window_days' => 'integer|min:1|max:14',
        ]);

        $position = $profile->positions()->create($validated);

        return response()->json($position, 201);
    }

    // Company only - view their own positions
    public function index(Request $request)
    {
        if ($request->user()->role !== 'company') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $profile = $request->user()->companyProfile;

        if (!$profile) {
            return response()->json(['message' => 'Complete your company profile first.'], 403);
        }

        $positions = $profile->positions()
            ->withCount('applications')
            ->latest()
            ->get();

        return response()->json($positions);
    }

    // Company only - update their position
    public function update(Request $request, Position $position)
    {
        if ($request->user()->role !== 'company') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $profile = $request->user()->companyProfile;

        if ($position->company_id !== $profile->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'title'                  => 'sometimes|string|max:255',
            'department'             => 'sometimes|string|max:255',
            'field_required'         => 'sometimes|string|max:255',
            'slots_total'            => 'sometimes|integer|min:1',
            'duration'               => 'sometimes|in:3_months,6_months',
            'work_days'              => 'sometimes|string|max:100',
            'work_type'              => 'sometimes|in:onsite,hybrid,remote',
            'work_style'             => 'sometimes|in:hands_on,desk,field,mixed',
            'mentor_available'       => 'boolean',
            'allowance_available'    => 'boolean',
            'allowance_amount'       => 'nullable|numeric|min:0',
            'requirements'           => 'nullable|string',
            'next_steps'             => 'nullable|array',
            'application_deadline'   => 'nullable|date',
            'acceptance_window_days' => 'integer|min:1|max:14',
        ]);

        $position->update($validated);

        return response()->json($position);
    }

    // Company only - manually close a position
    public function close(Request $request, Position $position)
    {
        if ($request->user()->role !== 'company') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $profile = $request->user()->companyProfile;

        if ($position->company_id !== $profile->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $position->update(['status' => 'closed']);

        return response()->json(['message' => 'Position closed successfully.']);
    }
}