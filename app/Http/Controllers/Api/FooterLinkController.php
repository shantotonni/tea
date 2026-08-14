<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FooterLink;
use Illuminate\Http\Request;

class FooterLinkController extends Controller
{
    public function index()
    {
        return response()->json(['data' => FooterLink::orderBy('col')->orderBy('sort_order')->orderBy('id')->get()]);
    }

    public function store(Request $request)
    {
        return response()->json(['data' => FooterLink::create($this->validated($request))], 201);
    }

    public function update(Request $request, FooterLink $link)
    {
        $link->update($this->validated($request));

        return response()->json(['data' => $link]);
    }

    public function destroy(FooterLink $link)
    {
        $link->delete();

        return response()->json(['message' => 'Link deleted.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'col' => 'required|string|in:explore,support,contact',
            'label' => 'required|string|max:120',
            'target' => 'nullable|string|max:120',
            'is_published' => 'sometimes|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);
    }
}
