<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class OfferCampaign extends Model
{
    protected $fillable = [
        'title', 'subtitle', 'badge', 'discount_label', 'accent', 'promo_code_id',
        'starts_at', 'ends_at', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'offer_campaign_products')->orderBy('products.id');
    }

    public function promoCode()
    {
        return $this->belongsTo(PromoCode::class, 'promo_code_id');
    }

    /**
     * Lock the assigned coupon to exactly this campaign's products so it can
     * ONLY be redeemed on offer items, and default it to single-use-per-customer.
     */
    public function syncCouponToProducts(): void
    {
        if (! $this->promo_code_id) {
            return;
        }
        $promo = $this->promoCode()->first();
        if (! $promo) {
            return;
        }
        $slugs = $this->products()->pluck('products.slug')->all();
        $promo->scope_products = $slugs;
        // one coupon, one use per customer (guest must sign in to redeem)
        if (! $promo->per_customer_limit) {
            $promo->per_customer_limit = 1;
        }
        $promo->is_active = true;
        $promo->save();
    }

    /** is this campaign live right now? */
    public function isLive(): bool
    {
        if (! $this->is_active) {
            return false;
        }
        $now = Carbon::now();
        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }
        if ($this->ends_at && $now->gt($this->ends_at)) {
            return false;
        }

        return true;
    }

    /** scope: currently live (active + within date window) */
    public function scopeLive($query)
    {
        $now = Carbon::now();

        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now));
    }
}
