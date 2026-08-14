<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NavLink;
use Illuminate\Http\Request;

class NavLinkController extends Controller
{
    public function index()
    {
        return response()->json(['data' => NavLink::orderBy('sort_order')->orderBy('id')->get()]);
    }

    public function store(Request $request)
    {
        return response()->json(['data' => NavLink::create($this->validated($request))], 201);
    }

    public function update(Request $request, NavLink $link)
    {
        $link->update($this->validated($request));

        return response()->json(['data' => $link]);
    }

    public function destroy(NavLink $link)
    {
        $link->delete();

        return response()->json(['message' => 'Link deleted.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'label' => 'required|string|max:60',
            'target' => 'required|string|max:60',
            'is_cta' => 'sometimes|boolean',
            'is_published' => 'sometimes|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);
    }
}
