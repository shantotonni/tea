<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    /**
     * POST /api/public/checkout — place an order.
     * Guest checkout allowed; links the customer when a valid token is sent.
     * Prices, stock, discounts and shipping are ALL computed server-side — the client
     * cart is treated as untrusted input.
     */
    public function store(Request $request)
    {
        // 1. Anti-Bot Honeypot Trap
        if ($request->filled('website') || $request->filled('hp_trap') || $request->filled('company_url')) {
            abort(422, 'Spam request detected.');
        }

        // 2. Strict Input Validation
        $data = $request->validate([
            'items' => 'required|array|min:1|max:25',
            'items.*.id' => 'required|string|max:100',       // product slug
            'items.*.qty' => 'required|integer|min:1|max:99',
            'name' => 'required|string|min:2|max:120',
            'email' => 'required|email:filter|max:150',
            'phone' => ['required', 'string', 'min:10', 'max:20', 'regex:/^[0-9+\s\-()]{10,20}$/'],
            'address' => 'required|string|min:4|max:255',
            'city' => 'required|string|min:2|max:80',
            'delivery_zone' => 'nullable|in:inside,outside',
            'payment_method' => 'nullable|string|max:40',
            'note' => 'nullable|string|max:500',
            'promo_code' => 'nullable|string|max:40',
        ], [
            'phone.regex' => 'Please provide a valid phone number (10–15 digits).',
            'items.max' => 'You cannot order more than 25 different items in a single checkout.',
        ]);

        // 3. Fast Duplicate Order Suppression (5-second window per IP + Email + Phone)
        $ip = $request->ip();
        $cleanEmail = Str::lower(trim($data['email']));
        $digitsPhone = preg_replace('/[^0-9]/', '', $data['phone']);
        $antiSpamLockKey = "ck_order_lock:" . md5("{$ip}:{$cleanEmail}:{$digitsPhone}");
        if (Cache::has($antiSpamLockKey)) {
            abort(429, 'Duplicate submission detected. Please wait a few seconds before placing another order.');
        }

        // Total order units sanity cap
        $totalUnits = array_sum(array_column($data['items'], 'qty'));
        if ($totalUnits > 200) {
            abort(422, 'Total order item quantity exceeds maximum limit of 200 units.');
        }

        // 4. Thorough XSS & Script Tag Sanitization
        $sanitize = function ($str) {
            if ($str === null) return null;
            // Strip script and iframe tags along with inner content
            $noScripts = preg_replace('/<(script|iframe|style)\b[^>]*>(.*?)<\/\1>/is', '', (string)$str);
            return strip_tags(trim($noScripts));
        };

        $data['name'] = $sanitize($data['name']);
        $data['address'] = $sanitize($data['address']);
        $data['city'] = $sanitize($data['city']);
        $data['note'] = isset($data['note']) ? $sanitize($data['note']) : null;
        $data['phone'] = trim($data['phone']);

        // resolve the signed-in customer if a valid token was sent (optional)
        $customer = null;
        try {
            $customer = Auth::guard('customer')->user();
        } catch (\Throwable $e) {
            $customer = null;
        }

        $order = DB::transaction(function () use ($data, $customer, $cleanEmail) {
                $subtotal = 0;
                $lines = [];

                foreach ($data['items'] as $row) {
                    // lock the row so concurrent orders can't oversell
                    $product = Product::where('slug', $row['id'])->lockForUpdate()->first();
                    if (! $product) {
                        abort(422, "One of the items is no longer available.");
                    }
                    if ($product->status === 'Out of stock') {
                        abort(422, "“{$product->name}” is currently out of stock.");
                    }
                    if ($product->stock < $row['qty']) {
                        abort(422, "“{$product->name}” only has {$product->stock} left in stock.");
                    }

                    $lineTotal = $product->price * $row['qty'];
                    $subtotal += $lineTotal;
                    $lines[] = [
                        'product' => $product,
                        'qty' => $row['qty'],
                        'price' => $product->price,
                    ];
                }

                // shipping from admin settings
                $ship = Setting::grouped()['shipping'] ?? [];
                $inside = (int) ($ship['inside_dhaka'] ?? 60);
                $outside = (int) ($ship['outside_dhaka'] ?? 120);
                $freeAbove = (int) ($ship['free_above'] ?? 2000);

                // zone is the customer's own choice; fall back to city text only if absent
                $isDhaka = isset($data['delivery_zone'])
                    ? $data['delivery_zone'] === 'inside'
                    : Str::contains(Str::lower($data['city']), 'dhaka');
                $shipping = $subtotal >= $freeAbove ? 0 : ($isDhaka ? $inside : $outside);

                // promo code — re-validated server-side against the full cart context
                // (customer rules, product scope, ranges…). The client discount is never trusted.
                $discount = 0;
                $promo = null;
                if (! empty($data['promo_code'])) {
                    $promo = PromoCode::where('code', Str::upper(trim($data['promo_code'])))
                        ->lockForUpdate()->first();
                    if ($promo) {
                        $ctx = array_map(fn ($l) => [
                            'slug' => $l['product']->slug,
                            'category' => $l['product']->category,
                            'line_total' => $l['price'] * $l['qty'],
                        ], $lines);

                        $res = $promo->evaluate($subtotal, $ctx, $customer);
                        if ($res['ok']) {
                            $discount = $res['discount'];
                            if ($res['free_shipping']) {
                                $shipping = 0;
                            }
                        } else {
                            $promo = null; // invalid at checkout time → drop it silently
                        }
                    }
                }

                $total = max(0, $subtotal - $discount) + $shipping;

                $order = Order::create([
                    // temporary unique code; replaced with an id-based one right after insert
                    'code' => '#CK-TMP-'.Str::upper(Str::random(12)),
                    'customer_id' => $customer->id ?? null,
                    'customer_name' => $data['name'],
                    'customer_email' => Str::lower($data['email']),
                    'phone' => $data['phone'],
                    'address' => $data['address'],
                    'city' => $data['city'],
                    'payment_method' => $data['payment_method'] ?? 'Cash on Delivery',
                    'items_count' => array_sum(array_column($data['items'], 'qty')),
                    'subtotal' => $subtotal,
                    'shipping' => $shipping,
                    'promo_code' => $promo->code ?? null,
                    'discount' => $discount,
                    'total' => $total,
                    'note' => $data['note'] ?? null,
                    'status' => 'Pending',
                    'channel' => 'Website',
                ]);

                // race-safe sequential code derived from the (unique) auto-increment id;
                // stays above the seeded #CK-2834..2841 range and can never collide
                $order->code = '#CK-'.(2840 + $order->id);
                $order->save();

                foreach ($lines as $line) {
                    $order->items()->create([
                        'product_id' => $line['product']->id,
                        'product_name' => $line['product']->name,
                        'qty' => $line['qty'],
                        'price' => $line['price'],
                    ]);
                    // decrement stock
                    $line['product']->decrement('stock', $line['qty']);
                }

                // record the redemption (a free-shipping-only code counts too)
                if ($promo) {
                    $promo->increment('used_count');
                    \App\Models\PromoRedemption::create([
                        'promo_code_id' => $promo->id,
                        'customer_id' => $customer->id ?? null,
                        'order_id' => $order->id,
                        'email' => Str::lower($data['email']),
                        'discount' => $discount,
                    ]);
                }

                // keep customer aggregates fresh
                if ($customer) {
                    $customer->increment('orders_count');
                    $customer->increment('spent', $total);
                }

                return $order;
        });

        // Set lock for 5 seconds to prevent accidental fast double submit
        Cache::put($antiSpamLockKey, true, 5);

        return response()->json([
            'message' => 'Order placed successfully.',
            'data' => [
                'code' => $order->code,
                'subtotal' => $order->subtotal,
                'shipping' => $order->shipping,
                'promo_code' => $order->promo_code,
                'discount' => $order->discount,
                'total' => $order->total,
                'status' => $order->status,
                'payment_method' => $order->payment_method,
            ],
        ], 201);
    }
}
