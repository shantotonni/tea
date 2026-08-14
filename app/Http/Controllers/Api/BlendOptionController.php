<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlendOption;
use Illuminate\Http\Request;

class BlendOptionController extends Controller
{
    public function store(Request $request)
    {
        return response()->json(['data' => BlendOption::create($this->validated($request))], 201);
    }

    public function update(Request $request, BlendOption $option)
    {
        $option->update($this->validated($request));

        return response()->json(['data' => $option]);
    }

    public function destroy(BlendOption $option)
    {
        $option->delete();

        return response()->json(['message' => 'Option deleted.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'question_id' => 'required|integer|exists:blend_questions,id',
            'opt_id' => 'required|string|max:40',
            'title' => 'required|string|max:80',
            'hint' => 'nullable|string|max:160',
            'icon' => 'nullable|string|max:16',
            'sort_order' => 'nullable|integer|min:0',
        ]);
    }
}
