<?php

namespace Database\Seeders;

use App\Models\FooterLink;
use App\Models\NavLink;
use App\Models\Setting;
use App\Models\SocialLink;
use Illuminate\Database\Seeder;

class LayoutSeeder extends Seeder
{
    public function run()
    {
        // ----- navbar links -----
        $nav = [
            ['Home', 'home', false],
            ['Our Story', 'story', false],
            ['Best Seller', 'bestseller', false],
            ['Collection', 'collection', false],
            ['Process', 'process', false],
            ['Reviews', 'reviews', false],
            ['Journal', 'journal', false],
            ['Shop Now', 'collection', true],
        ];
        foreach ($nav as $i => [$label, $target, $isCta]) {
            NavLink::firstOrCreate(
                ['label' => $label, 'target' => $target],
                ['is_cta' => $isCta, 'is_published' => true, 'sort_order' => $i]
            );
        }

        // ----- footer link columns -----
        $footer = [
            ['explore', 'Our Story', 'story'],
            ['explore', 'Collection', 'collection'],
            ['explore', 'Process', 'process'],
            ['explore', 'Reviews', 'reviews'],
            ['support', 'Shipping', '#'],
            ['support', 'Returns', '#'],
            ['support', 'Brewing Guide', '#'],
            ['support', 'FAQ', '#'],
            ['contact', 'Sreemangal, Moulvibazar', ''],
            ['contact', 'chakunjo@gmail.com', ''],
            ['contact', '+880 1XXX-XXXXXX', ''],
        ];
        foreach ($footer as $i => [$col, $label, $target]) {
            FooterLink::firstOrCreate(
                ['col' => $col, 'label' => $label],
                ['target' => $target, 'is_published' => true, 'sort_order' => $i]
            );
        }

        // ----- social links -----
        $socials = ['Facebook', 'Instagram', 'YouTube', 'TikTok', 'WhatsApp'];
        foreach ($socials as $i => $name) {
            SocialLink::firstOrCreate(
                ['name' => $name],
                ['href' => '#', 'is_published' => true, 'sort_order' => $i]
            );
        }

        // ----- footer copy (settings group: footer) -----
        $footerCopy = [
            'about' => 'Single-origin tea from the misty hills of Sreemangal. Five years in the gardens, packed under our own name since 2021.',
            'copyright' => '© 2026 Cha Kunjo. Crafted with care.',
            'bottom_note' => 'Privacy · Terms',
        ];
        foreach ($footerCopy as $key => $value) {
            Setting::firstOrCreate(
                ['key' => "footer.{$key}"],
                ['group' => 'footer', 'value' => $value, 'type' => 'string']
            );
        }

        // ----- SEO (settings group: seo) -----
        $seo = [
            'title' => 'Cha Kunjo — Premium Single-Origin Tea from the Hills',
            'description' => 'Hand-plucked single-origin tea from the misty hills of Sreemangal. Five years in the gardens, sealed within 48 hours of harvest — Cha Kunjo.',
            'keywords' => 'tea, single-origin tea, Sreemangal tea, green tea, black tea, Bangladesh tea, Cha Kunjo',
            'og_title' => 'Cha Kunjo — Premium Single-Origin Tea',
            'og_description' => 'Hand-plucked tea from Sreemangal, sealed within 48 hours of harvest.',
            'og_image' => '/images/slider/1.jpeg',
        ];
        foreach ($seo as $key => $value) {
            Setting::firstOrCreate(
                ['key' => "seo.{$key}"],
                ['group' => 'seo', 'value' => $value, 'type' => 'string']
            );
        }
    }
}
