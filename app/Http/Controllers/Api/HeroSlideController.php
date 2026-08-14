<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HeroSlide;
use Illuminate\Http\Request;

class HeroSlideController extends Controller
{
    public function index()
    {
        return response()->json(['data' => HeroSlide::orderBy('sort_order')->orderBy('id')->get()]);
    }

    public function store(Request $request)
    {
        return response()->json(['data' => HeroSlide::create($this->validated($request))], 201);
    }

    public function update(Request $request, HeroSlide $slide)
    {
        $slide->update($this->validated($request));

        return response()->json(['data' => $slide]);
    }

    public function destroy(HeroSlide $slide)
    {
        $slide->delete();

        return response()->json(['message' => 'Slide deleted.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'image' => 'required|string|max:255',
            'is_published' => 'sometimes|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);
    }
}
