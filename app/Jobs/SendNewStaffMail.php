<?php

namespace app\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;

use Illuminate\Support\Facades\Mail;
use app\Mail\NewStaff;
use app\Models\User;
use app\Utilities;

class SendNewStaffMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected User $user, protected string $password)
    {
        //
    }

    /**
     * Middleware for the job.
     */
    public function middleware(): array
    {
        // Prevent sending duplicate emails concurrently for the same user
        return [new WithoutOverlapping($this->user->id)];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Mail::to($this->user->email)->send(new NewStaff($this->user, $this->password));
        } catch (\Exception $e) {
            Utilities::jobLog("Error sending New Staff onboarding email: " . $e->getMessage());
        }
    }
}
