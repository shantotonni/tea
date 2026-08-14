<?php

namespace Database\Seeders;

use App\Models\Review;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    public function run()
    {
        $reviews = [
            ['lang' => 'bn', 'name' => 'তৌহিদুর রহমান', 'city' => 'ধানমন্ডি, ঢাকা', 'product' => 'Signature Gold', 'text' => 'শ্রীমঙ্গলের আসল ফ্রেশ চায়ের ফ্লেভার পাচ্ছিলাম না দীর্ঘদিন। Cha Kunjo এর Signature Gold খাওয়ার পর সত্যি অবাক হয়েছি — লিকারের রঙ ও অ্যারোমা একদম অন্য লেভেলের!'],
            ['lang' => 'en', 'name' => 'Nusrat A.', 'city' => 'Gulshan, Dhaka', 'product' => 'Highland Green', 'text' => 'The Highland Green transformed my morning ritual. Fresh, clean, and utterly addictive. Authentic Sreemangal leaves!'],
            ['lang' => 'bn', 'name' => 'ফারজানা আক্তার', 'city' => 'মিরপুর, ঢাকা', 'product' => 'Highland Green', 'text' => 'প্যাকেজিং আর চায়ের কোয়ালিটি দেখে প্রিমিয়াম ব্র্যান্ড মনে হলো। সকালে Highland Green চা টা খেলে একদম মন চাঙ্গা হয়ে যায়!'],
            ['lang' => 'en', 'name' => 'Rafiq H.', 'city' => 'Agrabad, Chittagong', 'product' => 'Royal Black', 'text' => 'You can taste the care in every sip. Fast cash on delivery and the pouch seal preserves the aroma so well. Never buying supermarket tea again.'],
            ['lang' => 'bn', 'name' => 'আরিফুল ইসলাম', 'city' => 'জিইসি, চট্টগ্রাম', 'product' => 'Royal Black', 'text' => 'আগে দোকান থেকে সাধারণ প্যাকেট চা কিনতাম, কিন্তু এই চায়ের সতেজ গন্ধ আর খাঁটি স্বাদ মুখে লেগে থাকে। ডেলিভারিও মাত্র ২ দিনে পেয়েছি।'],
            ['lang' => 'bn', 'name' => 'সাদিয়া ইসলাম', 'city' => 'জিন্দাবাজার, সিলেট', 'product' => 'Masala Chai', 'text' => 'চা কুঞ্জের Masala Chai এক কথায় অসাধারণ! মশলার পারফেক্ট ব্লেন্ড আর গাঢ় লিকার — শীতের সন্ধ্যায় এর চেয়ে ভালো কিছু হয় না।'],
        ];

        foreach ($reviews as $i => $r) {
            Review::firstOrCreate(
                ['name' => $r['name'], 'text' => $r['text']],
                array_merge($r, ['rating' => 5, 'verified' => true, 'is_published' => true, 'sort_order' => $i])
            );
        }
    }
}
