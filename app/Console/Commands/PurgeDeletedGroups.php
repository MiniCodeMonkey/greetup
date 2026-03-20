<?php

namespace App\Console\Commands;

use App\Models\Group;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('groups:purge-deleted')]
#[Description('Permanently delete groups that were soft-deleted more than 90 days ago')]
class PurgeDeletedGroups extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $groups = Group::onlyTrashed()
            ->where('deleted_at', '<', now()->subDays(90))
            ->get();

        $count = $groups->count();

        foreach ($groups as $group) {
            $group->forceDelete();
        }

        $this->info("Purged {$count} soft-deleted group(s).");

        return self::SUCCESS;
    }
}
