<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class PromoCode extends Model
{
    protected $fillable = [
        'code', 'description', 'type', 'value', 'min_subtotal', 'max_subtotal',
        'max_discount', 'usage_limit', 'used_count', 'per_customer_limit',
        'customer_emails', 'customer_group_id', 'new_customers_only', 'min_customer_spend',
        'scope_products', 'scope_categories', 'free_shipping',
        'starts_at', 'expires_at', 'is_active',
    ];

    public function group()
    {
        return $this->belongsTo(CustomerGroup::class, 'customer_group_id');
    }

    protected $casts = [
        'value' => 'integer',
        'min_subtotal' => 'integer',
        'max_subtotal' => 'integer',
        'max_discount' => 'integer',
        'usage_limit' => 'integer',
        'used_count' => 'integer',
        'per_customer_limit' => 'integer',
        'min_customer_spend' => 'integer',
        'customer_emails' => 'array',
        'scope_products' => 'array',
        'scope_categories' => 'array',
        'new_customers_only' => 'boolean',
        'free_shipping' => 'boolean',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function setCodeAttribute($v)
    {
        $this->attributes['code'] = Str::upper(trim($v));
    }

    public function redemptions()
    {
        return $this->hasMany(PromoRedemption::class);
    }

    /**
     * Money helper — fail response.
     */
    private static function fail(string $message): array
    {
        return ['ok' => false, 'discount' => 0, 'free_shipping' => false, 'message' => $message];
    }

    private static function taka($n): string
    {
        return '৳'.number_format((int) $n);
    }

    /**
     * Evaluate this code against a full cart context.
     *
     * @param  int          $subtotal  full cart subtotal (server-computed)
     * @param  array        $items     [ ['slug'=>, 'category'=>, 'line_total'=>], ... ]
     * @param  Customer|null $customer signed-in customer (null = guest)
     * @return array ['ok'=>bool, 'discount'=>int, 'free_shipping'=>bool, 'message'=>string]
     */
    public function evaluate(int $subtotal, array $items = [], ?Customer $customer = null): array
    {
        // --- status / window ---
        if (! $this->is_active) {
            return self::fail('This code is no longer active.');
        }
        $now = Carbon::now();
        if ($this->starts_at && $now->lt($this->starts_at)) {
            return self::fail('This code is not active yet.');
        }
        if ($this->expires_at && $now->gt($this->expires_at)) {
            return self::fail('This code has expired.');
        }

        // --- global usage limit ---
        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return self::fail('This code has reached its usage limit.');
        }

        // --- customer-restricted rules (need a signed-in customer) ---
        $needsCustomer = ! empty($this->customer_emails)
            || $this->customer_group_id
            || $this->new_customers_only
            || ($this->min_customer_spend !== null && $this->min_customer_spend > 0)
            || ($this->per_customer_limit !== null && $this->per_customer_limit > 0);

        if ($needsCustomer && ! $customer) {
            return self::fail('Please sign in to use this code.');
        }

        if ($customer) {
            $email = Str::lower($customer->email);

            // restricted to a customer group — only members can redeem
            if ($this->customer_group_id) {
                $inGroup = $customer->groups()->where('customer_groups.id', $this->customer_group_id)->exists();
                if (! $inGroup) {
                    return self::fail('This code is only available to a specific customer group.');
                }
            }

            if (! empty($this->customer_emails)) {
                $allowed = array_map(fn ($e) => Str::lower(trim($e)), $this->customer_emails);
                if (! in_array($email, $allowed, true)) {
                    return self::fail('This code is not available on your account.');
                }
            }

            if ($this->new_customers_only && (int) $customer->orders_count > 0) {
                return self::fail('This code is for first-time orders only.');
            }

            if ($this->min_customer_spend !== null && (int) $customer->spent < $this->min_customer_spend) {
                return self::fail('This code unlocks after you have spent '.self::taka($this->min_customer_spend).'.');
            }

            if ($this->per_customer_limit !== null && $this->per_customer_limit > 0) {
                $used = $this->redemptions()->where('customer_id', $customer->id)->count();
                if ($used >= $this->per_customer_limit) {
                    return self::fail('You have already used this code the maximum number of times.');
                }
            }
        }

        // --- cart amount range ---
        if ($subtotal < $this->min_subtotal) {
            return self::fail('Add '.self::taka($this->min_subtotal - $subtotal).' more to use this code.');
        }
        if ($this->max_subtotal !== null && $subtotal > $this->max_subtotal) {
            return self::fail('This code applies to orders up to '.self::taka($this->max_subtotal).'.');
        }

        // --- product / category scope: discount base = matching items only ---
        $hasScope = ! empty($this->scope_products) || ! empty($this->scope_categories);
        if ($hasScope) {
            $slugs = array_map('strval', $this->scope_products ?? []);
            $cats = array_map(fn ($c) => Str::lower($c), $this->scope_categories ?? []);
            $base = 0;
            foreach ($items as $it) {
                $matchSlug = ! empty($slugs) && in_array($it['slug'] ?? '', $slugs, true);
                $matchCat = ! empty($cats) && in_array(Str::lower($it['category'] ?? ''), $cats, true);
                if ($matchSlug || $matchCat) {
                    $base += (int) ($it['line_total'] ?? 0);
                }
            }
            if ($base <= 0) {
                return self::fail('This code only applies to specific products that are not in your cart.');
            }
        } else {
            $base = $subtotal;
        }

        // --- discount ---
        $discount = $this->type === 'percent'
            ? (int) floor($base * $this->value / 100)
            : (int) $this->value;

        if ($this->type === 'percent' && $this->max_discount) {
            $discount = min($discount, $this->max_discount);
        }
        $discount = min($discount, $base, $subtotal); // never exceed the eligible base or the cart

        // a free-shipping-only code (no monetary discount) is still valid
        if ($discount <= 0 && ! $this->free_shipping) {
            return self::fail('This code gives no discount on your current cart.');
        }

        return [
            'ok' => true,
            'discount' => $discount,
            'free_shipping' => (bool) $this->free_shipping,
            'message' => 'Code applied.',
        ];
    }
}
