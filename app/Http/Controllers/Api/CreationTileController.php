<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CreationTile;
use Illuminate\Http\Request;

class CreationTileController extends Controller
{
    public function index()
    {
        return response()->json(['data' => CreationTile::orderBy('sort_order')->orderBy('id')->get()]);
    }

    public function store(Request $request)
    {
        return response()->json(['data' => CreationTile::create($this->validated($request))], 201);
    }

    public function update(Request $request, CreationTile $tile)
    {
        $tile->update($this->validated($request));

        return response()->json(['data' => $tile]);
    }

    public function destroy(CreationTile $tile)
    {
        $tile->delete();

        return response()->json(['message' => 'Tile deleted.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'image' => 'required|string|max:255',
            'label' => 'required|string|max:120',
            'meta' => 'nullable|string|max:120',
            'target' => 'nullable|string|max:160',
            'is_wide' => 'sometimes|boolean',
            'is_published' => 'sometimes|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);
    }
}
