<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlendQuestion;
use Illuminate\Http\Request;

class BlendQuestionController extends Controller
{
    public function index()
    {
        return response()->json(['data' => BlendQuestion::with('options')->orderBy('sort_order')->orderBy('id')->get()]);
    }

    public function store(Request $request)
    {
        return response()->json(['data' => BlendQuestion::create($this->validated($request))], 201);
    }

    public function update(Request $request, BlendQuestion $question)
    {
        $question->update($this->validated($request));

        return response()->json(['data' => $question]);
    }

    public function destroy(BlendQuestion $question)
    {
        $question->delete(); // options cascade

        return response()->json(['message' => 'Question deleted.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'key' => 'required|string|max:40',
            'label' => 'required|string|max:160',
            'is_published' => 'sometimes|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);
    }
}
