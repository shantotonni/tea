<?php

namespace Database\Seeders;

use App\Models\BlendOption;
use App\Models\BlendQuestion;
use Illuminate\Database\Seeder;

class BlendSeeder extends Seeder
{
    public function run()
    {
        $questions = [
            [
                'key' => 'time', 'label' => 'When do you reach for tea?', 'sort' => 0,
                'options' => [
                    ['morning', 'Morning', 'to wake up properly', '🌅'],
                    ['afternoon', 'Afternoon', 'the 4 o’clock cup', '☀️'],
                    ['evening', 'Evening', 'to slow down', '🌙'],
                ],
            ],
            [
                'key' => 'style', 'label' => 'How do you take it?', 'sort' => 1,
                'options' => [
                    ['plain', 'Plain', 'no milk, no sugar', '🍵'],
                    ['milk', 'With milk', 'doodh cha', '🥛'],
                    ['spice', 'With spice', 'elach, adha, daruchini', '🌶'],
                ],
            ],
            [
                'key' => 'strength', 'label' => 'How strong?', 'sort' => 2,
                'options' => [
                    ['light', 'Light', 'delicate and floral', '🍃'],
                    ['balanced', 'Balanced', 'everyday cup', '⚖️'],
                    ['bold', 'Bold', 'wakes the whole house', '🔥'],
                ],
            ],
        ];

        foreach ($questions as $q) {
            $question = BlendQuestion::firstOrCreate(
                ['key' => $q['key']],
                ['label' => $q['label'], 'is_published' => true, 'sort_order' => $q['sort']]
            );
            foreach ($q['options'] as $i => [$optId, $title, $hint, $icon]) {
                BlendOption::firstOrCreate(
                    ['question_id' => $question->id, 'opt_id' => $optId],
                    ['title' => $title, 'hint' => $hint, 'icon' => $icon, 'sort_order' => $i]
                );
            }
        }
    }
}
