<?php

namespace Database\Seeders;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\DirectMessage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;

class DirectMessageSeeder extends Seeder
{
    /**
     * Message samples for realistic conversations.
     *
     * @var list<list<string>>
     */
    private const array CONVERSATION_SCRIPTS = [
        [
            'Hey! Great talk at the meetup last night.',
            'Thanks! Glad you enjoyed it. Any questions about the topic?',
            'Actually yes — could you share the slides?',
            'Sure, I\'ll post them in the group discussion later today.',
        ],
        [
            'Are you coming to the event next Thursday?',
            'Wouldn\'t miss it! Are you bringing anyone?',
            'Maybe — I\'ll check with a friend.',
        ],
        [
            'Hi, I saw you\'re also interested in photography walks. Want to join us Saturday?',
            'That sounds great! What time and where?',
            'We meet at 10am at Nyhavn. Bring your camera!',
            'Perfect, see you there!',
            'Looking forward to it 📸',
        ],
        [
            'Welcome to the group! Let me know if you have any questions.',
            'Thanks for the warm welcome! Looking forward to my first event.',
        ],
        [
            'Hey, wanted to follow up on our conversation about remote work tools.',
            'Oh yes! I\'ve been meaning to try that app you mentioned.',
            'It\'s really changed how I manage my day. Happy to walk you through it.',
        ],
        [
            'Do you know if there\'s a code of conduct for the group?',
            'Yes, it\'s linked in the group description. The organisers take it seriously.',
            'Good to know, thanks!',
        ],
        [
            'I\'m thinking of proposing a talk for next month. Any tips?',
            'Just pitch it to the organiser — they\'re always looking for speakers!',
            'I\'ll draft something this weekend. Nervous but excited!',
            'You\'ll do great. The community is really supportive.',
        ],
        [
            'Missed the last event — was it any good?',
            'It was excellent! The speaker covered some really practical stuff.',
        ],
        [
            'Fancy grabbing coffee after the next meetup?',
            'Definitely! There\'s a nice place round the corner from the venue.',
            'Perfect. See you there!',
        ],
        [
            'Just wanted to say thanks for organising everything. It really makes a difference.',
            'That means a lot! It\'s great to see the community growing.',
            'Keep up the fantastic work!',
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        if ($users->count() < 4) {
            return;
        }

        $usedPairs = [];

        foreach (self::CONVERSATION_SCRIPTS as $index => $messages) {
            // Pick two distinct users
            $pair = $this->pickUniquePair($users, $usedPairs);
            if ($pair === null) {
                break;
            }

            [$userA, $userB] = $pair;
            $usedPairs[] = $pair;

            $conversation = Conversation::create();

            // Some conversations are read, some have unread messages
            $isRead = fake()->boolean(60);
            $baseTime = Carbon::now()->subDays(rand(1, 14));

            ConversationParticipant::create([
                'conversation_id' => $conversation->id,
                'user_id' => $userA->id,
                'last_read_at' => $isRead ? now() : $baseTime,
                'is_muted' => false,
            ]);

            ConversationParticipant::create([
                'conversation_id' => $conversation->id,
                'user_id' => $userB->id,
                'last_read_at' => $isRead ? now() : null,
                'is_muted' => false,
            ]);

            // Create messages alternating between users
            foreach ($messages as $msgIndex => $body) {
                $sender = $msgIndex % 2 === 0 ? $userA : $userB;

                DirectMessage::create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $sender->id,
                    'body' => $body,
                    'created_at' => (clone $baseTime)->addMinutes($msgIndex * rand(5, 60)),
                    'updated_at' => (clone $baseTime)->addMinutes($msgIndex * rand(5, 60)),
                ]);
            }
        }
    }

    /**
     * @param  Collection<int, User>  $users
     * @param  list<array{User, User}>  $usedPairs
     * @return array{User, User}|null
     */
    private function pickUniquePair($users, array $usedPairs): ?array
    {
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $pair = $users->random(2);
            $a = $pair->first();
            $b = $pair->last();

            $alreadyUsed = false;
            foreach ($usedPairs as [$existingA, $existingB]) {
                if (($a->id === $existingA->id && $b->id === $existingB->id)
                    || ($a->id === $existingB->id && $b->id === $existingA->id)) {
                    $alreadyUsed = true;
                    break;
                }
            }

            if (! $alreadyUsed) {
                return [$a, $b];
            }
        }

        return null;
    }
}
