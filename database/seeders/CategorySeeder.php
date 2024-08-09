<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;


class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $categories = [
            ['name' => 'Birthday'],
            ['name' => 'Pool Party'],
            ['name' => 'Wedding'],
            ['name' => 'Conference'],
            ['name' => 'Concert'],
            ['name' => 'Seminar'],
            ['name' => 'Workshop'],
            ['name' => 'Webinar'],
            ['name' => 'Exhibition'],
            ['name' => 'Festival'],
            ['name' => 'Sports Event'],
            ['name' => 'Corporate Event'],
            ['name' => 'Charity Event'],
            ['name' => 'Networking Event'],
            ['name' => 'Award Ceremony'],
            ['name' => 'Fashion Show'],
            ['name' => 'Trade Show'],
            ['name' => 'Product Launch'],
            ['name' => 'Art Show'],
            ['name' => 'Theater'],
            ['name' => 'Fundraiser'],
            ['name' => 'Holiday Party'],
            ['name' => 'Music Festival'],
            ['name' => 'Book Launch'],
            ['name' => 'Science Fair'],
            ['name' => 'Tech Meetup'],
            ['name' => 'Startup Pitch'],
            ['name' => 'Family Reunion'],
            ['name' => 'Religious Ceremony'],
            ['name' => 'School Event'],
            ['name' => 'Community Event'],
            ['name' => 'Food Festival'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
