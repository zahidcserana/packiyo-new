<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\ApiController;
use App\Models\Product;
use App\Models\Tag;
use Illuminate\Http\Request;

class ProductController extends ApiController
{
    public function index()
    {
        $tenantId = request('tenant_id');

        $products = Product::where('customer_id', $tenantId)
            // ->where('is_active', true)
            ->with('productImages') // eager load all images
            ->paginate(20);

          // Format the response
        $formattedProducts = $products->getCollection()->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'category' => $product->tags?->first()?->name,
                'sku' => $product->sku,
                'description' => $product->customs_description,
                'price' => (float) $product->price,
                'images' => $product->productImages->map(fn($image) => $image->source),
                'updated_at' => $product->updated_at->toDateTimeString(),
            ];
        });

        // Return paginated response with formatted products
        return response()->json([
            'current_page' => $products->currentPage(),
            'per_page' => $products->perPage(),
            'total' => $products->total(),
            'data' => $formattedProducts,
        ]);

        // return response()->json($products);
    }

    public function productSearch()
    {
        $sku = request('sku');
        $tenant = app('tenant');

        $product = Product::where('customer_id', $tenant->id)
            ->where('sku', $sku)
            ->with(['productImages', 'tags'])
            ->firstOrFail();

        // ✅ Parse the notes safely into structured items
        $items = collect(explode(',', $product->notes ?? ''))
            ->map(function ($row) {
                $parts = array_map('trim', explode(':', $row, 2));
                return [
                    'count' => $parts[0] ?? null,
                    'content' => $parts[1] ?? null,
                ];
            })
            ->filter(fn($item) => filled($item['content'])) // avoid empty lines
            ->values();

        $data = [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'category' => $product->tags?->first()?->name,
            'description' => $product->customs_description,
            'price' => (float) $product->price,
            'notes_raw' => $product->notes,       // keep original string if needed
            'content' => $items,                    // structured version
            'images' => $product->productImages->map(fn($image) => $image->source)->values(),
            'updated_at' => $product->updated_at->toDateTimeString(),
        ];

        return response()->json($data);
    }

    public function show($tenantSlug, $id)
    {
        $tenantId = request('tenant_id');

        $product = Product::where('customer_id', $tenantId)
            ->where('id', $id)
            // ->where('is_active', true)
            ->with('productImages')
            ->firstOrFail();

        $data = [
            'id' => $product->id,
            'name' => $product->name,
            'category' => $product->tags?->first()?->name,
            'sku' => $product->sku,
            'description' => $product->customs_description,
            'price' => (float) $product->price,
            'images' => $product->productImages->map(fn($image) => $image->source),
            'updated_at' => $product->updated_at->toDateTimeString(),
        ];

        return response()->json($data);
    }

    public function getProductsByTag(Request $request, $tenantSlug, $tagSlug)
    {
        // Get the tenant set by middleware
        $tenant = app('tenant');

        // Find the tag for this tenant
        $tag = Tag::where('name', $tagSlug)
            ->where('customer_id', $tenant->id)
            ->firstOrFail();

        // Get the products associated with the tag and eager load all images
        $products = $tag->products()
            ->where('customer_id', $tenant->id)
            ->with('productImages') // eager load all images
            ->paginate(20);

        // Format the response
        $formattedProducts = $products->getCollection()->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'category' => $product->tags?->first()?->name,
                'sku' => $product->sku,
                'description' => $product->customs_description,
                'price' => (float) $product->price,
                'images' => $product->productImages->map(fn($image) => $image->source),
                'updated_at' => $product->updated_at->toDateTimeString(),
            ];
        });

        // Return paginated response with formatted products
        return response()->json([
            'current_page' => $products->currentPage(),
            'per_page' => $products->perPage(),
            'total' => $products->total(),
            'data' => $formattedProducts,
        ]);
    }


    public function getProductsByTagOld(Request $request, $tenantSlug, $tagSlug)
    {
        $tenant = app('tenant'); // set by middleware

        $tag = Tag::where('name', $tagSlug)->where('customer_id', $tenant->id)->firstOrFail();

        $products = $tag->products()
            ->where('customer_id', $tenant->id)
            ->with(['tags']) // optional: eager load tags
            ->paginate(20);

        return response()->json($products);
    }
}
