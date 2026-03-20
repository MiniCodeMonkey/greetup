<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Disable Scout search syncing during seeding
        User::disableSearchSyncing();
        Group::disableSearchSyncing();
        Event::disableSearchSyncing();

        $this->call([
            RoleSeeder::class,
            InterestSeeder::class,
            UserSeeder::class,
            GroupSeeder::class,
            EventSeeder::class,
            RsvpSeeder::class,
            DiscussionSeeder::class,
            EventCommentSeeder::class,
            EventFeedbackSeeder::class,
            DirectMessageSeeder::class,
            ReportSeeder::class,
            SettingsSeeder::class,
        ]);

        User::enableSearchSyncing();
        Group::enableSearchSyncing();
        Event::enableSearchSyncing();
    }
}
