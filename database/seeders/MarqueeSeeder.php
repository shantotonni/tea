<?php

namespace Database\Seeders;

use App\Models\MarqueeItem;
use Illuminate\Database\Seeder;

class MarqueeSeeder extends Seeder
{
    public function run()
    {
        $items = [
            'Green Tea', 'Black Tea', 'White Tea', 'Oolong',
            'Herbal Infusions', 'Kunjo Masala Chai', 'Golden Tips', 'First Flush',
        ];
        foreach ($items as $i => $label) {
            MarqueeItem::firstOrCreate(['label' => $label], ['is_published' => true, 'sort_order' => $i]);
        }
    }
}
