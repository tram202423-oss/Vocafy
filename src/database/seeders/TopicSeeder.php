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
        $animals = Category::where('slug', 'animals')->first();

        Topic::firstOrCreate(
            ['slug' => 'mammals'],
            [
                'category_id' => $animals->id,
                'name' => 'Mammals',
            ]
        );

        Topic::firstOrCreate(
            ['slug' => 'birds'],
            [
                'category_id' => $animals->id,
                'name' => 'Birds',
            ]
        );

        $food = Category::where('slug', 'food')->first();

        Topic::firstOrCreate(
            ['slug' => 'fruits'],
            [
                'category_id' => $food->id,
                'name' => 'Fruits',
            ]
        );

        Topic::firstOrCreate(
            ['slug' => 'vegetables'],
            [
                'category_id' => $food->id,
                'name' => 'Vegetables',
            ]
        );
    }
}