<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StoryPoint;
use Illuminate\Http\Request;

class StoryPointController extends Controller
{
    public function index()
    {
        return response()->json(['data' => StoryPoint::orderBy('sort_order')->orderBy('id')->get()]);
    }

    public function store(Request $request)
    {
        return response()->json(['data' => StoryPoint::create($this->validated($request))], 201);
    }

    public function update(Request $request, StoryPoint $point)
    {
        $point->update($this->validated($request));

        return response()->json(['data' => $point]);
    }

    public function destroy(StoryPoint $point)
    {
        $point->delete();

        return response()->json(['message' => 'Point deleted.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'text' => 'required|string|max:240',
            'is_published' => 'sometimes|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);
    }
}
