<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InstaShot;
use Illuminate\Http\Request;

class InstaShotController extends Controller
{
    public function index()
    {
        return response()->json(['data' => InstaShot::orderBy('sort_order')->orderBy('id')->get()]);
    }

    public function store(Request $request)
    {
        return response()->json(['data' => InstaShot::create($this->validated($request))], 201);
    }

    public function update(Request $request, InstaShot $shot)
    {
        $shot->update($this->validated($request));

        return response()->json(['data' => $shot]);
    }

    public function destroy(InstaShot $shot)
    {
        $shot->delete();

        return response()->json(['message' => 'Shot deleted.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'image' => 'required|string|max:255',
            'caption' => 'nullable|string|max:160',
            'likes' => 'nullable|integer|min:0',
            'is_published' => 'sometimes|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);
    }
}
