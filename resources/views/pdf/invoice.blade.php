<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice #{{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.5;
        }
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            margin-bottom: 30px;
            border-bottom: 2px solid #4F46E5;
            padding-bottom: 20px;
        }
        .company-info {
            float: left;
            width: 50%;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #4F46E5;
            margin-bottom: 10px;
        }
        .invoice-info {
            float: right;
            width: 50%;
            text-align: right;
        }
        .invoice-title {
            font-size: 28px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
        .billing-info {
            margin: 30px 0;
        }
        .billing-section {
            float: left;
            width: 50%;
        }
        .billing-section h3 {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #4F46E5;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 30px 0;
        }
        .items-table th {
            background-color: #4F46E5;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: bold;
        }
        .items-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        .items-table tbody tr:hover {
            background-color: #f9fafb;
        }
        .text-right {
            text-align: right;
        }
        .totals {
            float: right;
            width: 300px;
            margin-top: 20px;
        }
        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .totals-row.total {
            font-size: 16px;
            font-weight: bold;
            border-top: 2px solid #4F46E5;
            border-bottom: 2px solid #4F46E5;
            margin-top: 10px;
            padding: 12px 0;
        }
        .totals-row.total .amount {
            color: #4F46E5;
        }
        .footer {
            margin-top: 60px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 11px;
            color: #6b7280;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-paid {
            background-color: #d1fae5;
            color: #065f46;
        }
        .status-unpaid {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .status-cancelled {
            background-color: #e5e7eb;
            color: #374151;
        }
        .payment-info {
            background-color: #f3f4f6;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .payment-info h3 {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #4F46E5;
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        {{-- Header --}}
        <div class="header clearfix">
            <div class="company-info">
                <div class="company-name">NUMZ.AI</div>
                <div>The First AI Hosting Billing Software</div>
                <div>{{ config('app.url') }}</div>
                <div>{{ config('mail.from.address') }}</div>
            </div>
            <div class="invoice-info">
                <div class="invoice-title">INVOICE</div>
                <div><strong>Invoice #:</strong> {{ $invoice->invoice_number }}</div>
                <div><strong>Date:</strong> {{ $invoice->created_at->format('M d, Y') }}</div>
                <div><strong>Due Date:</strong> {{ $invoice->due_date->format('M d, Y') }}</div>
                <div style="margin-top: 10px;">
                    <span class="status-badge status-{{ $invoice->status }}">
                        {{ ucfirst($invoice->status) }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Billing Information --}}
        <div class="billing-info clearfix">
            <div class="billing-section">
                <h3>BILL TO:</h3>
                <div><strong>{{ $invoice->user->name }}</strong></div>
                <div>{{ $invoice->user->email }}</div>
                @if($invoice->user->phone)
                    <div>{{ $invoice->user->phone }}</div>
                @endif
                @if($invoice->user->address)
                    <div>{{ $invoice->user->address }}</div>
                @endif
                @if($invoice->user->city || $invoice->user->state || $invoice->user->postal_code)
                    <div>
                        {{ $invoice->user->city }}
                        @if($invoice->user->state), {{ $invoice->user->state }}@endif
                        @if($invoice->user->postal_code) {{ $invoice->user->postal_code }}@endif
                    </div>
                @endif
                @if($invoice->user->country)
                    <div>{{ $invoice->user->country }}</div>
                @endif
            </div>
            <div class="billing-section" style="float: right;">
                <h3>FROM:</h3>
                <div><strong>NUMZ.AI</strong></div>
                <div>support@numz.ai</div>
                <div>www.numz.ai</div>
            </div>
        </div>

        {{-- Invoice Items --}}
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 50%;">Description</th>
                    <th style="width: 15%;" class="text-right">Quantity</th>
                    <th style="width: 17.5%;" class="text-right">Unit Price</th>
                    <th style="width: 17.5%;" class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                    <tr>
                        <td>
                            <strong>{{ $item->description }}</strong>
                            @if($item->details)
                                <br><small style="color: #6b7280;">{{ $item->details }}</small>
                            @endif
                        </td>
                        <td class="text-right">{{ $item->quantity }}</td>
                        <td class="text-right">${{ number_format($item->unit_price, 2) }}</td>
                        <td class="text-right">${{ number_format($item->amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Totals --}}
        <div class="clearfix">
            <div class="totals">
                <div class="totals-row">
                    <span>Subtotal:</span>
                    <span class="amount">${{ number_format($invoice->subtotal, 2) }}</span>
                </div>
                @if($invoice->discount > 0)
                    <div class="totals-row">
                        <span>Discount:</span>
                        <span class="amount">-${{ number_format($invoice->discount, 2) }}</span>
                    </div>
                @endif
                @if($invoice->tax > 0)
                    <div class="totals-row">
                        <span>Tax:</span>
                        <span class="amount">${{ number_format($invoice->tax, 2) }}</span>
                    </div>
                @endif
                <div class="totals-row total">
                    <span>TOTAL:</span>
                    <span class="amount">${{ number_format($invoice->total, 2) }}</span>
                </div>
            </div>
        </div>

        {{-- Payment Information --}}
        @if($invoice->status === 'paid')
            <div class="clearfix" style="margin-top: 80px;">
                <div class="payment-info">
                    <h3>PAYMENT INFORMATION</h3>
                    <div><strong>Status:</strong> Paid</div>
                    @if($invoice->paid_at)
                        <div><strong>Payment Date:</strong> {{ $invoice->paid_at->format('M d, Y H:i') }}</div>
                    @endif
                    @if($invoice->payment_method)
                        <div><strong>Payment Method:</strong> {{ ucfirst($invoice->payment_method) }}</div>
                    @endif
                    @if($invoice->transaction_id)
                        <div><strong>Transaction ID:</strong> {{ $invoice->transaction_id }}</div>
                    @endif
                </div>
            </div>
        @elseif($invoice->status === 'unpaid')
            <div class="clearfix" style="margin-top: 80px;">
                <div class="payment-info">
                    <h3>PAYMENT INSTRUCTIONS</h3>
                    <div>Please visit {{ config('app.url') }}/portal/invoices/{{ $invoice->id }} to pay this invoice online.</div>
                    <div>We accept credit cards, PayPal, and cryptocurrency payments.</div>
                    <div style="margin-top: 10px;"><strong>Amount Due:</strong> ${{ number_format($invoice->total, 2) }}</div>
                </div>
            </div>
        @endif

        {{-- Footer --}}
        <div class="footer">
            <p>Thank you for your business!</p>
            <p>NUMZ.AI - The First AI Hosting Billing Software</p>
            <p>Generated on {{ now()->format('F d, Y') }}</p>
        </div>
    </div>
</body>
</html>
