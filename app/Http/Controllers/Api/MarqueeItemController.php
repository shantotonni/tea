<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarqueeItem;
use Illuminate\Http\Request;

class MarqueeItemController extends Controller
{
    public function index()
    {
        return response()->json(['data' => MarqueeItem::orderBy('sort_order')->orderBy('id')->get()]);
    }

    public function store(Request $request)
    {
        return response()->json(['data' => MarqueeItem::create($this->validated($request))], 201);
    }

    public function update(Request $request, MarqueeItem $item)
    {
        $item->update($this->validated($request));

        return response()->json(['data' => $item]);
    }

    public function destroy(MarqueeItem $item)
    {
        $item->delete();

        return response()->json(['message' => 'Item deleted.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'label' => 'required|string|max:80',
            'is_published' => 'sometimes|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);
    }
}
