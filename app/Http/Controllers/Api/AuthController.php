<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /** how many failed attempts before a temporary lockout */
    private const MAX_ATTEMPTS = 5;
    private const DECAY_SECONDS = 60;

    /**
     * POST /api/login — issue a JWT for valid credentials.
     * Brute-force protected: 5 attempts / minute per email+IP.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'remember' => 'sometimes|boolean',
        ]);

        $key = $this->throttleKey($request);

        // already locked out?
        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'message' => "Too many attempts. Try again in {$seconds} seconds.",
                'retry_after' => $seconds,
            ], 429);
        }

        // longer session when "keep me signed in" is checked
        $ttl = ! empty($credentials['remember']) ? 60 * 24 * 30 : 60; // 30 days vs 1 hour
        $token = Auth::guard('api')->setTTL($ttl)->attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ]);

        if (! $token) {
            RateLimiter::hit($key, self::DECAY_SECONDS);
            $left = self::MAX_ATTEMPTS - RateLimiter::attempts($key);

            return response()->json([
                'message' => $left > 0
                    ? 'Email or password is incorrect.'
                    : 'Too many attempts. Please wait a minute.',
            ], 401);
        }

        // success — clear the counter
        RateLimiter::clear($key);

        return $this->tokenResponse($token);
    }

    public function me()
    {
        return response()->json(['user' => Auth::guard('api')->user()]);
    }

    /**
     * PUT /api/profile — update the signed-in admin's own details.
     * Changing the password requires the current password.
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::guard('api')->user();

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => 'sometimes|string|max:80',
            'current_password' => 'nullable|required_with:password|string',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        // verify current password before allowing a change
        if (! empty($data['password'])) {
            if (! \Illuminate\Support\Facades\Hash::check($data['current_password'], $user->password)) {
                return response()->json([
                    'message' => 'Your current password is incorrect.',
                    'errors' => ['current_password' => ['Your current password is incorrect.']],
                ], 422);
            }
            $user->password = \Illuminate\Support\Facades\Hash::make($data['password']);
        }

        $user->name = $data['name'];
        $user->email = $data['email'];
        if (! empty($data['role'])) {
            $user->role = $data['role'];
        }
        $user->save();

        return response()->json([
            'message' => 'Profile updated.',
            'user' => $user->fresh(),
        ]);
    }

    public function logout()
    {
        Auth::guard('api')->logout();

        return response()->json(['message' => 'Signed out.']);
    }

    public function refresh()
    {
        return $this->tokenResponse(Auth::guard('api')->refresh());
    }

    private function throttleKey(Request $request): string
    {
        return Str::lower($request->input('email')) . '|' . $request->ip();
    }

    private function tokenResponse(string $token)
    {
        return response()->json([
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
            'user' => Auth::guard('api')->user(),
        ]);
    }
}
