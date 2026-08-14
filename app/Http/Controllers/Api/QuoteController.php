<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use Illuminate\Http\Request;

class QuoteController extends Controller
{
    public function index(Request $request)
    {
        $query = Quote::orderBy('tab')->orderBy('sort_order')->orderBy('id');

        if ($tab = $request->query('tab')) {
            $query->where('tab', $tab);
        }

        return response()->json(['data' => $query->get()]);
    }

    public function store(Request $request)
    {
        return response()->json(['data' => Quote::create($this->validated($request))], 201);
    }

    public function update(Request $request, Quote $quote)
    {
        $quote->update($this->validated($request));

        return response()->json(['data' => $quote]);
    }

    public function destroy(Quote $quote)
    {
        $quote->delete();

        return response()->json(['message' => 'Quote deleted.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'tab' => 'required|string|in:wisdom,health',
            'text' => 'required|string|max:600',
            'author' => 'required|string|max:160',
            'title' => 'nullable|string|max:200',
            'is_published' => 'sometimes|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);
    }
}
