<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CollectionNote;
use Illuminate\Http\Request;

class CollectionNoteController extends Controller
{
    public function index()
    {
        return response()->json(['data' => CollectionNote::orderBy('sort_order')->orderBy('id')->get()]);
    }

    public function store(Request $request)
    {
        return response()->json(['data' => CollectionNote::create($this->validated($request))], 201);
    }

    public function update(Request $request, CollectionNote $note)
    {
        $note->update($this->validated($request));

        return response()->json(['data' => $note]);
    }

    public function destroy(CollectionNote $note)
    {
        $note->delete();

        return response()->json(['message' => 'Note deleted.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'icon' => 'nullable|string|max:16',
            'label' => 'required|string|max:120',
            'is_published' => 'sometimes|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);
    }
}
