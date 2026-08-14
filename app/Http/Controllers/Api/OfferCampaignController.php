<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OfferCampaign;
use Illuminate\Http\Request;

class OfferCampaignController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => OfferCampaign::withCount('products')->with('promoCode:id,code')
                ->orderBy('sort_order')->orderByDesc('id')->get()
                ->map(fn ($c) => array_merge($c->toArray(), [
                    'live' => $c->isLive(),
                    'coupon' => optional($c->promoCode)->code,
                ])),
        ]);
    }

    public function show(OfferCampaign $campaign)
    {
        return response()->json(['data' => array_merge(
            $campaign->toArray(),
            [
                'product_ids' => $campaign->products()->pluck('products.id'),
                'live' => $campaign->isLive(),
                'coupon' => optional($campaign->promoCode)->code,
            ]
        )]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $campaign = OfferCampaign::create($data);
        $campaign->products()->sync($request->input('product_ids', []));
        $campaign->syncCouponToProducts();

        return response()->json(['data' => $campaign], 201);
    }

    public function update(Request $request, OfferCampaign $campaign)
    {
        $campaign->update($this->validated($request));
        if ($request->has('product_ids')) {
            $campaign->products()->sync($request->input('product_ids', []));
        }
        $campaign->syncCouponToProducts();

        return response()->json(['data' => $campaign]);
    }

    public function destroy(OfferCampaign $campaign)
    {
        $campaign->delete();

        return response()->json(['message' => 'Campaign deleted.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:140',
            'subtitle' => 'nullable|string|max:240',
            'badge' => 'nullable|string|max:60',
            'discount_label' => 'nullable|string|max:60',
            'accent' => 'nullable|string|max:20',
            'promo_code_id' => 'nullable|integer|exists:promo_codes,id',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'nullable|integer|min:0',
            'product_ids' => 'sometimes|array',
            'product_ids.*' => 'integer|exists:products,id',
        ]);
    }
}
