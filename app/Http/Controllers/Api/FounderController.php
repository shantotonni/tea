<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Founder;
use Illuminate\Http\Request;

class FounderController extends Controller
{
    public function index()
    {
        return response()->json(['data' => Founder::orderBy('sort_order')->orderBy('id')->get()]);
    }

    public function store(Request $request)
    {
        return response()->json(['data' => Founder::create($this->validated($request))], 201);
    }

    public function update(Request $request, Founder $founder)
    {
        $founder->update($this->validated($request));

        return response()->json(['data' => $founder]);
    }

    public function destroy(Founder $founder)
    {
        $founder->delete();

        return response()->json(['message' => 'Founder deleted.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:120',
            'role' => 'required|string|max:120',
            'initials' => 'nullable|string|max:4',
            'is_published' => 'sometimes|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);
    }
}
