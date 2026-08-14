<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // ----- admin login -----
        User::updateOrCreate(
            ['email' => 'chakunjo@gmail.com'],
            [
                'name' => 'Shojibul Islam',
                'role' => 'Estate Administrator',
                'password' => Hash::make('admin123'),
            ]
        );

        // ----- products -----
        $products = [
            ['slug' => 'highland-green', 'name' => 'Kunjo Highland Green', 'sku' => 'CK-GRN-250', 'category' => 'Green Tea', 'blurb' => 'Grassy, bright, delicately sweet. A clean morning ritual.', 'image' => 'images/green.jpg', 'tag' => 'Popular', 'weight' => '250g', 'price' => 480, 'old_price' => 560, 'stock' => 128, 'reviews' => 46, 'status' => 'Active'],
            ['slug' => 'royal-black', 'name' => 'Kunjo Royal Black', 'sku' => 'CK-BLK-250', 'category' => 'Black Tea', 'blurb' => 'Bold, malty, full-bodied. The classic that never fades.', 'image' => 'images/feature.jpg', 'tag' => 'Premium', 'weight' => '250g', 'price' => 550, 'old_price' => 650, 'stock' => 86, 'reviews' => 72, 'status' => 'Active'],
            ['slug' => 'silver-white', 'name' => 'Kunjo Silver White', 'sku' => 'CK-WHT-100', 'category' => 'White Tea', 'blurb' => 'Whisper-soft, floral, rare. Plucked only in early spring.', 'image' => 'images/white.jpg', 'tag' => 'Limited', 'weight' => '100g', 'price' => 720, 'old_price' => 850, 'stock' => 9, 'reviews' => 21, 'status' => 'Low stock'],
            ['slug' => 'masala-chai', 'name' => 'Kunjo Masala Chai', 'sku' => 'CK-CHA-250', 'category' => 'Spiced Blend', 'blurb' => 'Warming spice, deep comfort. Home in a cup.', 'image' => 'images/chai.jpg', 'tag' => 'Spiced', 'weight' => '250g', 'price' => 500, 'old_price' => 600, 'stock' => 204, 'reviews' => 58, 'status' => 'Active'],
            ['slug' => 'kunjo-oolong-reserve', 'name' => 'Kunjo Oolong Reserve', 'sku' => 'CK-OOL-150', 'category' => 'Oolong', 'blurb' => 'Roasted, complex, layered. For the patient drinker.', 'image' => 'images/leaves.jpg', 'tag' => 'Reserve', 'weight' => '150g', 'price' => 890, 'old_price' => 990, 'stock' => 0, 'reviews' => 12, 'status' => 'Out of stock'],
            ['slug' => 'kunjo-signature-gold', 'name' => 'Kunjo Signature Gold', 'sku' => 'CK-SIG-200', 'category' => 'House Reserve', 'blurb' => 'Our house reserve — golden tips, honeyed finish.', 'image' => 'images/pouch-gold.jpeg', 'tag' => 'Signature', 'weight' => '200g', 'price' => 950, 'old_price' => 1150, 'stock' => 74, 'reviews' => 39, 'status' => 'Active'],
            ['slug' => 'kunjo-classic-green', 'name' => 'Kunjo Classic Green', 'sku' => 'CK-CLS-200', 'category' => 'Classic Blend', 'blurb' => 'The everyday Cha Kunjo cup — smooth, bright, endlessly drinkable.', 'image' => 'images/pouch-green.jpeg', 'tag' => 'New', 'weight' => '200g', 'price' => 620, 'old_price' => 740, 'stock' => 162, 'reviews' => 17, 'status' => 'Active'],
        ];

        // per-product rich detail (rating, flags, gallery, tasting, facts)
        $extras = [
            'highland-green'       => ['rating' => 4.8, 'tasting' => [5, 4, 3, 2], 'facts' => ['Top two leaves + bud', '900m+', 'First Flush 2026', 'Sreemangal']],
            'royal-black'          => ['rating' => 4.9, 'in_gift_box' => true, 'tasting' => [5, 4, 2, 3], 'facts' => ['Whole leaf, orthodox', '850m+', 'Second Flush 2026', 'Sreemangal']],
            'silver-white'         => ['rating' => 5.0, 'tasting' => [2, 5, 4, 1], 'facts' => ['Silver tips only', '1,100m+', 'Early spring 2026', 'Sreemangal']],
            'masala-chai'          => ['rating' => 4.7, 'in_gift_box' => true, 'tasting' => [5, 5, 3, 2], 'facts' => ['Black tea + spices', '800m+', 'Blended in-house', 'Sreemangal']],
            'kunjo-oolong-reserve' => ['rating' => 4.8, 'tasting' => [4, 4, 3, 3], 'facts' => ['Semi-oxidised', '950m+', 'First Flush 2026', 'Sreemangal']],
            'kunjo-signature-gold' => ['rating' => 4.9, 'is_featured' => true, 'in_gift_box' => true, 'tasting' => [4, 5, 3, 2], 'facts' => ['Golden Tips', '900–1,100m', 'First Flush 2026', 'Sreemangal']],
            'kunjo-classic-green'  => ['rating' => 4.6, 'tasting' => [3, 4, 4, 2], 'facts' => ['Whole leaf', '900m+', 'First Flush 2026', 'Sreemangal']],
        ];

        foreach ($products as $p) {
            $x = $extras[$p['slug']] ?? [];
            [$s, $a, $sw, $as] = $x['tasting'] ?? [4, 4, 3, 2];
            [$leaf, $elev, $harvest, $origin] = $x['facts'] ?? ['Whole leaf', '900m+', 'First Flush 2026', 'Sreemangal'];

            $p['rating'] = $x['rating'] ?? 5.0;
            $p['is_featured'] = $x['is_featured'] ?? false;
            $p['in_gift_box'] = $x['in_gift_box'] ?? false;
            $p['details'] = [
                'gallery' => ['/' . ltrim($p['image'], '/'), '/images/pouch-gold.jpeg', '/images/chai.jpg', '/images/leaves.jpg'],
                'tasting' => ['Strength' => $s, 'Aroma' => $a, 'Sweetness' => $sw, 'Astringency' => $as],
                'facts' => ['Leaf grade' => $leaf, 'Elevation' => $elev, 'Harvest' => $harvest, 'Origin' => $origin],
            ];

            Product::updateOrCreate(['slug' => $p['slug']], $p);
        }

        // ----- customers -----
        $customers = [
            ['name' => 'Nusrat Ahmed', 'email' => 'nusrat@mail.com', 'city' => 'Dhaka', 'orders_count' => 14, 'spent' => 18420, 'tier' => 'Gold'],
            ['name' => 'Rafiq Hasan', 'email' => 'rafiq.h@mail.com', 'city' => 'Chittagong', 'orders_count' => 9, 'spent' => 11250, 'tier' => 'Silver'],
            ['name' => 'Tania Rahman', 'email' => 'tania.r@mail.com', 'city' => 'Sylhet', 'orders_count' => 21, 'spent' => 29870, 'tier' => 'Gold'],
            ['name' => 'Imran Kabir', 'email' => 'imran.k@mail.com', 'city' => 'Khulna', 'orders_count' => 4, 'spent' => 4380, 'tier' => 'Bronze'],
            ['name' => 'Sadia Noor', 'email' => 'sadia@mail.com', 'city' => 'Rajshahi', 'orders_count' => 7, 'spent' => 8110, 'tier' => 'Silver'],
            ['name' => 'Mitu Islam', 'email' => 'mitu@mail.com', 'city' => 'Dhaka', 'orders_count' => 17, 'spent' => 22940, 'tier' => 'Gold'],
        ];
        foreach ($customers as $c) {
            Customer::updateOrCreate(['email' => $c['email']], $c);
        }

        // ----- orders -----
        $orders = [
            ['code' => '#CK-2841', 'customer_email' => 'nusrat@mail.com', 'customer_name' => 'Nusrat Ahmed', 'items_count' => 3, 'total' => 1780, 'status' => 'Delivered', 'channel' => 'Website'],
            ['code' => '#CK-2840', 'customer_email' => 'rafiq.h@mail.com', 'customer_name' => 'Rafiq Hasan', 'items_count' => 1, 'total' => 550, 'status' => 'Shipped', 'channel' => 'Website'],
            ['code' => '#CK-2839', 'customer_email' => 'tania.r@mail.com', 'customer_name' => 'Tania Rahman', 'items_count' => 5, 'total' => 3140, 'status' => 'Pending', 'channel' => 'Phone'],
            ['code' => '#CK-2838', 'customer_email' => 'imran.k@mail.com', 'customer_name' => 'Imran Kabir', 'items_count' => 2, 'total' => 1220, 'status' => 'Delivered', 'channel' => 'Website'],
            ['code' => '#CK-2837', 'customer_email' => 'sadia@mail.com', 'customer_name' => 'Sadia Noor', 'items_count' => 4, 'total' => 2380, 'status' => 'Cancelled', 'channel' => 'Website'],
            ['code' => '#CK-2836', 'customer_email' => 'nusrat@mail.com', 'customer_name' => 'Arif Chowdhury', 'items_count' => 2, 'total' => 1030, 'status' => 'Shipped', 'channel' => 'Facebook'],
            ['code' => '#CK-2835', 'customer_email' => 'mitu@mail.com', 'customer_name' => 'Mitu Islam', 'items_count' => 6, 'total' => 3960, 'status' => 'Delivered', 'channel' => 'Website'],
            ['code' => '#CK-2834', 'customer_email' => 'tania.r@mail.com', 'customer_name' => 'Jahid Hossain', 'items_count' => 1, 'total' => 720, 'status' => 'Pending', 'channel' => 'Phone'],
        ];
        foreach ($orders as $o) {
            $customer = Customer::where('email', $o['customer_email'])->first();
            Order::updateOrCreate(
                ['code' => $o['code']],
                array_merge($o, ['customer_id' => $customer->id ?? null])
            );
        }

        // ----- store profile settings -----
        $store = [
            'name' => 'Cha Kunjo',
            'email' => 'chakunjo@gmail.com',
            'phone' => '+880 1XXX-XXXXXX',
            'currency' => 'BDT — Bangladeshi Taka (৳)',
            'address' => 'Sreemangal, Moulvibazar, Sylhet',
            'description' => 'Premium single-origin tea from the misty hills of Sreemangal. Five years in the gardens, packed under our own name since 2021.',
        ];
        foreach ($store as $key => $value) {
            Setting::firstOrCreate(
                ['key' => "store.{$key}"],
                ['group' => 'store', 'value' => $value, 'type' => 'string']
            );
        }

        // notifications (bool)
        $notifications = ['new_order' => true, 'low_stock' => true, 'daily_digest' => false, 'new_review' => true];
        foreach ($notifications as $key => $value) {
            Setting::firstOrCreate(
                ['key' => "notifications.{$key}"],
                ['group' => 'notifications', 'value' => $value ? '1' : '0', 'type' => 'bool']
            );
        }

        // shipping — matches the storefront (Dhaka ৳60, outside ৳120, free above ৳2,000)
        $shipping = [
            ['inside_dhaka', '60', 'int'],
            ['outside_dhaka', '120', 'int'],
            ['free_above', '2000', 'int'],
            ['courier', 'Steadfast', 'string'],
            ['note', 'Delivered within 2–4 working days. Cash on delivery available.', 'string'],
        ];
        foreach ($shipping as [$key, $value, $type]) {
            Setting::firstOrCreate(
                ['key' => "shipping.{$key}"],
                ['group' => 'shipping', 'value' => $value, 'type' => $type]
            );
        }

        // security (bool)
        $security = ['two_factor' => false, 'login_alerts' => true];
        foreach ($security as $key => $value) {
            Setting::firstOrCreate(
                ['key' => "security.{$key}"],
                ['group' => 'security', 'value' => $value ? '1' : '0', 'type' => 'bool']
            );
        }

        $this->call([
            ReviewSeeder::class,
            FaqSeeder::class,
            BlogSeeder::class,
            ProcessSeeder::class,
            QuoteSeeder::class,
            FounderSeeder::class,
            HeroSeeder::class,
            MarqueeSeeder::class,
            StorySeeder::class,
            BlendSeeder::class,
            LayoutSeeder::class,
            SectionsSeeder::class,
            PromoSeeder::class,
            DistrictSeeder::class,
        ]);
    }
}
