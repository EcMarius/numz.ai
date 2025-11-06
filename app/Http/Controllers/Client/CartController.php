<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\HostingProduct;
use App\Models\DomainRegistration;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /**
     * Display shopping cart
     */
    public function index()
    {
        $cart = session()->get('cart', []);
        $total = $this->calculateTotal($cart);

        return view('client.cart.index', compact('cart', 'total'));
    }

    /**
     * Remove item from cart
     */
    public function remove($itemId)
    {
        $cart = session()->get('cart', []);

        if (isset($cart[$itemId])) {
            unset($cart[$itemId]);
            session()->put('cart', $cart);
        }

        return redirect()->route('client.cart')
            ->with('success', 'Item removed from cart');
    }

    /**
     * Update cart item
     */
    public function update(Request $request, $itemId)
    {
        $cart = session()->get('cart', []);

        if (isset($cart[$itemId])) {
            if ($request->has('quantity')) {
                $cart[$itemId]['quantity'] = max(1, (int) $request->quantity);
            }

            if ($request->has('domain')) {
                $cart[$itemId]['domain'] = $request->domain;
            }

            session()->put('cart', $cart);
        }

        return redirect()->route('client.cart')
            ->with('success', 'Cart updated successfully');
    }

    /**
     * Clear entire cart
     */
    public function clear()
    {
        session()->forget('cart');

        return redirect()->route('client.cart')
            ->with('success', 'Cart cleared');
    }

    /**
     * Add domain to cart
     */
    public function addDomain(Request $request)
    {
        $request->validate([
            'domain' => 'required|string|max:255',
            'years' => 'required|integer|min:1|max:10',
            'price' => 'required|numeric|min:0',
        ]);

        $cart = session()->get('cart', []);
        $cartItemId = 'domain_' . md5($request->domain);

        $cart[$cartItemId] = [
            'type' => 'domain',
            'domain' => $request->domain,
            'years' => $request->years,
            'price' => $request->price * $request->years,
            'unit_price' => $request->price,
            'quantity' => 1,
        ];

        session()->put('cart', $cart);

        return redirect()->route('client.cart')
            ->with('success', 'Domain added to cart successfully!');
    }

    /**
     * Calculate cart total
     */
    protected function calculateTotal($cart)
    {
        $subtotal = 0;

        foreach ($cart as $item) {
            $subtotal += $item['price'] * ($item['quantity'] ?? 1);
        }

        $taxRate = config('numz.tax_rate', 0);
        $tax = $subtotal * ($taxRate / 100);
        $total = $subtotal + $tax;

        return [
            'subtotal' => $subtotal,
            'tax' => $tax,
            'tax_rate' => $taxRate,
            'total' => $total,
            'item_count' => count($cart),
        ];
    }

    /**
     * Get cart count for header
     */
    public function count()
    {
        $cart = session()->get('cart', []);
        return response()->json(['count' => count($cart)]);
    }
}
