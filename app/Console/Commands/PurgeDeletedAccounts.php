<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('accounts:purge-deleted')]
#[Description('Permanently delete user accounts that were soft-deleted more than 30 days ago')]
class PurgeDeletedAccounts extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $users = User::onlyTrashed()
            ->where('deleted_at', '<', now()->subDays(30))
            ->get();

        $count = $users->count();

        foreach ($users as $user) {
            $user->forceDelete();
        }

        $this->info("Purged {$count} soft-deleted user account(s).");

        return self::SUCCESS;
    }
}
