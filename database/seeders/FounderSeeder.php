<?php

namespace Database\Seeders;

use App\Models\Founder;
use App\Models\FounderPoint;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class FounderSeeder extends Seeder
{
    public function run()
    {
        // ----- founders -----
        $founders = [
            ['name' => 'Shojibul Islam', 'role' => 'Co-Founder · Cha Kunjo', 'initials' => 'SI', 'sort_order' => 0],
            ['name' => 'Humaon Kabir', 'role' => 'Co-Founder · Cha Kunjo', 'initials' => 'HK', 'sort_order' => 1],
        ];
        foreach ($founders as $f) {
            Founder::firstOrCreate(['name' => $f['name']], $f + ['is_published' => true]);
        }

        // ----- story points -----
        $points = [
            ['num' => '01', 'title' => 'We buy direct from growers', 'text' => 'No auction floor, no broker margin. The farmer is paid direct.', 'sort_order' => 0],
            ['num' => '02', 'title' => 'We pack within 48 hours', 'text' => 'Nitrogen-flushed the same week it is plucked, never warehoused for months.', 'sort_order' => 1],
            ['num' => '03', 'title' => 'We keep batches small', 'text' => 'Forty kilos at a time, so one person can watch every minute of the roast.', 'sort_order' => 2],
        ];
        foreach ($points as $p) {
            FounderPoint::firstOrCreate(['num' => $p['num']], $p + ['is_published' => true]);
        }

        // ----- section copy (settings group: founder) -----
        $copy = [
            'eyebrow' => 'Behind the pouch',
            'title' => 'Why we started Cha Kunjo',
            'quote' => "For five years we bought leaf for other people's brands. The best lots never reached the shelf — they were blended away. In 2021 we decided to pack them under our own name instead, and sell them fresh enough that you can smell the garden.",
            'badge' => 'Cha Kunjo Co-Founders',
        ];
        foreach ($copy as $key => $value) {
            Setting::firstOrCreate(
                ['key' => "founder.{$key}"],
                ['group' => 'founder', 'value' => $value, 'type' => 'string']
            );
        }
    }
}
