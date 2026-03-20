<?php

namespace App\Jobs;

use App\Models\Event;
use App\Services\WaitlistService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PromoteFromWaitlist implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public Event $event) {}

    /**
     * Execute the job.
     */
    public function handle(WaitlistService $waitlistService): void
    {
        $waitlistService->promoteAll($this->event);
    }
}
