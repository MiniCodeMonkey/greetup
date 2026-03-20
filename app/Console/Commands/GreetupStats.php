<?php

namespace App\Console\Commands;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Group;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('greetup:stats')]
#[Description('Display platform statistics')]
class GreetupStats extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Greetup Platform Statistics');
        $this->info(str_repeat('─', 40));

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Users', User::query()->count()],
                ['Total Groups', Group::query()->count()],
                ['Total Events', Event::query()->count()],
                ['Published Events', Event::query()->where('status', EventStatus::Published)->count()],
                ['Active Events This Month', $this->activeEventsThisMonth()],
                ['Past Events', Event::query()->where('status', EventStatus::Past)->count()],
            ],
        );

        return self::SUCCESS;
    }

    private function activeEventsThisMonth(): int
    {
        return Event::query()
            ->where('status', EventStatus::Published)
            ->where('starts_at', '>=', now()->startOfMonth())
            ->where('starts_at', '<=', now()->endOfMonth())
            ->count();
    }
}
