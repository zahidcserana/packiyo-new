<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\ApiController;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\CustomerSetting;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class HomeController extends ApiController
{
    public function index()
    {
        $tenant = app('tenant');
        // $customerIds = Customer::withClients($tenant->id)->pluck('id')->toArray();

        // ✅ Eager load only what’s needed
        $tags = $tenant->tags()
            ->with([
                'products' => function ($query) use ($tenant) {
                    $query->where('customer_id', $tenant->id)
                        ->latest()
                        ->take(2)
                        ->with('productImages'); // eager load images for performance
                }
            ])
            ->orderBy('name')
            ->get();

        // ✅ Prepare final structured response
        $response = [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->ContactInformation->name,
                'image' => $tenant->threeplLogo?->source ?? asset('img/banner.jpg'),
                'store_image' => $tenant->storeLogo?->source ?? asset('img/store.webp'),
                'company' => $tenant->ContactInformation->company_name,
                'slug' => $tenant->slug,
                'store_domain' => $tenant->store_domain,
                'about' => customer_settings($tenant->id, CustomerSetting::CUSTOMER_SETTING_CUSTOMS_DESCRIPTION),
                'moto' => customer_settings($tenant->id, CustomerSetting::CUSTOMER_SETTING_CUSTOMS_SIGNER),
            ],
            'tags' => $tags->map(function ($tag) {
                $productCount = $tag->products()->count();

                $products = $tag->products->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'category' => $product->tags?->first()?->name,
                        'sku' => $product->sku,
                        'description' => $product->customs_description,
                        'price' => (float) $product->price,
                        'image_url' => $product->productImages->first()?->source ?? null,
                        'updated_at' => $product->updated_at->toDateTimeString(),
                    ];
                });

                return [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'product_count' => $productCount,
                    'products' => $products,
                    'feature_product' => $products->first(), // ✅ this now works properly
                ];
            }),
        ];

        return response()->json($response);
    }
}
