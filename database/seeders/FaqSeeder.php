<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run()
    {
        $faqs = [
            ['question' => 'How long does delivery take?', 'answer' => 'Orders placed before 4pm ship the same day. Inside Dhaka you get it in 1–2 days, outside Dhaka 2–4 days. We send a tracking SMS the moment the parcel leaves Sreemangal.'],
            ['question' => 'Is cash on delivery available?', 'answer' => 'Yes, everywhere in Bangladesh. Pay the courier when the parcel reaches you — open it, smell it, then pay. Card and mobile banking work too.'],
            ['question' => 'What does delivery cost?', 'answer' => 'Inside Dhaka ৳60, outside Dhaka ৳120. Free anywhere on orders above ৳2,000.'],
            ['question' => 'How fresh is the tea really?', 'answer' => 'Every pouch carries its pack date. Leaf goes from plucking to sealed pouch within 48 hours, nitrogen-flushed so the aroma survives the journey. If it does not smell alive when you open it, we replace it within 7 days.'],
            ['question' => 'How should I store it?', 'answer' => 'Keep the pouch sealed, away from light, heat and anything strong-smelling — spices and tea share air fast. No need to refrigerate. Sealed, it holds for 18 months.'],
            ['question' => 'Which blend should I buy first?', 'answer' => 'If you take tea with milk, start with Kunjo Royal Black. Plain drinkers should start with Highland Green. Or answer three quick questions in our blend finder above and we will pick for you.'],
        ];

        foreach ($faqs as $i => $f) {
            Faq::firstOrCreate(
                ['question' => $f['question']],
                array_merge($f, ['is_published' => true, 'sort_order' => $i])
            );
        }
    }
}
