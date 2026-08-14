<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SocialLink;
use Illuminate\Http\Request;

class SocialLinkController extends Controller
{
    public function index()
    {
        return response()->json(['data' => SocialLink::orderBy('sort_order')->orderBy('id')->get()]);
    }

    public function store(Request $request)
    {
        return response()->json(['data' => SocialLink::create($this->validated($request))], 201);
    }

    public function update(Request $request, SocialLink $social)
    {
        $social->update($this->validated($request));

        return response()->json(['data' => $social]);
    }

    public function destroy(SocialLink $social)
    {
        $social->delete();

        return response()->json(['message' => 'Social link deleted.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:40',
            'href' => 'required|string|max:255',
            'is_published' => 'sometimes|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);
    }
}
