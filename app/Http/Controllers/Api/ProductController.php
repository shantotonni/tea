<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query()->latest('id');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        return response()->json(['data' => $query->get()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']) . '-' . Str::random(4);
        $data = $this->withDetails($request, $data);

        return response()->json(['data' => Product::create($data)], 201);
    }

    public function show(Product $product)
    {
        return response()->json(['data' => $product]);
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->withDetails($request, $this->validated($request, $product->id), $product);
        $product->update($data);

        return response()->json(['data' => $product->fresh()]);
    }

    /** fold the tasting/facts/gallery/specs/brewing/story inputs into the details JSON column */
    private function withDetails(Request $request, array $data, ?Product $existing = null): array
    {
        $current = $existing->details ?? [];

        if ($request->has('tasting')) {
            $data['details']['tasting'] = $request->input('tasting', []);
        }
        if ($request->has('facts')) {
            $data['details']['facts'] = $request->input('facts', []);
        }
        if ($request->has('gallery')) {
            $data['details']['gallery'] = array_values(array_filter(
                array_map('trim', (array) $request->input('gallery', []))
            ));
        }
        if ($request->has('specs')) {
            $data['details']['specs'] = $request->input('specs', []);
        }
        if ($request->has('brewing')) {
            $data['details']['brewing'] = $request->input('brewing', []);
        }
        if ($request->has('sizes')) {
            $data['details']['sizes'] = $request->input('sizes', []);
        }
        if ($request->has('story')) {
            $data['details']['story'] = $request->input('story');
        }
        if ($request->has('brew_note')) {
            $data['details']['brew_note'] = $request->input('brew_note');
        }
        if ($request->has('ship_note')) {
            $data['details']['ship_note'] = $request->input('ship_note');
        }
        if ($request->has('seo')) {
            $seoInput = $request->input('seo', []);
            $data['details']['seo'] = [
                'meta_title' => $seoInput['meta_title'] ?? null,
                'meta_description' => $seoInput['meta_description'] ?? null,
                'meta_keywords' => $seoInput['meta_keywords'] ?? null,
                'og_image' => $seoInput['og_image'] ?? null,
            ];
        }

        // preserve any detail keys not sent this time
        if (isset($data['details'])) {
            $data['details'] = array_merge($current, $data['details']);
        }

        return $data;
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json(['message' => 'Product deleted.']);
    }

    private function validated(Request $request, $ignoreId = null)
    {
        return $request->validate([
            'name' => 'required|string|max:150',
            'slug' => 'nullable|string|unique:products,slug' . ($ignoreId ? ",{$ignoreId}" : ''),
            'sku' => 'required|string|unique:products,sku' . ($ignoreId ? ",{$ignoreId}" : ''),
            'category' => 'required|string|max:80',
            'blurb' => 'nullable|string',
            'image' => 'nullable|string',
            'tag' => 'nullable|string|max:40',
            'weight' => 'nullable|string|max:20',
            'price' => 'required|integer|min:0',
            'old_price' => 'nullable|integer|min:0',
            'stock' => 'nullable|integer|min:0',
            'reviews' => 'nullable|integer|min:0',
            'status' => 'nullable|string|max:30',
            'rating' => 'nullable|numeric|min:0|max:5',
            'is_featured' => 'sometimes|boolean',
            'in_gift_box' => 'sometimes|boolean',
        ]);
    }
}
