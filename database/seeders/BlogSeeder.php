<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use Illuminate\Database\Seeder;

class BlogSeeder extends Seeder
{
    public function run()
    {
        $posts = [
            [
                'category' => 'brewing',
                'title' => 'The Secret to Brewing First-Flush Sreemangal Gold Tea',
                'title_bn' => 'সঠিক নিয়মে চা তৈরির জাদুকরী উপায় ও তাপমাত্রা',
                'excerpt' => 'Discover why water temperature at 85°C and 3-minute steeping preserves EGCG catechins and natural aroma without bitterness.',
                'image' => '/images/garden.jpg',
                'author' => 'Shojibul Islam',
                'role' => 'Co-Founder',
                'read_time' => '4 min read',
                'is_featured' => true,
                'published_at' => '2026-07-24',
                'sort_order' => 0,
            ],
            [
                'category' => 'health',
                'title' => 'L-Theanine & Focus: How Whole Leaf Tea Calms the Mind',
                'title_bn' => 'চা পাতা কেন মানসিক ক্লান্তি দূর করে মনকে সতেজ রাখে',
                'excerpt' => 'Scientific research on how L-Theanine creates smooth alpha brain waves and sustained mental clarity without caffeine jitters.',
                'image' => '/images/leaves.jpg',
                'author' => 'Cha Kunjo Wellness Team',
                'role' => 'Health Journal',
                'read_time' => '5 min read',
                'is_featured' => false,
                'published_at' => '2026-07-18',
                'sort_order' => 1,
            ],
            [
                'category' => 'garden',
                'title' => 'Why Sreemangal Soil Produces Bangladesh’s Finest Tea',
                'title_bn' => 'শ্রীমঙ্গলের লাল মাটি ও পাহাড়ি আবহাওয়ার প্রাকৃতিক রূপকথা',
                'excerpt' => 'Exploring the 900m high-altitude tea slopes of Sylhet and how morning mist shapes the golden leaf flavor profile.',
                'image' => '/images/feature.jpg',
                'author' => 'Humaon Kabir',
                'role' => 'Co-Founder',
                'read_time' => '6 min read',
                'is_featured' => false,
                'published_at' => '2026-07-10',
                'sort_order' => 2,
            ],
        ];

        foreach ($posts as $p) {
            BlogPost::firstOrCreate(['title' => $p['title']], $p);
        }
    }
}
