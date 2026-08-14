<?php

namespace Database\Seeders;

use App\Models\ProcessStep;
use Illuminate\Database\Seeder;

class ProcessSeeder extends Seeder
{
    public function run()
    {
        $steps = [
            ['num' => '01', 'title' => 'Hand Plucking', 'text' => 'Only the top two leaves and a bud, picked at dawn by skilled hands.'],
            ['num' => '02', 'title' => 'Withering', 'text' => 'Leaves rest and soften, releasing their natural aromas slowly.'],
            ['num' => '03', 'title' => 'Rolling & Oxidation', 'text' => 'Careful rolling shapes each leaf and unlocks its signature character.'],
            ['num' => '04', 'title' => 'Small-Batch Drying', 'text' => 'Gently fired to lock in freshness, then sealed straight from the estate.'],
        ];

        foreach ($steps as $i => $s) {
            ProcessStep::firstOrCreate(
                ['num' => $s['num']],
                array_merge($s, ['is_published' => true, 'sort_order' => $i])
            );
        }
    }
}
