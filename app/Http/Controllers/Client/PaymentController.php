<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Numz\Services\InvoiceService;
use App\Numz\Modules\PaymentGateways\StripeGateway;
use App\Numz\Modules\PaymentGateways\PayPalGateway;
use App\Numz\Modules\PaymentGateways\CoinbaseGateway;
use App\Numz\Modules\PaymentGateways\RazorpayGateway;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    /**
     * Show payment page
     */
    public function show(Request $request, $gateway, $invoiceId)
    {
        $invoice = Invoice::where('id', $invoiceId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        if ($invoice->status === 'paid') {
            return redirect()->route('client.invoices.show', $invoice->id)
                ->with('info', 'This invoice has already been paid');
        }

        return view('client.payment.show', compact('invoice', 'gateway'));
    }

    /**
     * Process Stripe payment
     */
    public function processStripe(Request $request)
    {
        $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'stripeToken' => 'required|string',
        ]);

        $invoice = Invoice::where('id', $request->invoice_id)
            ->where('user_id', auth()->id())
            ->where('status', 'unpaid')
            ->firstOrFail();

        $gateway = new StripeGateway();

        $result = $gateway->charge([
            'amount' => $invoice->total,
            'currency' => strtolower($invoice->currency),
            'token' => $request->stripeToken,
            'description' => "Invoice #{$invoice->invoice_number}",
            'metadata' => [
                'invoice_id' => $invoice->id,
                'user_id' => auth()->id(),
            ],
        ]);

        if ($result['success']) {
            // Record transaction
            PaymentTransaction::create([
                'user_id' => auth()->id(),
                'transaction_id' => $result['transaction_id'],
                'gateway' => 'stripe',
                'amount' => $invoice->total,
                'currency' => $invoice->currency,
                'status' => 'completed',
                'payment_method' => 'credit_card',
                'metadata' => json_encode($result),
            ]);

            // Mark invoice as paid
            $this->invoiceService->markInvoiceAsPaid($invoice, 'stripe', $result['transaction_id']);

            // Clear cart
            session()->forget('cart');
            session()->forget('checkout_invoice_id');

            return redirect()->route('client.payment.success', ['invoice' => $invoice->id]);
        }

        return redirect()->back()
            ->with('error', 'Payment failed: ' . ($result['error'] ?? 'Unknown error'));
    }

    /**
     * Process PayPal payment
     */
    public function processPayPal(Request $request)
    {
        $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
        ]);

        $invoice = Invoice::where('id', $request->invoice_id)
            ->where('user_id', auth()->id())
            ->where('status', 'unpaid')
            ->firstOrFail();

        $gateway = new PayPalGateway();

        $result = $gateway->charge([
            'amount' => $invoice->total,
            'currency' => $invoice->currency,
            'description' => "Invoice #{$invoice->invoice_number}",
            'success_url' => route('client.payment.paypal.return', ['invoice' => $invoice->id]),
            'cancel_url' => route('client.payment.show', ['gateway' => 'paypal', 'invoice' => $invoice->id]),
        ]);

        if ($result['success']) {
            // Redirect to PayPal
            return redirect($result['approval_url']);
        }

        return redirect()->back()
            ->with('error', 'PayPal initialization failed: ' . ($result['error'] ?? 'Unknown error'));
    }

    /**
     * PayPal return handler
     */
    public function paypalReturn(Request $request, $invoiceId)
    {
        $invoice = Invoice::where('id', $invoiceId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        if ($request->has('token') && $request->has('PayerID')) {
            // Payment approved, capture it
            // TODO: Implement PayPal capture logic

            // Record transaction
            PaymentTransaction::create([
                'user_id' => auth()->id(),
                'transaction_id' => $request->token,
                'gateway' => 'paypal',
                'amount' => $invoice->total,
                'currency' => $invoice->currency,
                'status' => 'completed',
                'payment_method' => 'paypal',
            ]);

            // Mark invoice as paid
            $this->invoiceService->markInvoiceAsPaid($invoice, 'paypal', $request->token);

            // Clear cart
            session()->forget('cart');

            return redirect()->route('client.payment.success', ['invoice' => $invoice->id]);
        }

        return redirect()->route('client.invoices.show', $invoice->id)
            ->with('error', 'Payment was cancelled');
    }

    /**
     * Process Coinbase payment
     */
    public function processCoinbase(Request $request)
    {
        $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
        ]);

        $invoice = Invoice::where('id', $request->invoice_id)
            ->where('user_id', auth()->id())
            ->where('status', 'unpaid')
            ->firstOrFail();

        $gateway = new CoinbaseGateway();

        $result = $gateway->charge([
            'amount' => $invoice->total,
            'currency' => $invoice->currency,
            'description' => "Invoice #{$invoice->invoice_number}",
            'success_url' => route('client.payment.success', ['invoice' => $invoice->id]),
            'cancel_url' => route('client.payment.show', ['gateway' => 'coinbase', 'invoice' => $invoice->id]),
        ]);

        if ($result['success']) {
            // Redirect to Coinbase hosted page
            return redirect($result['hosted_url']);
        }

        return redirect()->back()
            ->with('error', 'Coinbase initialization failed: ' . ($result['error'] ?? 'Unknown error'));
    }

    /**
     * Show payment success page
     */
    public function success($invoiceId)
    {
        $invoice = Invoice::where('id', $invoiceId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        return view('client.payment.success', compact('invoice'));
    }

    /**
     * Show payment failed page
     */
    public function failed(Request $request)
    {
        $error = $request->get('error', 'Payment processing failed');

        return view('client.payment.failed', compact('error'));
    }
}
