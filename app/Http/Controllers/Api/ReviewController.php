<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $query = Review::query()->orderBy('sort_order')->orderByDesc('id');

        if (($lang = $request->query('lang')) && in_array($lang, ['bn', 'en'])) {
            $query->where('lang', $lang);
        }
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('text', 'like', "%{$search}%");
            });
        }

        return response()->json(['data' => $query->get()]);
    }

    public function store(Request $request)
    {
        return response()->json(['data' => Review::create($this->validated($request))], 201);
    }

    public function update(Request $request, Review $review)
    {
        $review->update($this->validated($request));

        return response()->json(['data' => $review]);
    }

    public function destroy(Review $review)
    {
        $review->delete();

        return response()->json(['message' => 'Review deleted.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:120',
            'city' => 'nullable|string|max:120',
            'text' => 'required|string|max:1000',
            'lang' => 'required|in:bn,en',
            'product' => 'nullable|string|max:80',
            'rating' => 'nullable|integer|min:1|max:5',
            'verified' => 'sometimes|boolean',
            'is_published' => 'sometimes|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);
    }
}
