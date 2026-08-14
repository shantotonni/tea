<?php

namespace App\Services;

use App\Models\Product;

class PromoService
{
    /**
     * Turn client cart lines ([{id: slug, qty}]) into a trusted context for
     * PromoCode::evaluate — subtotal and per-line totals come from DB prices.
     *
     * @return array ['subtotal' => int, 'items' => [['slug','category','line_total'], ...]]
     */
    public static function buildCart(array $rows): array
    {
        $slugs = array_values(array_filter(array_map(fn ($r) => $r['id'] ?? null, $rows)));
        $products = Product::whereIn('slug', $slugs)->get()->keyBy('slug');

        $subtotal = 0;
        $items = [];
        foreach ($rows as $r) {
            $p = $products->get($r['id'] ?? null);
            if (! $p) {
                continue;
            }
            $qty = max(1, (int) ($r['qty'] ?? 1));
            $lineTotal = $p->price * $qty;
            $subtotal += $lineTotal;
            $items[] = [
                'slug' => $p->slug,
                'category' => $p->category,
                'line_total' => $lineTotal,
            ];
        }

        return ['subtotal' => $subtotal, 'items' => $items];
    }
}
