<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\ApiController;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartController extends ApiController
{
    /**
     * Create a new cart or update an existing cart
     */
    public function store(Request $request, $tenantSlug)
    {
        dd($request->all());

        $tenantId = $request->get('tenant_id');
        $items = $request->get('items', []); // [{ product_id, qty }]

        // Create new cart
        $cart = Cart::create([
            'cart_token' => Str::uuid()->toString(),
            'customer_id' => $tenantId,
            'status' => 'open',
        ]);

        foreach ($items as $item) {
            $product = Product::where('customer_id', $tenantId)
                ->where('id', $item['product_id'])
                ->firstOrFail();

            $cart->items()->create([
                'product_id' => $product->id,
                'qty' => $item['qty'] ?? 1,
                'unit_price' => $product->price,
            ]);
        }

        return response()->json([
            'cart_token' => $cart->cart_token,
            'cart' => $cart->load('items.product'),
        ]);
    }

    /**
     * View cart details
     */
    public function show($tenantSlug, $cartToken)
    {
        $cart = Cart::with('items.product')
            ->where('cart_token', $cartToken)
            ->first();

        if (!$cart) {
            return response()->json(['error' => 'Cart not found'], 404);
        }

        return response()->json($cart);
    }

    /**
     * Update cart (add/remove items)
     */
    public function update(Request $request, $tenantSlug, $cartToken)
    {
        $cart = Cart::with('items')->where('cart_token', $cartToken)->firstOrFail();

        if ($cart->status !== 'open') {
            return response()->json(['error' => 'Cart is not editable'], 400);
        }

        foreach ($request->get('items', []) as $item) {
            $existing = $cart->items()->where('product_id', $item['product_id'])->first();

            if ($existing) {
                if (isset($item['qty']) && $item['qty'] > 0) {
                    $existing->update(['qty' => $item['qty']]);
                } else {
                    $existing->delete(); // remove if qty=0
                }
            } else {
                $product = Product::where('customer_id', $cart->customer_id)
                    ->where('id', $item['product_id'])
                    ->firstOrFail();

                $cart->items()->create([
                    'product_id' => $product->id,
                    'qty' => $item['qty'] ?? 1,
                    'unit_price' => $product->price,
                ]);
            }
        }

        return response()->json($cart->load('items.product'));
    }
}
