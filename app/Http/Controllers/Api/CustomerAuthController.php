<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class CustomerAuthController extends Controller
{
    private const MAX_ATTEMPTS = 5;
    private const DECAY_SECONDS = 60;

    /** POST /api/customer/register */
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:150|unique:customers,email',
            'phone' => 'nullable|string|max:40',
            'password' => 'required|string|min:8',
        ]);

        $customer = Customer::create([
            'name' => $data['name'],
            'email' => Str::lower($data['email']),
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'tier' => 'Bronze',
        ]);

        $token = Auth::guard('customer')->login($customer);

        return $this->tokenResponse($token);
    }

    /** POST /api/customer/login — brute-force protected, supports Email or Phone number */
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'nullable|string',
            'email' => 'nullable|string',
            'password' => 'required|string',
        ]);

        $loginInput = trim($request->input('login', $request->input('email', '')));
        if (empty($loginInput)) {
            return response()->json(['message' => 'Please provide an email or phone number.'], 422);
        }

        $isEmail = filter_var($loginInput, FILTER_VALIDATE_EMAIL);

        $field = $isEmail ? 'email' : 'phone';
        $value = $isEmail ? Str::lower($loginInput) : $loginInput;

        $key = 'customer|'.$value.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json(['message' => "Too many attempts. Try again in {$seconds} seconds."], 429);
        }

        $token = Auth::guard('customer')->attempt([
            $field => $value,
            'password' => $request->input('password'),
        ]);

        // Try alternative phone format if initial attempt failed
        if (! $token && ! $isEmail) {
            $altPhone = str_starts_with($value, '+88') ? substr($value, 3) : '+88' . $value;
            $token = Auth::guard('customer')->attempt([
                'phone' => $altPhone,
                'password' => $request->input('password'),
            ]);
        }

        if (! $token) {
            RateLimiter::hit($key, self::DECAY_SECONDS);

            return response()->json(['message' => 'Email/Phone or password is incorrect.'], 401);
        }

        RateLimiter::clear($key);

        return $this->tokenResponse($token);
    }

    /** GET /api/customer/me */
    public function me()
    {
        return response()->json(['customer' => Auth::guard('customer')->user()]);
    }

    /** POST /api/customer/logout */
    public function logout()
    {
        Auth::guard('customer')->logout();

        return response()->json(['message' => 'Signed out.']);
    }

    /** GET /api/customer/orders — signed-in customer's order history */
    public function orders()
    {
        $customer = Auth::guard('customer')->user();

        $orders = $customer->orders()
            ->with('items')
            ->orderByDesc('id')
            ->get()
            ->map(fn ($o) => [
                'code' => $o->code,
                'status' => $o->status,
                'subtotal' => $o->subtotal,
                'shipping' => $o->shipping,
                'discount' => $o->discount,
                'promo_code' => $o->promo_code,
                'total' => $o->total,
                'payment_method' => $o->payment_method,
                'address' => $o->address,
                'city' => $o->city,
                'items_count' => $o->items_count,
                'created_at' => $o->created_at,
                'items' => $o->items->map(fn ($i) => [
                    'product_name' => $i->product_name, 'qty' => $i->qty, 'price' => $i->price,
                ]),
            ]);

        return response()->json(['data' => $orders]);
    }

    /** PUT /api/customer/profile — update the signed-in customer's own details */
    public function updateProfile(Request $request)
    {
        $customer = Auth::guard('customer')->user();

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:150|unique:customers,email,'.$customer->id,
            'phone' => 'nullable|string|max:40',
            'city' => 'nullable|string|max:80',
            'current_password' => 'nullable|required_with:password|string',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        // password change requires the current one
        if (! empty($data['password'])) {
            if (empty($customer->password) || ! \Illuminate\Support\Facades\Hash::check($data['current_password'], $customer->password)) {
                return response()->json([
                    'message' => 'Your current password is incorrect.',
                    'errors' => ['current_password' => ['Your current password is incorrect.']],
                ], 422);
            }
            $customer->password = \Illuminate\Support\Facades\Hash::make($data['password']);
        }

        $customer->name = $data['name'];
        $customer->email = \Illuminate\Support\Str::lower($data['email']);
        $customer->phone = $data['phone'] ?? $customer->phone;
        $customer->city = $data['city'] ?? $customer->city;
        $customer->save();

        return response()->json(['message' => 'Profile updated.', 'customer' => $customer->fresh()]);
    }

    /** GET /api/customer/wishlist */
    public function getWishlist()
    {
        $customer = Auth::guard('customer')->user();
        return response()->json(['data' => $customer->wishlist ?? []]);
    }

    /** POST /api/customer/wishlist/toggle */
    public function toggleWishlist(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|string',
        ]);
        $customer = Auth::guard('customer')->user();
        $wishlist = $customer->wishlist ?? [];
        $productId = $data['product_id'];

        if (in_array($productId, $wishlist)) {
            $wishlist = array_values(array_filter($wishlist, fn ($id) => $id !== $productId));
        } else {
            $wishlist[] = $productId;
        }

        $customer->wishlist = $wishlist;
        try {
            $customer->save();
        } catch (\Throwable $e) {
            /* ignore if column absent */
        }

        return response()->json(['data' => $wishlist]);
    }

    private function tokenResponse(string $token)
    {
        return response()->json([
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard('customer')->factory()->getTTL() * 60,
            'customer' => Auth::guard('customer')->user(),
        ]);
    }
}
