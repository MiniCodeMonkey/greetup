<?php

namespace Database\Seeders;

use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Models\Comment;
use App\Models\Discussion;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'admin'))->first();
        $users = User::whereDoesntHave('roles', fn ($q) => $q->where('name', 'admin'))->get();

        if ($users->count() < 3) {
            return;
        }

        $comments = Comment::all();
        $discussions = Discussion::all();

        // 3 pending reports
        for ($i = 0; $i < 3; $i++) {
            $reportable = fake()->boolean(60) && $comments->isNotEmpty()
                ? $comments->random()
                : ($discussions->isNotEmpty() ? $discussions->random() : null);

            if ($reportable === null) {
                continue;
            }

            Report::create([
                'reporter_id' => $users->random()->id,
                'reportable_type' => $reportable::class,
                'reportable_id' => $reportable->id,
                'reason' => fake()->randomElement([ReportReason::Spam, ReportReason::Harassment, ReportReason::InappropriateContent]),
                'description' => fake()->optional(0.7)->sentence(),
                'status' => ReportStatus::Pending,
            ]);
        }

        // 1 resolved report
        $reportable = $comments->isNotEmpty() ? $comments->random() : null;
        if ($reportable) {
            Report::create([
                'reporter_id' => $users->random()->id,
                'reportable_type' => $reportable::class,
                'reportable_id' => $reportable->id,
                'reason' => ReportReason::Spam,
                'description' => 'This comment appears to be advertising a product.',
                'status' => ReportStatus::Resolved,
                'reviewed_by' => $admin?->id,
                'reviewed_at' => now()->subDays(2),
                'resolution_notes' => 'Confirmed spam. Comment removed and user warned.',
            ]);
        }

        // 1 dismissed report
        $reportable = $discussions->isNotEmpty() ? $discussions->random() : null;
        if ($reportable) {
            Report::create([
                'reporter_id' => $users->random()->id,
                'reportable_type' => $reportable::class,
                'reportable_id' => $reportable->id,
                'reason' => ReportReason::Misleading,
                'description' => 'I think this post is misleading.',
                'status' => ReportStatus::Dismissed,
                'reviewed_by' => $admin?->id,
                'reviewed_at' => now()->subDay(),
                'resolution_notes' => 'Reviewed and found no violation. Discussion is within community guidelines.',
            ]);
        }
    }
}
