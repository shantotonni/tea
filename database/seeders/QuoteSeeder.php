<?php

namespace Database\Seeders;

use App\Models\Quote;
use Illuminate\Database\Seeder;

class QuoteSeeder extends Seeder
{
    public function run()
    {
        $quotes = [
            // wisdom
            ['wisdom', 'Teaism is a religion of the art of life. There is something in the nature of tea that leads us into a world of quiet contemplation.', 'Okakura Kakuzo', 'Author of "The Book of Tea" (1906)', 0],
            ['wisdom', 'চা হলো এমন এক নীরব সুধা যা মানুষের আত্মাকে শান্ত করে এবং চিন্তাকে গভীরতর করে।', 'Rabindranath Tagore (রবীন্দ্রনাথ ঠাকুর)', 'Nobel Laureate Poet & Philosopher', 1],
            ['wisdom', 'Tea tempers the spirit, harmonizes the mind, dispels fatigue and awakens the soul.', 'Lu Yu (陸羽)', 'The Sage of Tea (780 AD, Classic of Tea)', 2],
            ['wisdom', 'You can never get a cup of tea large enough or a book long enough to suit me.', 'C.S. Lewis', 'Renowned Author & Scholar', 3],
            ['wisdom', 'Drink your tea slowly and reverently, as if it is the axis on which the world revolves.', 'Thich Nhat Hanh', 'Zen Master & Author', 4],
            // health
            ['health', 'Pure unadulterated green & black tea leaves are packed with EGCG catechins and polyphenols that boost cellular immunity and protect vascular health.', 'Dr. William Li, MD', 'Physician, Scientist & Author of "Eat to Beat Disease"', 0],
            ['health', 'The natural synergy of L-theanine and caffeine in whole leaf tea promotes alpha brain waves, creating sustained calm focus without stress or jitters.', 'Dr. Andrew Huberman, PhD', 'Neuroscientist & Professor, Stanford University', 1],
            ['health', 'Drinking fresh, high-altitude single-origin tea daily improves gut microbiome diversity, lowers stress hormones, and promotes healthy longevity.', 'Dr. Mehmet Oz, MD', 'Cardiothoracic Surgeon & Health Advocate', 2],
            ['health', 'Tea is one of nature’s richest sources of protective antioxidants. Daily consumption supports metabolic health, cellular repair, and vitality.', 'Dr. Andrew Weil, MD', 'Integrative Medicine Pioneer, Harvard University', 3],
        ];

        foreach ($quotes as [$tab, $text, $author, $title, $sort]) {
            Quote::firstOrCreate(
                ['tab' => $tab, 'author' => $author],
                ['text' => $text, 'title' => $title, 'is_published' => true, 'sort_order' => $sort]
            );
        }
    }
}
