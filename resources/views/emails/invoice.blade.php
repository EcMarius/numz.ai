<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $invoice->invoice_number }}</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f3f4f6;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f3f4f6; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="padding: 40px 30px; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <h2 style="margin: 0 0 10px; color: #111827; font-size: 24px; font-weight: bold;">
                                            {{ config('app.name') }}
                                        </h2>
                                        <p style="margin: 0; color: #6b7280; font-size: 14px;">
                                            Invoice #{{ $invoice->invoice_number }}
                                        </p>
                                    </td>
                                    <td align="right">
                                        <span style="display: inline-block; padding: 8px 16px; background-color: {{ $invoice->status === 'paid' ? '#10b981' : '#f59e0b' }}; color: #ffffff; border-radius: 20px; font-size: 13px; font-weight: 600;">
                                            {{ strtoupper($invoice->status) }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <p style="margin: 0 0 20px; color: #374151; font-size: 16px;">
                                Hi {{ $invoice->user->name }},
                            </p>

                            @if($invoice->status === 'paid')
                            <p style="margin: 0 0 20px; color: #374151; font-size: 16px; line-height: 1.6;">
                                Thank you for your payment! This email confirms that we've received your payment for invoice #{{ $invoice->invoice_number }}.
                            </p>
                            @else
                            <p style="margin: 0 0 20px; color: #374151; font-size: 16px; line-height: 1.6;">
                                A new invoice has been generated for your account. Please review the details below and make your payment by {{ $invoice->due_date->format('M d, Y') }}.
                            </p>
                            @endif

                            <!-- Invoice Details -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin: 30px 0; background-color: #f9fafb; border-radius: 8px; padding: 20px;">
                                <tr>
                                    <td width="50%">
                                        <p style="margin: 0 0 5px; color: #6b7280; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">
                                            Invoice Number
                                        </p>
                                        <p style="margin: 0; color: #111827; font-size: 15px; font-weight: 600;">
                                            {{ $invoice->invoice_number }}
                                        </p>
                                    </td>
                                    <td width="50%" align="right">
                                        <p style="margin: 0 0 5px; color: #6b7280; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">
                                            Invoice Date
                                        </p>
                                        <p style="margin: 0; color: #111827; font-size: 15px; font-weight: 600;">
                                            {{ $invoice->created_at->format('M d, Y') }}
                                        </p>
                                    </td>
                                </tr>
                                <tr><td colspan="2" style="height: 15px;"></td></tr>
                                <tr>
                                    <td width="50%">
                                        <p style="margin: 0 0 5px; color: #6b7280; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">
                                            Due Date
                                        </p>
                                        <p style="margin: 0; color: #111827; font-size: 15px; font-weight: 600;">
                                            {{ $invoice->due_date->format('M d, Y') }}
                                        </p>
                                    </td>
                                    <td width="50%" align="right">
                                        <p style="margin: 0 0 5px; color: #6b7280; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">
                                            Amount Due
                                        </p>
                                        <p style="margin: 0; color: #111827; font-size: 20px; font-weight: bold;">
                                            ${{ number_format($invoice->total, 2) }}
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Invoice Items -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin: 30px 0;">
                                <thead>
                                    <tr style="background-color: #f3f4f6;">
                                        <th style="padding: 12px; text-align: left; color: #6b7280; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">
                                            Description
                                        </th>
                                        <th style="padding: 12px; text-align: right; color: #6b7280; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; width: 80px;">
                                            Amount
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($invoice->items as $item)
                                    <tr style="border-bottom: 1px solid #e5e7eb;">
                                        <td style="padding: 15px 12px; color: #374151; font-size: 14px;">
                                            <strong>{{ $item->description }}</strong>
                                            @if($item->details)
                                            <br><span style="color: #6b7280; font-size: 13px;">{{ $item->details }}</span>
                                            @endif
                                        </td>
                                        <td style="padding: 15px 12px; text-align: right; color: #111827; font-size: 14px; font-weight: 600;">
                                            ${{ number_format($item->amount, 2) }}
                                        </td>
                                    </tr>
                                    @endforeach

                                    <!-- Subtotal -->
                                    <tr>
                                        <td style="padding: 12px; text-align: right; color: #6b7280; font-size: 14px;">
                                            Subtotal:
                                        </td>
                                        <td style="padding: 12px; text-align: right; color: #111827; font-size: 14px; font-weight: 600;">
                                            ${{ number_format($invoice->subtotal, 2) }}
                                        </td>
                                    </tr>

                                    <!-- Tax -->
                                    @if($invoice->tax > 0)
                                    <tr>
                                        <td style="padding: 12px; text-align: right; color: #6b7280; font-size: 14px;">
                                            Tax ({{ $invoice->tax_rate }}%):
                                        </td>
                                        <td style="padding: 12px; text-align: right; color: #111827; font-size: 14px; font-weight: 600;">
                                            ${{ number_format($invoice->tax, 2) }}
                                        </td>
                                    </tr>
                                    @endif

                                    <!-- Total -->
                                    <tr style="background-color: #f9fafb;">
                                        <td style="padding: 15px 12px; text-align: right; color: #111827; font-size: 16px; font-weight: 600;">
                                            Total:
                                        </td>
                                        <td style="padding: 15px 12px; text-align: right; color: #111827; font-size: 18px; font-weight: bold;">
                                            ${{ number_format($invoice->total, 2) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- Payment Button -->
                            @if($invoice->status !== 'paid')
                            <div style="text-align: center; margin: 30px 0;">
                                <a href="{{ route('dashboard.invoices.pay', $invoice->id) }}" style="display: inline-block; padding: 14px 32px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px;">
                                    Pay Invoice Now
                                </a>
                            </div>

                            <p style="margin: 20px 0; color: #6b7280; font-size: 13px; text-align: center;">
                                We accept credit cards, PayPal, and bank transfers
                            </p>
                            @endif

                            <!-- Download PDF -->
                            <div style="margin: 30px 0; padding: 20px; background-color: #f9fafb; border-radius: 8px; text-align: center;">
                                <p style="margin: 0 0 10px; color: #374151; font-size: 14px;">
                                    Need a PDF copy?
                                </p>
                                <a href="{{ route('dashboard.invoices.download', $invoice->id) }}" style="color: #2563eb; text-decoration: none; font-weight: 600;">
                                    Download Invoice PDF
                                </a>
                            </div>

                            @if($invoice->notes)
                            <div style="margin: 30px 0; padding: 20px; background-color: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 4px;">
                                <p style="margin: 0 0 5px; color: #92400e; font-weight: 600; font-size: 14px;">
                                    Note:
                                </p>
                                <p style="margin: 0; color: #78350f; font-size: 14px;">
                                    {{ $invoice->notes }}
                                </p>
                            </div>
                            @endif

                            <p style="margin: 30px 0 0; color: #374151; font-size: 14px; line-height: 1.6;">
                                If you have any questions about this invoice, please don't hesitate to <a href="{{ route('contact') }}" style="color: #2563eb; text-decoration: none;">contact us</a>.
                            </p>

                            <p style="margin: 20px 0 0; color: #374151; font-size: 14px;">
                                Best regards,<br>
                                <strong>The {{ config('app.name') }} Team</strong>
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 30px; background-color: #f9fafb; border-top: 1px solid #e5e7eb; text-align: center;">
                            <p style="margin: 0 0 10px; color: #6b7280; font-size: 14px;">
                                {{ config('app.name') }}
                            </p>
                            <p style="margin: 0 0 15px; color: #9ca3af; font-size: 12px;">
                                This invoice was sent to {{ $invoice->user->email }}
                            </p>
                            <p style="margin: 0; font-size: 12px;">
                                <a href="{{ route('dashboard.invoices.index') }}" style="color: #6b7280; text-decoration: none; margin: 0 10px;">View All Invoices</a>
                                <a href="{{ route('contact') }}" style="color: #6b7280; text-decoration: none; margin: 0 10px;">Support</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
