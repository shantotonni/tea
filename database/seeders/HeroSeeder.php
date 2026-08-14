<?php

namespace Database\Seeders;

use App\Models\HeroFeature;
use App\Models\HeroSlide;
use App\Models\HeroStat;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class HeroSeeder extends Seeder
{
    public function run()
    {
        // ----- slides -----
        $slides = ['/images/slider/1.jpeg', '/images/slider/2.jpeg'];
        foreach ($slides as $i => $img) {
            HeroSlide::firstOrCreate(['image' => $img], ['is_published' => true, 'sort_order' => $i]);
        }

        // ----- features -----
        $features = [
            ['🍃', '100% Pure Leaf'],
            ['🚚', 'Home Delivery'],
            ['💵', 'Cash on Delivery'],
            ['🌱', 'No Additives'],
        ];
        foreach ($features as $i => [$icon, $label]) {
            HeroFeature::firstOrCreate(['label' => $label], ['icon' => $icon, 'is_published' => true, 'sort_order' => $i]);
        }

        // ----- stats -----
        $stats = [
            ['5', 'Years in Tea'],
            ['100%', 'Pure Leaf'],
            ['7', 'Signature Blends'],
            ['48h', 'Garden to Pack'],
        ];
        foreach ($stats as $i => [$value, $label]) {
            HeroStat::firstOrCreate(['label' => $label], ['value' => $value, 'is_published' => true, 'sort_order' => $i]);
        }

        // ----- copy (settings group: hero) -----
        $copy = [
            'eyebrow' => 'Single-Origin · Hand-Plucked · Since 2021',
            'title' => 'Taste the Mist of Sreemangal Hills',
            'title_accent' => 'Sreemangal Hills',
            'subtitle' => 'Five years sourcing straight from the highland gardens of Sreemangal — now packed under our own name. Hand-plucked, roasted in small batches, sealed within days of harvest. Fresh tea, no middlemen, no compromise.',
            'cta_primary_label' => '🍃 Shop Signature Teas',
            'cta_primary_target' => 'collection',
            'cta_ghost_label' => '📖 Discover Our Garden Story',
            'cta_ghost_target' => 'story',
        ];
        foreach ($copy as $key => $value) {
            Setting::updateOrCreate(
                ['key' => "hero.{$key}"],
                ['group' => 'hero', 'value' => $value, 'type' => 'string']
            );
        }
    }
}
