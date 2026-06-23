<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Topic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TopicSeeder extends Seeder
{
    public function run(): void
    {
        $topicGroups = [

            'toeic' => [
                'Contracts',
                'Marketing',
                'Warranties',
                'Business Planning',
                'Conferences',
                'Computers',
                'Office Technology',
                'Electronics',
                'Correspondence',
                'Job Advertising',
                'Apply and Interviewing',
                'Hiring and Training',
                'Salaries and Benefits',
                'Promotion and Awards',
                'Shopping',
                'Ordering Supplies',
                'Shipping',
                'Invoices',
                'Banking',
                'Investments',
                'Taxes',
                'Property and Departments',
                'Restaurants',
                'Entertainment',
                'Travel',
                'Hotels',
                'Airports',
            ],

            'ielts' => [
                'Education',
                'Environment',
                'Technology',
                'Health',
                'Culture',
                'Media',
                'Communication',
                'Transportation',
                'Tourism',
                'Globalization',
                'Climate Change',
                'Science',
                'Crime and Punishment',
                'Work and Career',
                'Government',
                'Social Issues',
                'Animals',
                'History',
                'Space Exploration',
                'Art and Literature',
            ],

            'toefl' => [
                'Biology',
                'Chemistry',
                'Physics',
                'Astronomy',
                'Geography',
                'Geology',
                'History',
                'Psychology',
                'Sociology',
                'Anthropology',
                'Economics',
                'Political Science',
                'Environmental Science',
                'Art History',
                'Literature',
                'Architecture',
                'Music',
                'Education',
                'Medicine',
                'Business',
            ],

            'daily-english' => [
                'Greetings',
                'Family',
                'Friends',
                'Daily Routine',
                'Food and Drinks',
                'Restaurant',
                'Shopping',
                'Clothes',
                'Weather',
                'Transportation',
                'Travel',
                'Hotel',
                'Airport',
                'Health',
                'Sports',
                'Hobbies',
                'Movies',
                'Music',
                'Technology',
                'Work',
                'School',
                'Home',
                'Pets',
                'Social Media',
                'Money',
            ],
        ];

        foreach ($topicGroups as $categorySlug => $topics) {

            $category = Category::where('slug', $categorySlug)->first();

            if (! $category) {
                continue;
            }

            foreach ($topics as $topic) {

                Topic::firstOrCreate(
                    [
                        'slug' => Str::slug($topic),
                    ],
                    [
                        'category_id' => $category->id,
                        'name' => $topic,
                    ]
                );
            }
        }
    }
}