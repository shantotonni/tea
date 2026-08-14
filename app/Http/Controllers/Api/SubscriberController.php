<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class SubscriberController extends Controller
{
    /** admin — list subscribers */
    public function index()
    {
        return response()->json(['data' => Subscriber::orderByDesc('id')->get()]);
    }

    /** admin — remove a subscriber */
    public function destroy(Subscriber $subscriber)
    {
        $subscriber->delete();

        return response()->json(['message' => 'Subscriber removed.']);
    }

    /** public — newsletter signup (rate limited, idempotent) */
    public function store(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email|max:150',
        ]);

        $key = 'subscribe:'.Str::lower($data['email']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json(['message' => 'Too many attempts. Please try again later.'], 429);
        }
        RateLimiter::hit($key, 60);

        // idempotent — never leak whether the email already existed
        Subscriber::firstOrCreate(
            ['email' => Str::lower($data['email'])],
            ['source' => 'newsletter']
        );

        return response()->json(['message' => 'Subscribed. Welcome to the tea ritual!'], 201);
    }
}
