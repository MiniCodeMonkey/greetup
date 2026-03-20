<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Named organizers with realistic details across cities.
     *
     * @var list<array{name: string, email: string, location: string, latitude: float, longitude: float, timezone: string, bio: string}>
     */
    private const array ORGANIZERS = [
        [
            'name' => 'Lars Andersen',
            'email' => 'lars@greetup.test',
            'location' => 'Copenhagen, Denmark',
            'latitude' => 55.6761000,
            'longitude' => 12.5683000,
            'timezone' => 'Europe/Copenhagen',
            'bio' => 'Laravel developer and community builder. Organising tech meetups in Copenhagen since 2019.',
        ],
        [
            'name' => 'Katja Müller',
            'email' => 'katja@greetup.test',
            'location' => 'Berlin, Germany',
            'latitude' => 52.5200000,
            'longitude' => 13.4050000,
            'timezone' => 'Europe/Berlin',
            'bio' => 'JavaScript enthusiast and startup founder. Passionate about building inclusive tech communities in Berlin.',
        ],
        [
            'name' => 'James Whitfield',
            'email' => 'james@greetup.test',
            'location' => 'London, United Kingdom',
            'latitude' => 51.5074000,
            'longitude' => -0.1278000,
            'timezone' => 'Europe/London',
            'bio' => 'Avid reader and book club organiser. Loves discovering hidden literary gems across London.',
        ],
        [
            'name' => 'Maria Santos',
            'email' => 'maria@greetup.test',
            'location' => 'New York, NY',
            'latitude' => 40.7128000,
            'longitude' => -74.0060000,
            'timezone' => 'America/New_York',
            'bio' => 'Outdoor adventurer and trail runner. Leading hiking groups across the NYC metro area.',
        ],
        [
            'name' => 'Sofie Nielsen',
            'email' => 'sofie@greetup.test',
            'location' => 'Copenhagen, Denmark',
            'latitude' => 55.6761000,
            'longitude' => 12.5683000,
            'timezone' => 'Europe/Copenhagen',
            'bio' => 'Street photographer capturing the soul of Scandinavian cities. Love sharing my passion with others.',
        ],
        [
            'name' => 'Henrik Larsen',
            'email' => 'henrik@greetup.test',
            'location' => 'Copenhagen, Denmark',
            'latitude' => 55.6761000,
            'longitude' => 12.5683000,
            'timezone' => 'Europe/Copenhagen',
            'bio' => 'Remote work advocate and coworking space regular. Building the remote workers community in Denmark.',
        ],
        [
            'name' => 'Mikkel Jensen',
            'email' => 'mikkel@greetup.test',
            'location' => 'Copenhagen, Denmark',
            'latitude' => 55.6761000,
            'longitude' => 12.5683000,
            'timezone' => 'Europe/Copenhagen',
            'bio' => 'Board game designer and collector. Hosting game nights every week in Copenhagen.',
        ],
        [
            'name' => 'Anna Weber',
            'email' => 'anna@greetup.test',
            'location' => 'Berlin, Germany',
            'latitude' => 52.5200000,
            'longitude' => 13.4050000,
            'timezone' => 'Europe/Berlin',
            'bio' => 'Software engineer and diversity champion. Building safe spaces for women in tech across Berlin.',
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = Hash::make('password');

        // Admin user
        User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@greetup.test',
            'password' => $password,
            'bio' => 'Platform administrator for Greetup.',
            'location' => 'Copenhagen, Denmark',
            'latitude' => 55.6761000,
            'longitude' => 12.5683000,
            'timezone' => 'Europe/Copenhagen',
        ]);

        // Regular demo user
        User::factory()->create([
            'name' => 'Demo User',
            'email' => 'user@greetup.test',
            'password' => $password,
            'bio' => 'Just a regular user exploring communities and events.',
            'location' => 'London, United Kingdom',
            'latitude' => 51.5074000,
            'longitude' => -0.1278000,
            'timezone' => 'Europe/London',
        ]);

        // 8 named organizers
        foreach (self::ORGANIZERS as $organizer) {
            User::factory()->create([
                'name' => $organizer['name'],
                'email' => $organizer['email'],
                'password' => $password,
                'bio' => $organizer['bio'],
                'location' => $organizer['location'],
                'latitude' => $organizer['latitude'],
                'longitude' => $organizer['longitude'],
                'timezone' => $organizer['timezone'],
            ]);
        }

        // 40 regular users (mix of active/less active)
        User::factory(40)->create([
            'password' => $password,
        ]);
    }
}
