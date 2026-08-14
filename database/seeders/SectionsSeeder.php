<?php

namespace Database\Seeders;

use App\Models\CollectionNote;
use App\Models\CreationTile;
use App\Models\InstaShot;
use App\Models\PromoBanner;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class SectionsSeeder extends Seeder
{
    public function run()
    {
        // ---- creation collage tiles ----
        $tiles = [
            ['/images/pouch-gold.jpeg', 'Kunjo Signature Gold', 'House reserve · 200g', '/product/kunjo-signature-gold', true],
            ['/images/white.jpg', 'Kunjo Silver White', 'Limited · 100g', '/product/silver-white', false],
            ['/images/chai.jpg', 'Kunjo Masala Chai', 'Spiced blend · 250g', '/product/masala-chai', false],
            ['/images/pouch-green.jpeg', 'Kunjo Classic Green', 'Everyday cup · 200g', '/product/kunjo-classic-green', false],
            ['/images/green.jpg', 'Kunjo Highland Green', 'Bestseller · 250g', '/product/highland-green', false],
        ];
        foreach ($tiles as $i => [$image, $label, $meta, $target, $wide]) {
            CreationTile::firstOrCreate(
                ['label' => $label],
                ['image' => $image, 'meta' => $meta, 'target' => $target, 'is_wide' => $wide, 'is_published' => true, 'sort_order' => $i]
            );
        }

        // ---- promo banners ----
        $promos = [
            ['/images/garden.jpg', '🌿 SINGLE ORIGIN', 'Direct from Sreemangal', "Perfect blend of\nnature & taste", 'Hand-plucked from the misty upper slopes at first light.', '/product/highland-green', 'Explore Sreemangal Gardens'],
            ['/images/pouch-gold.jpeg', '✨ HOUSE RESERVE', 'Cha Kunjo Bestseller', "Handpicked leaves,\nbrewed to perfection", 'Golden tips sealed within 48 hours in our signature pouch.', '/product/kunjo-signature-gold', 'Shop Signature Gold'],
        ];
        foreach ($promos as $i => [$image, $badge, $eyebrow, $title, $text, $target, $cta]) {
            PromoBanner::firstOrCreate(
                ['title' => $title],
                ['image' => $image, 'badge' => $badge, 'eyebrow' => $eyebrow, 'text' => $text, 'target' => $target, 'cta' => $cta, 'is_published' => true, 'sort_order' => $i]
            );
        }

        // ---- instagram shots ----
        $shots = [
            ['/images/pouch-gold.jpeg', 'Signature Gold, sealed this morning', 214],
            ['/images/chai.jpg', 'Adha-elach cha weather', 187],
            ['/images/garden.jpg', 'First flush on the upper slope', 342],
            ['/images/leaves.jpg', 'The 6am pluck', 176],
            ['/images/pouch-green.jpeg', 'Classic Green restocked', 158],
            ['/images/white.jpg', 'Sorting the silver tips', 231],
        ];
        foreach ($shots as $i => [$image, $caption, $likes]) {
            InstaShot::firstOrCreate(
                ['caption' => $caption],
                ['image' => $image, 'likes' => $likes, 'is_published' => true, 'sort_order' => $i]
            );
        }

        // ---- collection notes ----
        $notes = [
            ['🚚', 'Free delivery above ৳2,000'],
            ['💵', 'Cash on delivery'],
            ['🍃', 'Sealed within 48h of plucking'],
        ];
        foreach ($notes as $i => [$icon, $label]) {
            CollectionNote::firstOrCreate(
                ['label' => $label],
                ['icon' => $icon, 'is_published' => true, 'sort_order' => $i]
            );
        }

        // ---- settings copy groups ----
        $groups = [
            'collection' => [
                'eyebrow' => 'Signature Collection',
                'title' => 'Crafted for Every Mood',
                'lead' => 'Each blend is a chapter — pick the one that speaks to your moment.',
            ],
            'creations' => [
                'eyebrow' => 'Our range',
                'title' => "Creations\nwith purpose",
                'lead' => 'Seven blends, each built for a different hour of the day.',
                'stat1_value' => '07', 'stat1_label' => 'signature blends',
                'stat2_value' => '48h', 'stat2_label' => 'garden to pouch',
                'cta_label' => 'Explore the collection',
            ],
            'insta' => [
                'eyebrow' => 'From the garden, daily',
                'handle' => '@chakunjo',
            ],
            'newsletter' => [
                'title' => 'Join the Tea Ritual',
                'lead' => 'Get 10% off your first order and early access to seasonal harvests.',
                'button_label' => 'Subscribe',
                'success_label' => 'Subscribed ✓',
                'fine' => 'No spam. Just good tea, once a month.',
            ],
            'giftbox' => [
                'eyebrow' => 'Gift & Discovery',
                'title' => "The Three-Cup\nDiscovery Box",
                'lead' => "Can't decide? Take all three of our most-ordered blends in one box — enough for roughly 240 cups. Wrapped in kraft paper with a handwritten card, ready to gift.",
                'note' => '🎁 Free gift wrap · 🚚 Free delivery',
                'discount_pct' => '18',
            ],
        ];
        foreach ($groups as $group => $pairs) {
            foreach ($pairs as $key => $value) {
                $type = ($group === 'giftbox' && $key === 'discount_pct') ? 'int' : 'string';
                Setting::firstOrCreate(
                    ['key' => "{$group}.{$key}"],
                    ['group' => $group, 'value' => $value, 'type' => $type]
                );
            }
        }
    }
}
