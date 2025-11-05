<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\HostingService;
use App\Models\DomainRegistration;
use App\Numz\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    protected $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    /**
     * Show checkout page
     */
    public function index()
    {
        if (!auth()->check()) {
            return redirect()->route('login')
                ->with('info', 'Please login to continue with checkout');
        }

        $cart = session()->get('cart', []);

        if (empty($cart)) {
            return redirect()->route('client.products')
                ->with('error', 'Your cart is empty');
        }

        // Calculate totals
        $subtotal = 0;
        foreach ($cart as $item) {
            $subtotal += $item['price'] * ($item['quantity'] ?? 1);
        }

        $taxRate = config('numz.tax_rate', 0);
        $tax = $subtotal * ($taxRate / 100);
        $total = $subtotal + $tax;

        // Get available payment gateways
        $paymentGateways = $this->getAvailablePaymentGateways();

        return view('client.checkout.index', compact('cart', 'subtotal', 'tax', 'taxRate', 'total', 'paymentGateways'));
    }

    /**
     * Process checkout
     */
    public function process(Request $request)
    {
        $request->validate([
            'payment_gateway' => 'required|string',
            'accept_terms' => 'required|accepted',
        ]);

        $cart = session()->get('cart', []);

        if (empty($cart)) {
            return redirect()->route('client.products')
                ->with('error', 'Your cart is empty');
        }

        // Create invoice
        $invoice = Invoice::create([
            'user_id' => auth()->id(),
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'status' => 'unpaid',
            'subtotal' => 0,
            'tax' => 0,
            'total' => 0,
            'currency' => config('numz.currency', 'USD'),
            'due_date' => now(),
        ]);

        // Create pending services and add invoice items
        foreach ($cart as $cartItem) {
            if ($cartItem['type'] === 'product') {
                // Create hosting service
                $service = HostingService::create([
                    'user_id' => auth()->id(),
                    'product_id' => $cartItem['product_id'],
                    'domain' => $cartItem['domain'] ?? 'pending-' . Str::random(10) . '.com',
                    'billing_cycle' => $cartItem['billing_cycle'],
                    'price' => $cartItem['price'],
                    'status' => 'pending',
                    'next_due_date' => $this->calculateNextDueDate($cartItem['billing_cycle']),
                    'registration_date' => now(),
                ]);

                // Add to invoice
                $invoice->addItem(
                    description: $cartItem['product_name'] . ' - ' . ucfirst($cartItem['billing_cycle']),
                    amount: $cartItem['price'],
                    quantity: 1,
                    itemType: 'service',
                    itemId: $service->id
                );
            } elseif ($cartItem['type'] === 'domain') {
                // Create domain registration
                $domain = DomainRegistration::create([
                    'user_id' => auth()->id(),
                    'domain' => $cartItem['domain'],
                    'registrar' => 'domainnameapi',
                    'status' => 'pending',
                    'registration_date' => now(),
                    'expiry_date' => now()->addYears($cartItem['years']),
                    'renewal_price' => $cartItem['unit_price'],
                    'auto_renew' => true,
                ]);

                // Add to invoice
                $invoice->addItem(
                    description: "Domain Registration - {$cartItem['domain']} ({$cartItem['years']} year" . ($cartItem['years'] > 1 ? 's' : '') . ")",
                    amount: $cartItem['unit_price'],
                    quantity: $cartItem['years'],
                    itemType: 'domain',
                    itemId: $domain->id
                );
            }
        }

        // Calculate invoice totals
        $invoice->calculateTotals();

        // Store invoice ID in session for payment processing
        session()->put('checkout_invoice_id', $invoice->id);

        // Redirect to payment gateway
        return redirect()->route('client.payment', [
            'gateway' => $request->payment_gateway,
            'invoice' => $invoice->id,
        ]);
    }

    /**
     * Calculate next due date
     */
    protected function calculateNextDueDate($billingCycle)
    {
        return match($billingCycle) {
            'monthly' => now()->addMonth(),
            'quarterly' => now()->addMonths(3),
            'semi_annually' => now()->addMonths(6),
            'annually' => now()->addYear(),
            'biennially' => now()->addYears(2),
            'triennially' => now()->addYears(3),
            default => now()->addMonth(),
        };
    }

    /**
     * Get available payment gateways
     */
    protected function getAvailablePaymentGateways()
    {
        $gateways = [];

        // Check which gateways are enabled
        $enabledGateways = [
            'stripe' => [
                'name' => 'Credit Card (Stripe)',
                'icon' => 'credit-card',
                'description' => 'Pay securely with your credit or debit card',
            ],
            'paypal' => [
                'name' => 'PayPal',
                'icon' => 'paypal',
                'description' => 'Pay with your PayPal account',
            ],
            'coinbase' => [
                'name' => 'Cryptocurrency',
                'icon' => 'bitcoin',
                'description' => 'Pay with Bitcoin, Ethereum, or other cryptocurrencies',
            ],
            'razorpay' => [
                'name' => 'Razorpay',
                'icon' => 'wallet',
                'description' => 'Pay with UPI, cards, or net banking',
            ],
        ];

        foreach ($enabledGateways as $key => $gateway) {
            // Check if gateway is enabled in module settings
            $enabled = \App\Models\ModuleSetting::get('payment_gateway', $key, 'enabled', 'false');
            if ($enabled === 'true') {
                $gateways[$key] = $gateway;
            }
        }

        // Always include manual payment option
        $gateways['manual'] = [
            'name' => 'Bank Transfer / Manual Payment',
            'icon' => 'building-columns',
            'description' => 'Pay via bank transfer or other manual methods',
        ];

        return $gateways;
    }
}
