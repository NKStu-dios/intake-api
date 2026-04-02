<?php

namespace App\Console\Commands;

use App\Models\Application;
use App\Models\Notification;
use Illuminate\Console\Command;

class ExpireAcceptances extends Command
{
    protected $signature = 'acceptances:expire';
    protected $description = 'Expire overdue acceptances and promote next waitlist applicant';

    public function handle()
    {
        $expired = Application::where('status', 'accepted')
            ->where('acceptance_deadline', '<', now())
            ->with(['position.company', 'student'])
            ->get();

        foreach ($expired as $application) {
            $application->update(['status' => 'expired']);

            // Notify student
            Notification::create([
                'user_id'                => $application->student->user_id,
                'type'                   => 'acceptance_expired',
                'message'                => "Your acceptance for {$application->position->title} at {$application->position->company->company_name} has expired because you did not confirm in time.",
                'related_application_id' => $application->id,
            ]);

            // Promote next waitlist applicant
            $next = Application::where('position_id', $application->position_id)
                ->where('status', 'waitlist')
                ->orderBy('created_at')
                ->first();

            if (!$next) continue;

            $deadline = now()->addDays($application->position->acceptance_window_days);

            $next->update([
                'status'              => 'accepted',
                'acceptance_deadline' => $deadline,
            ]);

            Notification::create([
                'user_id'                => $next->student->user_id,
                'type'                   => 'waitlist_promoted',
                'message'                => "Good news! You have been promoted from the waitlist and accepted for {$application->position->title} at {$application->position->company->company_name}. You have {$application->position->acceptance_window_days} days to confirm.",
                'related_application_id' => $next->id,
            ]);
        }

        $this->info("Processed {$expired->count()} expired acceptances.");
    }
}