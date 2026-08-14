<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\StoryPoint;
use Illuminate\Database\Seeder;

class StorySeeder extends Seeder
{
    public function run()
    {
        // ----- checklist points -----
        $points = [
            'Sourced direct from Sreemangal growers — fair prices, no brokers',
            'Zero pesticides, zero colour, zero artificial flavour',
            'Small-batch roasted and sealed within 48 hours of plucking',
        ];
        foreach ($points as $i => $text) {
            StoryPoint::firstOrCreate(['text' => $text], ['is_published' => true, 'sort_order' => $i]);
        }

        // ----- section copy (settings group: story) -----
        $copy = [
            'eyebrow' => 'Our Story',
            'title' => "Five Years in the\nGardens, One Name",
            'body1' => 'Cha Kunjo started in 2021 in the emerald valleys of Sreemangal — the tea capital. For five years we have worked alongside the growers here, learning which slope gives the sweetest leaf and which morning gives the best pluck.',
            'body2' => 'Now we pack that knowledge under our own name. No warehouse middlemen, no year-old stock — just tea that goes from garden to pouch while it is still *alive*.',
            'badge_year' => '2021',
            'cta_label' => 'Discover Our Teas',
        ];
        foreach ($copy as $key => $value) {
            Setting::firstOrCreate(
                ['key' => "story.{$key}"],
                ['group' => 'story', 'value' => $value, 'type' => 'string']
            );
        }
    }
}
