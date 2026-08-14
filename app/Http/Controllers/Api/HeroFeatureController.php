<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HeroFeature;
use Illuminate\Http\Request;

class HeroFeatureController extends Controller
{
    public function index()
    {
        return response()->json(['data' => HeroFeature::orderBy('sort_order')->orderBy('id')->get()]);
    }

    public function store(Request $request)
    {
        return response()->json(['data' => HeroFeature::create($this->validated($request))], 201);
    }

    public function update(Request $request, HeroFeature $feature)
    {
        $feature->update($this->validated($request));

        return response()->json(['data' => $feature]);
    }

    public function destroy(HeroFeature $feature)
    {
        $feature->delete();

        return response()->json(['message' => 'Feature deleted.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'icon' => 'nullable|string|max:16',
            'label' => 'required|string|max:80',
            'is_published' => 'sometimes|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);
    }
}
