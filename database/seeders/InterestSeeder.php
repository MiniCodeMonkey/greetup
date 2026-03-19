<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Tags\Tag;

class InterestSeeder extends Seeder
{
    /**
     * Seed interest tags across categories per spec section 9.2.
     *
     * @var array<string, list<string>>
     */
    private const array INTERESTS = [
        'Technology' => [
            'Web Development',
            'Mobile Development',
            'Data Science',
            'Machine Learning',
            'DevOps',
            'Cybersecurity',
            'Open Source',
            'Game Development',
        ],
        'Languages & Frameworks' => [
            'PHP',
            'Laravel',
            'JavaScript',
            'Python',
            'Rust',
            'Go',
            'React',
            'Vue.js',
        ],
        'Creative' => [
            'Photography',
            'Writing',
            'Music',
            'Art',
            'Film',
        ],
        'Lifestyle' => [
            'Hiking',
            'Running',
            'Cycling',
            'Cooking',
            'Board Games',
            'Book Club',
            'Language Exchange',
            'Parenting',
        ],
        'Professional' => [
            'Entrepreneurship',
            'Marketing',
            'Design',
            'Product Management',
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::INTERESTS as $interests) {
            foreach ($interests as $interest) {
                Tag::findOrCreate($interest, 'interest');
            }
        }
    }
}
