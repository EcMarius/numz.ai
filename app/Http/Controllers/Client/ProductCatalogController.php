<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\HostingProduct;
use Illuminate\Http\Request;

class ProductCatalogController extends Controller
{
    /**
     * Display product catalog
     */
    public function index(Request $request)
    {
        $type = $request->get('type', 'all');

        $query = HostingProduct::where('is_active', true);

        if ($type !== 'all') {
            $query->where('type', $type);
        }

        $products = $query->orderBy('monthly_price', 'asc')->get();

        $productsByType = [
            'shared' => $products->where('type', 'shared'),
            'vps' => $products->where('type', 'vps'),
            'dedicated' => $products->where('type', 'dedicated'),
            'cloud' => $products->where('type', 'cloud'),
            'reseller' => $products->where('type', 'reseller'),
        ];

        return view('client.products.index', compact('products', 'productsByType', 'type'));
    }

    /**
     * Show single product details
     */
    public function show($slug)
    {
        $product = HostingProduct::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $similarProducts = HostingProduct::where('type', $product->type)
            ->where('id', '!=', $product->id)
            ->where('is_active', true)
            ->limit(3)
            ->get();

        return view('client.products.show', compact('product', 'similarProducts'));
    }

    /**
     * Add product to cart
     */
    public function addToCart(Request $request, $slug)
    {
        $product = HostingProduct::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $request->validate([
            'billing_cycle' => 'required|in:monthly,quarterly,semi_annually,annually,biennially,triennially',
            'domain' => 'nullable|string|max:255',
        ]);

        $billingCycle = $request->billing_cycle;

        // Get price based on billing cycle
        $price = match($billingCycle) {
            'monthly' => $product->monthly_price,
            'quarterly' => $product->quarterly_price ?? $product->monthly_price * 3,
            'semi_annually' => $product->semi_annually_price ?? $product->monthly_price * 6,
            'annually' => $product->annually_price ?? $product->monthly_price * 12,
            'biennially' => $product->biennially_price ?? $product->monthly_price * 24,
            'triennially' => $product->triennially_price ?? $product->monthly_price * 36,
            default => $product->monthly_price,
        };

        // Get or create cart from session
        $cart = session()->get('cart', []);

        $cartItemId = 'product_' . $product->id . '_' . $billingCycle;

        $cart[$cartItemId] = [
            'type' => 'product',
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_type' => $product->type,
            'billing_cycle' => $billingCycle,
            'price' => $price,
            'domain' => $request->domain,
            'quantity' => 1,
        ];

        session()->put('cart', $cart);

        return redirect()->route('client.cart')
            ->with('success', 'Product added to cart successfully!');
    }
}
