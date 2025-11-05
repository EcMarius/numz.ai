<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\HostingService;
use App\Models\DomainRegistration;
use App\Models\Invoice;
use Illuminate\Http\Request;

class ClientPortalController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Client dashboard
     */
    public function dashboard()
    {
        $user = auth()->user();

        $stats = [
            'active_services' => HostingService::where('user_id', $user->id)
                ->where('status', 'active')
                ->count(),
            'total_services' => HostingService::where('user_id', $user->id)->count(),
            'active_domains' => DomainRegistration::where('user_id', $user->id)
                ->where('status', 'active')
                ->count(),
            'unpaid_invoices' => Invoice::where('user_id', $user->id)
                ->where('status', 'unpaid')
                ->count(),
            'total_spent' => Invoice::where('user_id', $user->id)
                ->where('status', 'paid')
                ->sum('total'),
        ];

        // Recent services
        $recentServices = HostingService::where('user_id', $user->id)
            ->with('product')
            ->latest()
            ->limit(5)
            ->get();

        // Upcoming renewals
        $upcomingRenewals = HostingService::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('next_due_date', '<=', now()->addDays(30))
            ->with('product')
            ->orderBy('next_due_date')
            ->limit(5)
            ->get();

        // Recent invoices
        $recentInvoices = Invoice::where('user_id', $user->id)
            ->latest()
            ->limit(5)
            ->get();

        return view('client.dashboard', compact('stats', 'recentServices', 'upcomingRenewals', 'recentInvoices'));
    }

    /**
     * List all services
     */
    public function services()
    {
        $services = HostingService::where('user_id', auth()->id())
            ->with(['product', 'server'])
            ->paginate(15);

        return view('client.services.index', compact('services'));
    }

    /**
     * Show single service
     */
    public function showService($id)
    {
        $service = HostingService::where('user_id', auth()->id())
            ->with(['product', 'server'])
            ->findOrFail($id);

        return view('client.services.show', compact('service'));
    }

    /**
     * Request service cancellation
     */
    public function cancelService(Request $request, $id)
    {
        $service = HostingService::where('user_id', auth()->id())
            ->findOrFail($id);

        $request->validate([
            'cancellation_reason' => 'required|string|max:1000',
        ]);

        // Update service status
        $service->update([
            'status' => 'cancelled',
            'notes' => 'Cancellation reason: ' . $request->cancellation_reason,
        ]);

        return redirect()->route('client.services')
            ->with('success', 'Service cancellation request submitted');
    }

    /**
     * List all domains
     */
    public function domains()
    {
        $domains = DomainRegistration::where('user_id', auth()->id())
            ->paginate(15);

        return view('client.domains.index', compact('domains'));
    }

    /**
     * Show single domain
     */
    public function showDomain($id)
    {
        $domain = DomainRegistration::where('user_id', auth()->id())
            ->findOrFail($id);

        return view('client.domains.show', compact('domain'));
    }

    /**
     * Update domain nameservers
     */
    public function updateNameservers(Request $request, $id)
    {
        $domain = DomainRegistration::where('user_id', auth()->id())
            ->findOrFail($id);

        $request->validate([
            'nameserver1' => 'required|string|max:255',
            'nameserver2' => 'required|string|max:255',
            'nameserver3' => 'nullable|string|max:255',
            'nameserver4' => 'nullable|string|max:255',
        ]);

        $domain->update($request->only(['nameserver1', 'nameserver2', 'nameserver3', 'nameserver4']));

        // TODO: Update nameservers via registrar API

        return redirect()->route('client.domains.show', $domain->id)
            ->with('success', 'Nameservers updated successfully');
    }

    /**
     * Toggle auto-renew for domain
     */
    public function toggleAutoRenew($id)
    {
        $domain = DomainRegistration::where('user_id', auth()->id())
            ->findOrFail($id);

        $domain->update([
            'auto_renew' => !$domain->auto_renew,
        ]);

        return redirect()->route('client.domains.show', $domain->id)
            ->with('success', 'Auto-renewal ' . ($domain->auto_renew ? 'enabled' : 'disabled'));
    }

    /**
     * List all invoices
     */
    public function invoices()
    {
        $invoices = Invoice::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('client.invoices.index', compact('invoices'));
    }

    /**
     * Show single invoice
     */
    public function showInvoice($id)
    {
        $invoice = Invoice::where('user_id', auth()->id())
            ->with('items')
            ->findOrFail($id);

        return view('client.invoices.show', compact('invoice'));
    }

    /**
     * Download invoice PDF
     */
    public function downloadInvoice($id)
    {
        $invoice = Invoice::where('user_id', auth()->id())
            ->with(['items', 'user'])
            ->findOrFail($id);

        // TODO: Generate PDF
        // For now, return view that can be printed
        return view('client.invoices.pdf', compact('invoice'));
    }

    /**
     * Pay invoice
     */
    public function payInvoice($id)
    {
        $invoice = Invoice::where('user_id', auth()->id())
            ->where('status', 'unpaid')
            ->findOrFail($id);

        // Create temporary cart with this invoice
        $cart = [];
        foreach ($invoice->items as $item) {
            $cart['invoice_item_' . $item->id] = [
                'type' => 'invoice_item',
                'description' => $item->description,
                'price' => $item->total,
                'quantity' => 1,
            ];
        }

        session()->put('cart', $cart);
        session()->put('checkout_invoice_id', $invoice->id);

        return redirect()->route('client.checkout');
    }
}
