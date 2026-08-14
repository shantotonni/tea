<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PromoBanner;
use Illuminate\Http\Request;

class PromoBannerController extends Controller
{
    public function index()
    {
        return response()->json(['data' => PromoBanner::orderBy('sort_order')->orderBy('id')->get()]);
    }

    public function store(Request $request)
    {
        return response()->json(['data' => PromoBanner::create($this->validated($request))], 201);
    }

    public function update(Request $request, PromoBanner $banner)
    {
        $banner->update($this->validated($request));

        return response()->json(['data' => $banner]);
    }

    public function destroy(PromoBanner $banner)
    {
        $banner->delete();

        return response()->json(['message' => 'Banner deleted.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'image' => 'required|string|max:255',
            'badge' => 'nullable|string|max:60',
            'eyebrow' => 'nullable|string|max:120',
            'title' => 'required|string|max:160',
            'text' => 'nullable|string|max:300',
            'target' => 'nullable|string|max:160',
            'cta' => 'nullable|string|max:80',
            'is_published' => 'sometimes|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);
    }
}
