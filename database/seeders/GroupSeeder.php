<?php

namespace Database\Seeders;

use App\Enums\GroupRole;
use App\Models\Group;
use App\Models\GroupMembershipQuestion;
use App\Models\User;
use Illuminate\Database\Seeder;

class GroupSeeder extends Seeder
{
    /**
     * Group definitions per spec.
     *
     * @var list<array{name: string, category: string, member_target: int, open: bool, organizer_email: string, location: string, latitude: float, longitude: float, timezone: string, description: string, welcome_message: string|null, interests: list<string>, questions: list<string>}>
     */
    private const array GROUPS = [
        [
            'name' => 'Copenhagen Laravel Meetup',
            'category' => 'tech',
            'member_target' => 35,
            'open' => true,
            'organizer_email' => 'lars@greetup.test',
            'location' => 'Copenhagen, Denmark',
            'latitude' => 55.6761000,
            'longitude' => 12.5683000,
            'timezone' => 'Europe/Copenhagen',
            'description' => "# Copenhagen Laravel Meetup\n\nThe **largest Laravel community** in Denmark. We meet monthly to share knowledge, discuss best practices, and network with fellow PHP and Laravel developers.\n\n## What we do\n- Monthly talks and live coding sessions\n- Quarterly hackathons\n- Annual Laravel conference watch parties\n\nWhether you're a beginner or a seasoned pro, everyone is welcome!",
            'welcome_message' => null,
            'interests' => ['Laravel', 'PHP', 'Web Development', 'Open Source'],
            'questions' => [],
        ],
        [
            'name' => 'Berlin JavaScript Community',
            'category' => 'tech',
            'member_target' => 28,
            'open' => true,
            'organizer_email' => 'katja@greetup.test',
            'location' => 'Berlin, Germany',
            'latitude' => 52.5200000,
            'longitude' => 13.4050000,
            'timezone' => 'Europe/Berlin',
            'description' => "# Berlin JavaScript Community\n\nBerlin's home for **JavaScript developers**. From vanilla JS to React, Vue, and Node.js — we cover it all.\n\n## Regular events\n- Bi-weekly meetups at co-working spaces\n- Workshop Wednesdays for hands-on learning\n- JS quiz nights (yes, really!)\n\nJoin us and level up your JS skills!",
            'welcome_message' => null,
            'interests' => ['JavaScript', 'React', 'Vue.js', 'Web Development'],
            'questions' => [],
        ],
        [
            'name' => 'London Book Club',
            'category' => 'lifestyle',
            'member_target' => 20,
            'open' => false,
            'organizer_email' => 'james@greetup.test',
            'location' => 'London, United Kingdom',
            'latitude' => 51.5074000,
            'longitude' => -0.1278000,
            'timezone' => 'Europe/London',
            'description' => "# London Book Club\n\nA friendly community of **book lovers** meeting across London's cosiest pubs and cafés.\n\n## How it works\n- We pick one book per month by group vote\n- Meet to discuss over drinks on the last Saturday\n- Mix of fiction, non-fiction, and classics\n\nAll reading levels and tastes welcome — the only requirement is a love of books!",
            'welcome_message' => 'Welcome to the London Book Club! Please introduce yourself in the discussions section and tell us what you are currently reading.',
            'interests' => ['Book Club', 'Writing'],
            'questions' => [
                'What genres do you enjoy reading?',
                'How many books do you typically read per month?',
                'Why are you interested in joining our book club?',
            ],
        ],
        [
            'name' => 'NYC Hiking Adventures',
            'category' => 'lifestyle',
            'member_target' => 25,
            'open' => true,
            'organizer_email' => 'maria@greetup.test',
            'location' => 'New York, NY',
            'latitude' => 40.7128000,
            'longitude' => -74.0060000,
            'timezone' => 'America/New_York',
            'description' => "# NYC Hiking Adventures\n\nExplore the **great outdoors** around New York City! From easy walks in Central Park to challenging trails in the Catskills.\n\n## What to expect\n- Weekend hikes (difficulty rated easy to hard)\n- Seasonal camping trips\n- Post-hike meals and drinks\n\nBring comfortable shoes and a sense of adventure!",
            'welcome_message' => null,
            'interests' => ['Hiking', 'Running', 'Cycling'],
            'questions' => [],
        ],
        [
            'name' => 'Copenhagen Photography Walks',
            'category' => 'creative',
            'member_target' => 15,
            'open' => true,
            'organizer_email' => 'sofie@greetup.test',
            'location' => 'Copenhagen, Denmark',
            'latitude' => 55.6761000,
            'longitude' => 12.5683000,
            'timezone' => 'Europe/Copenhagen',
            'description' => "# Copenhagen Photography Walks\n\nCapture the beauty of Copenhagen through your lens! Whether you shoot with a **DSLR or a smartphone**, you're welcome here.\n\n## Activities\n- Bi-weekly photo walks through different neighbourhoods\n- Monthly photo critique sessions\n- Seasonal exhibitions at local cafés\n\nAll skill levels welcome — let's explore and create together!",
            'welcome_message' => null,
            'interests' => ['Photography', 'Art'],
            'questions' => [],
        ],
        [
            'name' => 'Remote Workers Denmark',
            'category' => 'professional',
            'member_target' => 22,
            'open' => true,
            'organizer_email' => 'henrik@greetup.test',
            'location' => 'Copenhagen, Denmark',
            'latitude' => 55.6761000,
            'longitude' => 12.5683000,
            'timezone' => 'Europe/Copenhagen',
            'description' => "# Remote Workers Denmark\n\nConnect with fellow **remote professionals** across Denmark. Combat isolation and build meaningful professional relationships.\n\n## What we offer\n- Weekly co-working sessions at different cafés\n- Monthly skill-sharing presentations\n- Quarterly social dinners\n\nFreelancers, employees, and entrepreneurs — all remote workers are welcome!",
            'welcome_message' => null,
            'interests' => ['Entrepreneurship', 'Marketing', 'Product Management'],
            'questions' => [],
        ],
        [
            'name' => 'Board Game Nights CPH',
            'category' => 'lifestyle',
            'member_target' => 18,
            'open' => true,
            'organizer_email' => 'mikkel@greetup.test',
            'location' => 'Copenhagen, Denmark',
            'latitude' => 55.6761000,
            'longitude' => 12.5683000,
            'timezone' => 'Europe/Copenhagen',
            'description' => "# Board Game Nights CPH\n\nRoll the dice and draw a card — it's **game night** in Copenhagen!\n\n## What we play\n- Modern classics: Catan, Ticket to Ride, Wingspan\n- Strategy heavyweights: Terraforming Mars, Gloomhaven\n- Party games: Codenames, Wavelength, Dixit\n\nWe have a huge collection but feel free to bring your own favourites. Snacks provided!",
            'welcome_message' => null,
            'interests' => ['Board Games', 'Language Exchange'],
            'questions' => [],
        ],
        [
            'name' => 'Women in Tech Berlin',
            'category' => 'tech',
            'member_target' => 20,
            'open' => false,
            'organizer_email' => 'anna@greetup.test',
            'location' => 'Berlin, Germany',
            'latitude' => 52.5200000,
            'longitude' => 13.4050000,
            'timezone' => 'Europe/Berlin',
            'description' => "# Women in Tech Berlin\n\nA **supportive community** for women and non-binary individuals working in technology in Berlin.\n\n## Our mission\n- Create a safe space for networking and mentorship\n- Host talks from inspiring women in the industry\n- Provide career development workshops\n- Build lasting professional relationships\n\nAllies are welcome to attend select events!",
            'welcome_message' => 'Welcome to Women in Tech Berlin! We are glad you are here. Please introduce yourself in the discussions and let us know what you hope to get from this community.',
            'interests' => ['Web Development', 'Data Science', 'Design', 'Entrepreneurship'],
            'questions' => [
                'Tell us about yourself and your role in tech.',
                'What do you hope to gain from this community?',
            ],
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $allUsers = User::all();

        foreach (self::GROUPS as $groupDef) {
            $organizer = User::where('email', $groupDef['organizer_email'])->firstOrFail();

            $descriptionHtml = str($groupDef['description'])->markdown();

            $group = Group::firstOrCreate(
                ['name' => $groupDef['name']],
                [
                    'description' => $groupDef['description'],
                    'description_html' => $descriptionHtml,
                    'organizer_id' => $organizer->id,
                    'location' => $groupDef['location'],
                    'latitude' => $groupDef['latitude'],
                    'longitude' => $groupDef['longitude'],
                    'timezone' => $groupDef['timezone'],
                    'requires_approval' => ! $groupDef['open'],
                    'welcome_message' => $groupDef['welcome_message'],
                    'is_active' => true,
                ],
            );

            // Attach interests
            $group->attachTags($groupDef['interests'], 'interest');

            // Add membership questions for approval groups
            foreach ($groupDef['questions'] as $index => $question) {
                GroupMembershipQuestion::firstOrCreate(
                    ['group_id' => $group->id, 'question' => $question],
                    ['is_required' => true, 'sort_order' => $index + 1],
                );
            }

            // Add organizer as member with Organizer role
            $this->addMember($group, $organizer, GroupRole::Organizer, now()->subMonths(6));

            // Build a pool of eligible members (exclude the organizer)
            $eligible = $allUsers->where('id', '!=', $organizer->id)->shuffle();

            // Add co-organizers (1-2)
            $coOrgCount = fake()->numberBetween(1, 2);
            foreach ($eligible->splice(0, $coOrgCount) as $user) {
                $this->addMember($group, $user, GroupRole::CoOrganizer, now()->subMonths(rand(3, 5)));
            }

            // Add assistant organizers (1-2)
            $assistantCount = fake()->numberBetween(1, 2);
            foreach ($eligible->splice(0, $assistantCount) as $user) {
                $this->addMember($group, $user, GroupRole::AssistantOrganizer, now()->subMonths(rand(2, 4)));
            }

            // Add event organizers (1-2)
            $eventOrgCount = fake()->numberBetween(1, 2);
            foreach ($eligible->splice(0, $eventOrgCount) as $user) {
                $this->addMember($group, $user, GroupRole::EventOrganizer, now()->subMonths(rand(1, 3)));
            }

            // Fill remaining regular members up to target
            $currentCount = 1 + $coOrgCount + $assistantCount + $eventOrgCount;
            $remainingTarget = max(0, $groupDef['member_target'] - $currentCount);
            foreach ($eligible->splice(0, $remainingTarget) as $user) {
                $this->addMember($group, $user, GroupRole::Member, now()->subDays(rand(1, 150)));
            }
        }
    }

    private function addMember(Group $group, User $user, GroupRole $role, \DateTimeInterface $joinedAt): void
    {
        if (! $group->members()->where('user_id', $user->id)->exists()) {
            $group->members()->attach($user->id, [
                'role' => $role->value,
                'joined_at' => $joinedAt,
                'is_banned' => false,
            ]);
        }
    }
}
