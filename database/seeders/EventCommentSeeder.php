<?php

namespace Database\Seeders;

use App\Enums\EventStatus;
use App\Models\Comment;
use App\Models\Event;
use App\Models\EventCommentLike;
use Illuminate\Database\Seeder;

class EventCommentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Focus on upcoming/published events for comments
        $events = Event::with('group.members')
            ->where('status', EventStatus::Published)
            ->get();

        $totalComments = 0;

        foreach ($events as $event) {
            $members = $event->group->members;
            if ($members->count() < 2) {
                continue;
            }

            // 3-8 comments on upcoming events
            $commentCount = fake()->numberBetween(3, 8);

            $comments = [];
            for ($i = 0; $i < $commentCount; $i++) {
                $author = $members->random();
                $body = fake()->randomElement([
                    'Looking forward to this one! 🎉',
                    'Can\'t wait! Will there be food?',
                    'Is there parking nearby?',
                    'I might be 10 minutes late — save me a spot!',
                    'Great lineup for this event.',
                    'Will this be recorded for those who can\'t attend?',
                    'This sounds amazing, count me in!',
                    'Any prerequisites for attending?',
                    'What should I bring?',
                    'First time attending — excited!',
                    'Is there a dress code?',
                    'Can I bring a friend who isn\'t a member yet?',
                ]);

                $comment = Comment::create([
                    'event_id' => $event->id,
                    'user_id' => $author->id,
                    'body' => $body,
                    'body_html' => '<p>'.$body.'</p>',
                ]);

                $comments[] = $comment;
                $totalComments++;
            }

            // Add some replies to existing comments
            $replyCount = fake()->numberBetween(1, min(3, count($comments)));
            for ($i = 0; $i < $replyCount; $i++) {
                $parent = $comments[array_rand($comments)];
                $replier = $members->where('id', '!=', $parent->user_id)->first() ?? $members->random();
                $replyBody = fake()->randomElement([
                    'Good question — I\'d like to know too!',
                    'Yes, there\'s a car park round the corner.',
                    'No worries, we\'ll save your seat!',
                    'See you there!',
                    'Absolutely, everyone is welcome.',
                    'I\'ll bring some snacks!',
                ]);

                Comment::create([
                    'event_id' => $event->id,
                    'user_id' => $replier->id,
                    'parent_id' => $parent->id,
                    'body' => $replyBody,
                    'body_html' => '<p>'.$replyBody.'</p>',
                ]);
                $totalComments++;
            }

            // Add likes to some comments
            foreach ($comments as $comment) {
                if (fake()->boolean(60)) {
                    $likers = $members->where('id', '!=', $comment->user_id)
                        ->random(min(fake()->numberBetween(1, 4), $members->count() - 1));

                    foreach ($likers as $liker) {
                        EventCommentLike::firstOrCreate([
                            'comment_id' => $comment->id,
                            'user_id' => $liker->id,
                        ]);
                    }
                }
            }
        }
    }
}
