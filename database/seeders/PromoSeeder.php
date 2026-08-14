<?php

namespace Database\Seeders;

use App\Models\PromoCode;
use Illuminate\Database\Seeder;

class PromoSeeder extends Seeder
{
    public function run()
    {
        $codes = [
            ['code' => 'KUNJO10', 'description' => '10% off your order', 'type' => 'percent', 'value' => 10, 'min_subtotal' => 0, 'max_discount' => 300],
            ['code' => 'FIRST50', 'description' => '৳50 off your first order', 'type' => 'fixed', 'value' => 50, 'min_subtotal' => 500],
            ['code' => 'WELCOME15', 'description' => '15% off above ৳1,500', 'type' => 'percent', 'value' => 15, 'min_subtotal' => 1500, 'max_discount' => 500],
        ];
        foreach ($codes as $c) {
            PromoCode::firstOrCreate(['code' => $c['code']], $c + ['is_active' => true]);
        }
    }
}
