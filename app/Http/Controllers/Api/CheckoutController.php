<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    /**
     * POST /api/public/checkout — place an order.
     * Guest checkout allowed; links the customer when a valid token is sent.
     * Prices, stock and shipping are ALL computed server-side — the client
     * cart is treated as untrusted input.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|string',          // product slug
            'items.*.qty' => 'required|integer|min:1|max:99',
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:150',
            'phone' => 'required|string|max:40',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:80',
            'delivery_zone' => 'nullable|in:inside,outside',
            'payment_method' => 'nullable|string|max:40',
            'note' => 'nullable|string|max:500',
            'promo_code' => 'nullable|string|max:40',
        ]);

        // resolve the signed-in customer if a valid token was sent (optional)
        $customer = null;
        try {
            $customer = Auth::guard('customer')->user();
        } catch (\Throwable $e) {
            $customer = null;
        }

        $order = DB::transaction(function () use ($data, $customer) {
                $subtotal = 0;
                $lines = [];

                foreach ($data['items'] as $row) {
                    // lock the row so concurrent orders can't oversell
                    $product = Product::where('slug', $row['id'])->lockForUpdate()->first();
                    if (! $product) {
                        abort(422, "One of the items is no longer available.");
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
