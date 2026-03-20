<?php

namespace Database\Seeders;

use App\Models\Discussion;
use App\Models\DiscussionReply;
use App\Models\Group;
use Illuminate\Database\Seeder;

class DiscussionSeeder extends Seeder
{
    /**
     * Discussion topics by group category.
     *
     * @var array<string, list<string>>
     */
    private const array TOPICS = [
        'tech' => [
            'What IDE/editor do you use and why?',
            'Best resources for learning in 2026?',
            'How do you handle testing in your projects?',
            'Share your favourite open source project',
            'Career advice for junior developers',
            'What are you building right now?',
        ],
        'lifestyle' => [
            'Introduce yourself!',
            'What got you into this hobby?',
            'Favourite spots in the city for our activity?',
            'Suggestions for next month\'s meetup location?',
            'Share your recent experiences!',
        ],
        'creative' => [
            'Share your latest work!',
            'What gear/tools do you recommend?',
            'Favourite inspiration sources?',
            'Looking for collaboration partners',
            'Tips for beginners',
        ],
        'professional' => [
            'Best co-working spaces in the city?',
            'How do you stay productive working remotely?',
            'Networking tips and strategies',
            'Share your remote work setup',
            'Freelance vs employment — what works for you?',
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $groups = Group::with('members')->get();
        $discussionCount = 0;

        foreach ($groups as $group) {
            $members = $group->members;
            if ($members->count() < 2) {
                continue;
            }

            $category = $this->getCategory($group->name);
            $topics = self::TOPICS[$category] ?? self::TOPICS['lifestyle'];

            // 2-3 discussions per group
            $count = fake()->numberBetween(2, 3);
            $selectedTopics = fake()->randomElements($topics, min($count, count($topics)));

            foreach ($selectedTopics as $index => $topic) {
                $author = $members->random();
                $body = fake()->paragraphs(rand(1, 3), true);
                $isPinned = $index === 0; // Pin the first discussion in each group

                $discussion = Discussion::create([
                    'group_id' => $group->id,
                    'user_id' => $author->id,
                    'title' => $topic,
                    'body' => $body,
                    'body_html' => '<p>'.nl2br(e($body)).'</p>',
                    'is_pinned' => $isPinned,
                    'is_locked' => false,
                    'last_activity_at' => now()->subDays(rand(0, 30)),
                ]);

                // 3-10 replies per discussion
                $replyCount = fake()->numberBetween(3, 10);
                $otherMembers = $members->where('id', '!=', $author->id);

                for ($r = 0; $r < $replyCount; $r++) {
                    $replier = $otherMembers->isNotEmpty() ? $otherMembers->random() : $author;
                    $replyBody = fake()->paragraph();

                    DiscussionReply::create([
                        'discussion_id' => $discussion->id,
                        'user_id' => $replier->id,
                        'body' => $replyBody,
                        'body_html' => '<p>'.$replyBody.'</p>',
                    ]);
                }

                $discussion->update(['last_activity_at' => now()->subHours(rand(1, 72))]);
                $discussionCount++;
            }
        }

        // Lock 1 discussion in the largest group (Copenhagen Laravel = 35 members)
        $largestGroup = Group::orderByDesc(
            Group::query()->getQuery()->newQuery()
                ->selectRaw('count(*)')
                ->from('group_members')
                ->whereColumn('group_members.group_id', 'groups.id')
        )->first();

        if ($largestGroup) {
            $discussion = Discussion::where('group_id', $largestGroup->id)
                ->where('is_pinned', false)
                ->first();

            $discussion?->update(['is_locked' => true]);
        }
    }

    private function getCategory(string $groupName): string
    {
        if (str_contains($groupName, 'Laravel') || str_contains($groupName, 'JavaScript') || str_contains($groupName, 'Women in Tech')) {
            return 'tech';
        }
        if (str_contains($groupName, 'Photography')) {
            return 'creative';
        }
        if (str_contains($groupName, 'Remote Workers')) {
            return 'professional';
        }

        return 'lifestyle';
    }
}
