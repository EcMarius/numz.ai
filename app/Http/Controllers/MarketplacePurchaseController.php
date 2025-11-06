<?php

namespace App\Http\Controllers;

use App\Models\Marketplace\MarketplaceItem;
use App\Models\Marketplace\MarketplacePurchase;
use App\Models\Marketplace\MarketplaceEarning;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session as StripeSession;

class MarketplacePurchaseController extends Controller
{
    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->middleware('auth');
        $this->stripeService = $stripeService;
    }

    /**
     * Show user's purchases
     */
    public function index()
    {
        $purchases = MarketplacePurchase::where('user_id', Auth::id())
            ->with(['item.creator', 'item.category'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('marketplace.purchases.index', compact('purchases'));
    }

    /**
     * Initiate purchase - create Stripe checkout session
     */
    public function initiate(MarketplaceItem $item)
    {
        $user = Auth::user();

        // Check if item is purchaseable
        if ($item->status !== 'approved' || !$item->is_active) {
            return back()->with('error', 'This item is not available for purchase.');
        }

        // Check if item is free
        if ($item->is_free) {
            return $this->handleFreePurchase($item, $user);
        }

        // Check if already purchased
        if ($item->isPurchasedBy($user)) {
            return redirect()->route('marketplace.show', $item)
                ->with('error', 'You have already purchased this item.');
        }

        try {
            \Stripe\Stripe::setApiKey($this->stripeService->getSecretKey());

            // Create Stripe checkout session
            $session = StripeSession::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => $item->name,
                            'description' => $item->short_description,
                            'images' => $item->icon ? [asset('storage/' . $item->icon)] : [],
                        ],
                        'unit_amount' => (int) ($item->price * 100), // Convert to cents
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('marketplace.purchase.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('marketplace.show', $item) . '?purchase=cancelled',
                'customer_email' => $user->email,
                'metadata' => [
                    'marketplace_item_id' => $item->id,
                    'buyer_id' => $user->id,
                    'creator_id' => $item->user_id,
                    'price' => $item->price,
                    'creator_revenue_percentage' => $item->creator_revenue_percentage,
                ],
            ]);

            return redirect($session->url);

        } catch (\Exception $e) {
            Log::error('Failed to create marketplace checkout session', [
                'item_id' => $item->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to initiate purchase. Please try again.');
        }
    }

    /**
     * Handle free item purchase
     */
    protected function handleFreePurchase(MarketplaceItem $item, $user)
    {
        try {
            DB::beginTransaction();

            // Create purchase record
            $purchase = MarketplacePurchase::create([
                'user_id' => $user->id,
                'marketplace_item_id' => $item->id,
                'transaction_id' => 'FREE-' . uniqid(),
                'price_paid' => 0,
                'platform_fee' => 0,
                'creator_earnings' => 0,
                'payment_provider' => 'free',
                'payment_status' => 'completed',
            ]);

            // Increment purchase count
            $item->incrementPurchases();

            DB::commit();

            return redirect()->route('marketplace.show', $item)
                ->with('success', 'Free item added to your library! You can now download it.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process free purchase', [
                'item_id' => $item->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to add item to library. Please try again.');
        }
    }

    /**
     * Handle successful purchase
     */
    public function success(Request $request)
    {
        $sessionId = $request->get('session_id');

        if (!$sessionId) {
            return redirect()->route('marketplace.index')
                ->with('error', 'Invalid purchase session.');
        }

        try {
            \Stripe\Stripe::setApiKey($this->stripeService->getSecretKey());
            $session = StripeSession::retrieve($sessionId);

            if ($session->payment_status !== 'paid') {
                return redirect()->route('marketplace.index')
                    ->with('error', 'Payment was not successful.');
            }

            // Check if purchase already exists
            $existingPurchase = MarketplacePurchase::where('transaction_id', $session->payment_intent)->first();
            if ($existingPurchase) {
                return redirect()->route('marketplace.show', $existingPurchase->item)
                    ->with('success', 'Purchase completed! You can now download your item.');
            }

            // Create purchase record
            $metadata = $session->metadata;
            $item = MarketplaceItem::findOrFail($metadata->marketplace_item_id);

            DB::beginTransaction();

            $creatorEarnings = $item->getCreatorEarnings();
            $platformFee = $item->getPlatformFee();

            // Create purchase
            $purchase = MarketplacePurchase::create([
                'user_id' => $metadata->buyer_id,
                'marketplace_item_id' => $item->id,
                'transaction_id' => $session->payment_intent,
                'price_paid' => $item->price,
                'platform_fee' => $platformFee,
                'creator_earnings' => $creatorEarnings,
                'payment_provider' => 'stripe',
                'payment_status' => 'completed',
            ]);

            // Create earning record for creator
            MarketplaceEarning::create([
                'creator_id' => $item->user_id,
                'marketplace_item_id' => $item->id,
                'purchase_id' => $purchase->id,
                'amount' => $creatorEarnings,
                'platform_fee' => $platformFee,
                'status' => 'pending', // Will become 'available' after 7 days
                'available_at' => now()->addDays(7),
            ]);

            // Increment purchase count
            $item->incrementPurchases();

            // Update creator profile stats
            if ($item->creator->creatorProfile) {
                $item->creator->creatorProfile->updateBalances();
            }

            DB::commit();

            Log::info('Marketplace purchase completed', [
                'purchase_id' => $purchase->id,
                'item_id' => $item->id,
                'buyer_id' => $metadata->buyer_id,
                'creator_id' => $item->user_id,
                'amount' => $item->price,
                'creator_earnings' => $creatorEarnings,
            ]);

            return redirect()->route('marketplace.show', $item)
                ->with('success', 'Purchase completed successfully! You can now download your item.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process marketplace purchase', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('marketplace.index')
                ->with('error', 'Failed to complete purchase. Please contact support.');
        }
    }

    /**
     * Request refund
     */
    public function refundRequest(MarketplacePurchase $purchase)
    {
        if ($purchase->user_id !== Auth::id()) {
            abort(403);
        }

        if ($purchase->payment_status !== 'completed') {
            return back()->with('error', 'This purchase cannot be refunded.');
        }

        // Check if within refund period (7 days)
        if ($purchase->created_at->diffInDays(now()) > 7) {
            return back()->with('error', 'Refund period has expired (7 days).');
        }

        return view('marketplace.purchases.refund', compact('purchase'));
    }

    /**
     * Process refund
     */
    public function refundProcess(Request $request, MarketplacePurchase $purchase)
    {
        if ($purchase->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            // In a real implementation, you would process the refund through Stripe here
            // For now, we'll just mark it as pending review

            $purchase->update([
                'refund_reason' => $request->reason,
                // In reality, you'd set payment_status to 'refunded' after Stripe processes it
            ]);

            DB::commit();

            // TODO: Send notification to admin for refund approval
            // TODO: Process Stripe refund

            return redirect()->route('marketplace.purchases.index')
                ->with('success', 'Refund request submitted. Our team will review it shortly.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process refund request', [
                'purchase_id' => $purchase->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to submit refund request. Please try again.');
        }
    }
}
