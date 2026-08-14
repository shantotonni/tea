<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FounderPoint;
use Illuminate\Http\Request;

class FounderPointController extends Controller
{
    public function index()
    {
        return response()->json(['data' => FounderPoint::orderBy('sort_order')->orderBy('id')->get()]);
    }

    public function store(Request $request)
    {
        return response()->json(['data' => FounderPoint::create($this->validated($request))], 201);
    }

    public function update(Request $request, FounderPoint $point)
    {
        $point->update($this->validated($request));

        return response()->json(['data' => $point]);
    }

    public function destroy(FounderPoint $point)
    {
        $point->delete();

        return response()->json(['message' => 'Point deleted.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'num' => 'required|string|max:8',
            'title' => 'required|string|max:140',
            'text' => 'required|string|max:400',
            'is_published' => 'sometimes|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);
    }
}
