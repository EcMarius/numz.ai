<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Wave\Subscription;
use Carbon\Carbon;

/**
 * LEGALLY COMPLIANT PRICE CHANGE NOTIFICATION EMAIL
 *
 * This email is sent when a plan's price changes and user needs to accept.
 * It complies with:
 * - EU GDPR requirements (advance notice, right to cancel)
 * - US consumer protection laws (clear disclosure)
 * - Stripe best practices (transparency, user control)
 *
 * LEGAL REQUIREMENTS MET:
 * 1. Clear disclosure of current vs new price
 * 2. Exact date when change takes effect (renewal date)
 * 3. Right to cancel before change (cancel link)
 * 4. Call-to-action to accept new price (dashboard link)
 * 5. Complete transparency (no hidden fees)
 */
class PriceChangeNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $subscription;
    public $user;
    public $plan;
    public $currentPrice;
    public $newPrice;
    public $currency;
    public $renewalDate;
    public $daysUntilRenewal;
    public $acceptUrl;
    public $cancelUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(Subscription $subscription)
    {
        $this->subscription = $subscription;
        $this->user = $subscription->user()->first();
        $this->plan = $subscription->plan;

        // Current price they're paying
        $this->currentPrice = $subscription->subscribed_price ?? ($subscription->cycle == 'month'
            ? $this->plan->monthly_price
            : $this->plan->yearly_price);

        // New price they'll pay
        $this->newPrice = $subscription->pending_price;
        $this->currency = $subscription->pending_currency;

        // Renewal/effective date
        $this->renewalDate = Carbon::parse($subscription->price_change_effective_date)->format('F j, Y');
        $this->daysUntilRenewal = Carbon::parse($subscription->price_change_effective_date)->diffInDays(now());

        // Action URLs
        $this->acceptUrl = url('/dashboard?show_price_change=true');
        $this->cancelUrl = url('/settings/subscription');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Important: Price Change for Your ' . $this->plan->name . ' Subscription',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.subscriptions.price-change',
            with: [
                'subscription' => $this->subscription,
                'user' => $this->user,
                'plan' => $this->plan,
                'currentPrice' => $this->currentPrice,
                'newPrice' => $this->newPrice,
                'currency' => $this->currency,
                'renewalDate' => $this->renewalDate,
                'daysUntilRenewal' => $this->daysUntilRenewal,
                'acceptUrl' => $this->acceptUrl,
                'cancelUrl' => $this->cancelUrl,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
