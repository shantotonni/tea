<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HeroStat;
use Illuminate\Http\Request;

class HeroStatController extends Controller
{
    public function index()
    {
        return response()->json(['data' => HeroStat::orderBy('sort_order')->orderBy('id')->get()]);
    }

    public function store(Request $request)
    {
        return response()->json(['data' => HeroStat::create($this->validated($request))], 201);
    }

    public function update(Request $request, HeroStat $stat)
    {
        $stat->update($this->validated($request));

        return response()->json(['data' => $stat]);
    }

    public function destroy(HeroStat $stat)
    {
        $stat->delete();

        return response()->json(['message' => 'Stat deleted.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'value' => 'required|string|max:32',
            'label' => 'required|string|max:80',
            'is_published' => 'sometimes|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);
    }
}
