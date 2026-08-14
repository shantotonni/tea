<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $query = BlogPost::query()->orderBy('sort_order')->orderByDesc('published_at');

        if (($cat = $request->query('category')) && array_key_exists($cat, BlogPost::CATEGORIES)) {
            $query->where('category', $cat);
        }
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%");
            });
        }

        return response()->json(['data' => $query->get()]);
    }

    public function store(Request $request)
    {
        return response()->json(['data' => BlogPost::create($this->validated($request))], 201);
    }

    public function update(Request $request, BlogPost $blog)
    {
        $blog->update($this->validated($request));

        return response()->json(['data' => $blog]);
    }

    public function destroy(BlogPost $blog)
    {
        $blog->delete();

        return response()->json(['message' => 'Post deleted.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'category' => 'required|in:brewing,health,garden',
            'title' => 'required|string|max:200',
            'title_bn' => 'nullable|string|max:200',
            'excerpt' => 'required|string|max:600',
            'image' => 'nullable|string|max:200',
            'author' => 'nullable|string|max:120',
            'role' => 'nullable|string|max:80',
            'read_time' => 'nullable|string|max:30',
            'is_featured' => 'sometimes|boolean',
            'is_published' => 'sometimes|boolean',
            'sort_order' => 'nullable|integer|min:0',
            'published_at' => 'nullable|date',
        ]);
    }
}
