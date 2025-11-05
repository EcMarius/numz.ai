<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Numz\Modules\Registrars\DomainNameAPIRegistrar;
use Illuminate\Http\Request;

class DomainController extends Controller
{
    /**
     * Show domain search page
     */
    public function index()
    {
        $tlds = $this->getAvailableTLDs();

        return view('client.domains.search', compact('tlds'));
    }

    /**
     * Check domain availability (AJAX)
     */
    public function checkAvailability(Request $request)
    {
        $request->validate([
            'domain' => 'required|string|max:255',
        ]);

        $domain = strtolower(trim($request->domain));

        // Remove http://, https://, www.
        $domain = preg_replace('#^https?://(www\.)?#', '', $domain);

        // Extract domain and TLD
        if (!str_contains($domain, '.')) {
            return response()->json([
                'success' => false,
                'error' => 'Please enter a valid domain name',
            ], 422);
        }

        // Check if domain looks valid
        if (!preg_match('/^[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,}$/i', $domain)) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid domain format',
            ], 422);
        }

        // Get domain pricing
        $tlds = $this->getAvailableTLDs();
        $parts = explode('.', $domain);
        $tld = '.' . end($parts);

        $price = $tlds[$tld] ?? 12.99;

        // Simulate availability check (in production, use registrar API)
        $available = $this->checkDomainWithRegistrar($domain);

        if ($available) {
            return response()->json([
                'success' => true,
                'available' => true,
                'domain' => $domain,
                'price' => $price,
                'message' => 'Domain is available!',
            ]);
        } else {
            // Generate alternatives
            $alternatives = $this->generateAlternatives($domain, $tlds);

            return response()->json([
                'success' => true,
                'available' => false,
                'domain' => $domain,
                'message' => 'Domain is not available',
                'alternatives' => $alternatives,
            ]);
        }
    }

    /**
     * Bulk domain search
     */
    public function bulkSearch(Request $request)
    {
        $request->validate([
            'domains' => 'required|array|min:1|max:50',
            'domains.*' => 'required|string|max:255',
        ]);

        $results = [];
        $tlds = $this->getAvailableTLDs();

        foreach ($request->domains as $domain) {
            $domain = strtolower(trim($domain));
            $domain = preg_replace('#^https?://(www\.)?#', '', $domain);

            if (preg_match('/^[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,}$/i', $domain)) {
                $parts = explode('.', $domain);
                $tld = '.' . end($parts);
                $price = $tlds[$tld] ?? 12.99;

                $results[] = [
                    'domain' => $domain,
                    'available' => $this->checkDomainWithRegistrar($domain),
                    'price' => $price,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'results' => $results,
        ]);
    }

    /**
     * Check domain with registrar (simulated for now)
     */
    protected function checkDomainWithRegistrar($domain)
    {
        // In production, use actual registrar API
        // For now, simulate: domains ending with even numbers are "available"
        $hash = crc32($domain);
        return ($hash % 2) === 0;
    }

    /**
     * Generate domain alternatives
     */
    protected function generateAlternatives($domain, $tlds)
    {
        $alternatives = [];
        $parts = explode('.', $domain);
        $name = $parts[0];
        $originalTld = '.' . end($parts);

        // Try different TLDs
        $suggestedTlds = ['.com', '.net', '.org', '.io', '.co', '.app'];

        foreach ($suggestedTlds as $tld) {
            if ($tld !== $originalTld) {
                $altDomain = $name . $tld;
                if ($this->checkDomainWithRegistrar($altDomain)) {
                    $alternatives[] = [
                        'domain' => $altDomain,
                        'price' => $tlds[$tld] ?? 12.99,
                        'available' => true,
                    ];

                    if (count($alternatives) >= 5) break;
                }
            }
        }

        // Try variations of the name
        $variations = [
            'get' . $name,
            $name . 'app',
            $name . 'hq',
            $name . 'online',
            'my' . $name,
        ];

        foreach ($variations as $variation) {
            $altDomain = $variation . $originalTld;
            if ($this->checkDomainWithRegistrar($altDomain)) {
                $alternatives[] = [
                    'domain' => $altDomain,
                    'price' => $tlds[$originalTld] ?? 12.99,
                    'available' => true,
                ];

                if (count($alternatives) >= 10) break;
            }
        }

        return $alternatives;
    }

    /**
     * Get available TLDs with pricing
     */
    protected function getAvailableTLDs()
    {
        return [
            '.com' => 12.99,
            '.net' => 13.99,
            '.org' => 11.99,
            '.io' => 34.99,
            '.co' => 29.99,
            '.app' => 14.99,
            '.dev' => 12.99,
            '.info' => 9.99,
            '.biz' => 10.99,
            '.me' => 19.99,
            '.tv' => 29.99,
            '.xyz' => 7.99,
            '.online' => 8.99,
            '.site' => 8.99,
            '.store' => 11.99,
            '.tech' => 12.99,
            '.space' => 6.99,
            '.pro' => 14.99,
            '.cloud' => 11.99,
            '.ai' => 99.99,
        ];
    }

    /**
     * Add domain to cart
     */
    public function addToCart(Request $request)
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

        return response()->json([
            'success' => true,
            'message' => 'Domain added to cart',
            'cart_count' => count($cart),
        ]);
    }
}
