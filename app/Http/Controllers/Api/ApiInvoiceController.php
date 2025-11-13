<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Http\Request;

class ApiInvoiceController extends Controller
{
    /**
     * Get all invoices
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $userId = $request->input('user_id');
        $status = $request->input('status');

        $query = Invoice::with(['user', 'items']);

        // Filter by user
        if ($userId) {
            $query->where('user_id', $userId);
        }

        // Filter by status
        if ($status) {
            $query->where('status', $status);
        }

        // Order by latest
        $query->orderBy('created_at', 'desc');

        $invoices = $query->paginate($perPage);

        return InvoiceResource::collection($invoices);
    }

    /**
     * Get single invoice
     */
    public function show($id)
    {
        $invoice = Invoice::with(['user', 'items'])->findOrFail($id);

        return new InvoiceResource($invoice);
    }

    /**
     * Create new invoice
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'due_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $invoice = Invoice::create([
            'user_id' => $request->user_id,
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'status' => 'unpaid',
            'due_date' => $request->due_date,
            'currency' => $request->currency ?? 'USD',
            'discount' => $request->discount ?? 0,
        ]);

        // Add items
        foreach ($request->items as $item) {
            $invoice->addItem(
                $item['description'],
                $item['unit_price'],
                $item['quantity'],
                $item['item_type'] ?? null,
                $item['item_id'] ?? null,
                $item['details'] ?? null
            );
        }

        // Calculate totals
        $invoice->calculateTotals();

        // Trigger webhook
        app(\App\Services\WebhookService::class)->trigger('invoice.created', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'user_id' => $invoice->user_id,
            'total' => $invoice->total,
            'due_date' => $invoice->due_date->toIso8601String(),
        ], $invoice->user_id);

        return response()->json([
            'success' => true,
            'message' => 'Invoice created successfully',
            'data' => new InvoiceResource($invoice->load(['user', 'items'])),
        ], 201);
    }

    /**
     * Mark invoice as paid
     */
    public function pay(Request $request, $id)
    {
        $invoice = Invoice::findOrFail($id);

        $request->validate([
            'payment_method' => 'required|string',
            'transaction_id' => 'nullable|string',
            'amount' => 'nullable|numeric|min:0',
        ]);

        $amount = $request->amount ?? $invoice->total;

        if ($amount >= $invoice->total) {
            $invoice->markAsPaid($request->payment_method, $request->transaction_id);
        } else {
            $invoice->update([
                'amount_paid' => $invoice->amount_paid + $amount,
            ]);
        }

        // Trigger webhook
        app(\App\Services\WebhookService::class)->trigger('invoice.paid', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'user_id' => $invoice->user_id,
            'amount' => $amount,
            'payment_method' => $request->payment_method,
        ], $invoice->user_id);

        return response()->json([
            'success' => true,
            'message' => 'Payment recorded successfully',
            'data' => new InvoiceResource($invoice),
        ]);
    }

    /**
     * Cancel invoice
     */
    public function cancel($id)
    {
        $invoice = Invoice::findOrFail($id);

        if ($invoice->status === 'paid') {
            return response()->json([
                'success' => false,
                'error' => 'Cannot cancel a paid invoice',
            ], 400);
        }

        $invoice->update(['status' => 'cancelled']);

        // Trigger webhook
        app(\App\Services\WebhookService::class)->trigger('invoice.cancelled', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'user_id' => $invoice->user_id,
        ], $invoice->user_id);

        return response()->json([
            'success' => true,
            'message' => 'Invoice cancelled successfully',
            'data' => new InvoiceResource($invoice),
        ]);
    }

    /**
     * Download invoice PDF
     */
    public function download($id)
    {
        $invoice = Invoice::with(['user', 'items'])->findOrFail($id);

        return $invoice->downloadPdf();
    }
}
