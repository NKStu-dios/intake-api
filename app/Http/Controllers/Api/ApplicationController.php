<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Notification;
use App\Models\Position;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    // Student - apply to a position
    public function store(Request $request, Position $position)
    {
        if ($request->user()->role !== 'student') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $profile = $request->user()->studentProfile;

        if (!$profile) {
            return response()->json(['message' => 'Complete your student profile first.'], 403);
        }

        if ($position->status === 'closed') {
            return response()->json(['message' => 'This position is no longer accepting applications.'], 409);
        }

        $alreadyApplied = Application::where('student_id', $profile->id)
            ->where('position_id', $position->id)
            ->exists();

        if ($alreadyApplied) {
            return response()->json(['message' => 'You have already applied to this position.'], 409);
        }

        $validated = $request->validate([
            'siwes_duration'     => 'required|in:3_months,6_months',
            'submitted_documents'=> 'nullable|array',
        ]);

        $application = Application::create([
            'student_id'          => $profile->id,
            'position_id'         => $position->id,
            'siwes_duration'      => $validated['siwes_duration'],
            'submitted_documents' => $validated['submitted_documents'] ?? null,
            'status'              => 'pending',
        ]);

        // Notify company
        Notification::create([
            'user_id'                => $position->company->user_id,
            'type'                   => 'application_received',
            'message'                => "{$profile->full_name} has applied for {$position->title}.",
            'related_application_id' => $application->id,
        ]);

        return response()->json($application->load('position.company'), 201);
    }

    // Student - view their own applications
    public function myApplications(Request $request)
    {
        if ($request->user()->role !== 'student') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $profile = $request->user()->studentProfile;

        $applications = Application::where('student_id', $profile->id)
            ->with('position.company')
            ->latest()
            ->get();

        return response()->json($applications);
    }

    // Student - withdraw application
    public function withdraw(Request $request, Application $application)
    {
        if ($request->user()->role !== 'student') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $profile = $request->user()->studentProfile;

        if ($application->student_id !== $profile->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if (in_array($application->status, ['confirmed', 'declined', 'expired', 'withdrawn'])) {
            return response()->json(['message' => 'This application cannot be withdrawn.'], 409);
        }

        $wasAccepted = $application->status === 'accepted';

        $application->update(['status' => 'withdrawn']);

        // If they were accepted, promote next waitlist applicant
        if ($wasAccepted) {
            $this->promoteNextWaitlist($application->position);
        }

        return response()->json(['message' => 'Application withdrawn successfully.']);
    }

    // Company - view all applicants for a position
    public function applicants(Request $request, Position $position)
    {
        if ($request->user()->role !== 'company') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $profile = $request->user()->companyProfile;

        if ($position->company_id !== $profile->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $applicants = Application::where('position_id', $position->id)
            ->with('student')
            ->orderBy('created_at')
            ->get()
            ->groupBy('status');

        return response()->json($applicants);
    }

    // Company - move applicant to potential or waitlist
    public function updateStatus(Request $request, Application $application)
    {
        if ($request->user()->role !== 'company') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $profile = $request->user()->companyProfile;
        $position = $application->position;

        if ($position->company_id !== $profile->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:potential,waitlist,declined',
        ]);

        $application->update(['status' => $validated['status']]);

        // Notify student
        $messages = [
            'potential' => "Your application for {$position->title} at {$position->company->company_name} has been moved to Potential.",
            'waitlist'  => "Your application for {$position->title} at {$position->company->company_name} has been added to the Waitlist.",
            'declined'  => "Your application for {$position->title} at {$position->company->company_name} was not successful.",
        ];

        Notification::create([
            'user_id'                => $application->student->user_id,
            'type'                   => 'status_updated',
            'message'                => $messages[$validated['status']],
            'related_application_id' => $application->id,
        ]);

        return response()->json($application);
    }

    // Company - formally accept an applicant
    public function accept(Request $request, Application $application)
    {
        if ($request->user()->role !== 'company') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $profile = $request->user()->companyProfile;
        $position = $application->position;

        if ($position->company_id !== $profile->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($position->isFull()) {
            return response()->json(['message' => 'All slots are filled.'], 409);
        }

        if (!in_array($application->status, ['potential', 'waitlist', 'pending'])) {
            return response()->json(['message' => 'This application cannot be accepted.'], 409);
        }

        $deadline = now()->addDays($position->acceptance_window_days);

        $application->update([
            'status'              => 'accepted',
            'acceptance_deadline' => $deadline,
        ]);

        // Notify student
        Notification::create([
            'user_id'                => $application->student->user_id,
            'type'                   => 'application_accepted',
            'message'                => "You have been accepted for {$position->title} at {$position->company->company_name}. You have {$position->acceptance_window_days} days to confirm your placement.",
            'related_application_id' => $application->id,
        ]);

        return response()->json($application);
    }

    // Internal helper - promote next waitlist applicant
    private function promoteNextWaitlist(Position $position)
    {
        $next = Application::where('position_id', $position->id)
            ->where('status', 'waitlist')
            ->orderBy('created_at')
            ->first();

        if (!$next) return;

        $deadline = now()->addDays($position->acceptance_window_days);

        $next->update([
            'status'              => 'accepted',
            'acceptance_deadline' => $deadline,
        ]);

        Notification::create([
            'user_id'                => $next->student->user_id,
            'type'                   => 'waitlist_promoted',
            'message'                => "Good news! You have been promoted from the waitlist and accepted for {$position->title} at {$position->company->company_name}. You have {$position->acceptance_window_days} days to confirm.",
            'related_application_id' => $next->id,
        ]);
    }

    // Student - confirm accepted placement
    public function confirm(Request $request, Application $application)
    {
        if ($request->user()->role !== 'student') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $profile = $request->user()->studentProfile;

        if ($application->student_id !== $profile->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($application->status !== 'accepted') {
            return response()->json(['message' => 'This application is not pending confirmation.'], 409);
        }

        if (now()->isAfter($application->acceptance_deadline)) {
            return response()->json(['message' => 'Your acceptance window has expired.'], 409);
        }

        $application->update([
            'status'                => 'confirmed',
            'accepted_by_student_at'=> now(),
        ]);

        $position = $application->position;
        $position->increment('slots_filled');

        // Auto close position if full
        if ($position->isFull()) {
            $position->update(['status' => 'closed']);

            // Notify all remaining pending/waitlist applicants
            $remaining = Application::where('position_id', $position->id)
                ->whereIn('status', ['pending', 'waitlist', 'potential'])
                ->with('student')
                ->get();

            foreach ($remaining as $other) {
                Notification::create([
                    'user_id'                => $other->student->user_id,
                    'type'                   => 'position_filled',
                    'message'                => "The {$position->title} position at {$position->company->company_name} has been filled. Unfortunately your application was not successful this time.",
                    'related_application_id' => $other->id,
                ]);
            }
        }

        // Notify company
        Notification::create([
            'user_id'                => $position->company->user_id,
            'type'                   => 'placement_confirmed',
            'message'                => "{$profile->full_name} has confirmed their placement for {$position->title}.",
            'related_application_id' => $application->id,
        ]);

        return response()->json(['message' => 'Placement confirmed successfully.', 'application' => $application]);
    }
}