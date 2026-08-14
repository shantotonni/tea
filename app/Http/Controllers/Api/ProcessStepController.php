<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProcessStep;
use Illuminate\Http\Request;

class ProcessStepController extends Controller
{
    public function index()
    {
        return response()->json(['data' => ProcessStep::orderBy('sort_order')->orderBy('id')->get()]);
    }

    public function store(Request $request)
    {
        return response()->json(['data' => ProcessStep::create($this->validated($request))], 201);
    }

    public function update(Request $request, ProcessStep $step)
    {
        $step->update($this->validated($request));

        return response()->json(['data' => $step]);
    }

    public function destroy(ProcessStep $step)
    {
        $step->delete();

        return response()->json(['message' => 'Step deleted.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'num' => 'required|string|max:8',
            'title' => 'required|string|max:120',
            'text' => 'required|string|max:400',
            'is_published' => 'sometimes|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);
    }
}
