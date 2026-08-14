<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function index(Request $request)
    {
        $query = Faq::query()->orderBy('sort_order')->orderBy('id');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('question', 'like', "%{$search}%")
                    ->orWhere('answer', 'like', "%{$search}%");
            });
        }

        return response()->json(['data' => $query->get()]);
    }

    public function store(Request $request)
    {
        return response()->json(['data' => Faq::create($this->validated($request))], 201);
    }

    public function update(Request $request, Faq $faq)
    {
        $faq->update($this->validated($request));

        return response()->json(['data' => $faq]);
    }

    public function destroy(Faq $faq)
    {
        $faq->delete();

        return response()->json(['message' => 'FAQ deleted.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'question' => 'required|string|max:200',
            'answer' => 'required|string|max:2000',
            'is_published' => 'sometimes|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);
    }
}
