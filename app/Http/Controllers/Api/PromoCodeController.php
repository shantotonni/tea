<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\PromoCode;
use App\Services\PromoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class PromoCodeController extends Controller
{
    // ---------- admin CRUD ----------
    public function index()
    {
        return response()->json([
            'data' => PromoCode::withCount('redemptions')->with('group:id,name')->orderByDesc('id')->get(),
        ]);
    }

    public function store(Request $request)
    {
        return response()->json(['data' => PromoCode::create($this->validated($request))], 201);
    }

    public function update(Request $request, PromoCode $promo)
    {
        $promo->update($this->validated($request, $promo->id));

        return response()->json(['data' => $promo]);
    }

    public function destroy(PromoCode $promo)
    {
        $promo->delete();

        return response()->json(['message' => 'Promo code deleted.']);
    }

    private function validated(Request $request, $ignoreId = null): array
    {
        $data = $request->validate([
            'code' => 'required|string|max:40|unique:promo_codes,code'.($ignoreId ? ",{$ignoreId}" : ''),
            'description' => 'nullable|string|max:160',
            'type' => 'required|in:percent,fixed',
            'value' => 'required|integer|min:0'.($request->input('type') === 'percent' ? '|max:100' : ''),
            'min_subtotal' => 'nullable|integer|min:0',
            'max_subtotal' => 'nullable|integer|min:0',
            'max_discount' => 'nullable|integer|min:0',
            'usage_limit' => 'nullable|integer|min:0',
            'per_customer_limit' => 'nullable|integer|min:0',
            'customer_emails' => 'nullable|array',
            'customer_emails.*' => 'email',
            'customer_group_id' => 'nullable|integer|exists:customer_groups,id',
            'new_customers_only' => 'sometimes|boolean',
            'min_customer_spend' => 'nullable|integer|min:0',
            'scope_products' => 'nullable|array',
            'scope_products.*' => 'string',
            'scope_categories' => 'nullable|array',
            'scope_categories.*' => 'string',
            'free_shipping' => 'sometimes|boolean',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'sometimes|boolean',
        ]);

        // normalise emails to lowercase
        if (! empty($data['customer_emails'])) {
            $data['customer_emails'] = array_values(array_unique(
                array_map(fn ($e) => Str::lower(trim($e)), $data['customer_emails'])
            ));
        }

        return $data;
    }

    // ---------- public validation (checkout preview) ----------
    public function validateCode(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:40',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|string',
            'items.*.qty' => 'required|integer|min:1|max:99',
        ]);

        $key = 'promo:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 20)) {
            return response()->json(['valid' => false, 'message' => 'Too many attempts. Please wait a minute.'], 429);
        }
        RateLimiter::hit($key, 60);

        $promo = PromoCode::where('code', Str::upper(trim($data['code'])))->first();
        if (! $promo) {
            return response()->json(['valid' => false, 'message' => 'This promo code is not valid.']);
        }

        // signed-in customer, if a token was sent
        $customer = null;
        try {
            $customer = Auth::guard('customer')->user();
        } catch (\Throwable $e) {
            $customer = null;
        }

        $cart = PromoService::buildCart($data['items']);
        $result = $promo->evaluate($cart['subtotal'], $cart['items'], $customer instanceof Customer ? $customer : null);

        return response()->json([
            'valid' => $result['ok'],
            'discount' => $result['discount'],
            'free_shipping' => $result['free_shipping'],
            'message' => $result['message'],
            'code' => $promo->code,
        ]);
    }
}
