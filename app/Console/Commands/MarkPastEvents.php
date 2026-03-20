<?php

namespace App\Console\Commands;

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

#[Signature('events:mark-past')]
#[Description('Transition published events whose end time has passed to past status')]
class MarkPastEvents extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = Event::query()
            ->where('status', EventStatus::Published)
            ->where(function (Builder $query): void {
                $query->where(function (Builder $q): void {
                    $q->whereNotNull('ends_at')
                        ->where('ends_at', '<', now());
                })->orWhere(function (Builder $q): void {
                    $q->whereNull('ends_at')
                        ->where('starts_at', '<', now()->subHours(3));
                });
            })
            ->update(['status' => EventStatus::Past]);

        $this->info("Marked {$count} event(s) as past.");

        return self::SUCCESS;
    }
}
