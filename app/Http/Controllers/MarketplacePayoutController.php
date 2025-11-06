<?php

namespace App\Http\Controllers;

use App\Models\Marketplace\MarketplaceCreatorProfile;
use App\Models\Marketplace\MarketplaceEarning;
use App\Models\Marketplace\MarketplacePayout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MarketplacePayoutController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show earnings and payout history
     */
    public function index()
    {
        $user = Auth::user();
        $profile = MarketplaceCreatorProfile::where('user_id', $user->id)->first();

        if (!$profile) {
            return redirect()->route('marketplace.creator.dashboard')
                ->with('error', 'You need to be a creator to access earnings.');
        }

        // Update balances
        $profile->updateBalances();

        // Get earnings breakdown
        $pendingEarnings = MarketplaceEarning::where('creator_id', $user->id)
            ->pending()
            ->with(['item', 'purchase'])
            ->orderByDesc('created_at')
            ->get();

        $availableEarnings = MarketplaceEarning::where('creator_id', $user->id)
            ->available()
            ->with(['item', 'purchase'])
            ->whereNull('payout_id')
            ->orderByDesc('available_at')
            ->get();

        // Get payout history
        $payouts = MarketplacePayout::where('creator_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        $minimumPayout = 50.00; // Minimum payout amount

        return view('marketplace.creator.payouts', compact(
            'profile',
            'pendingEarnings',
            'availableEarnings',
            'payouts',
            'minimumPayout'
        ));
    }

    /**
     * Show payout request form
     */
    public function create()
    {
        $user = Auth::user();
        $profile = MarketplaceCreatorProfile::where('user_id', $user->id)->first();

        if (!$profile) {
            return redirect()->route('marketplace.creator.dashboard')
                ->with('error', 'Creator profile not found.');
        }

        // Update balances
        $profile->updateBalances();

        $minimumPayout = 50.00;

        // Check if can request payout
        if (!$profile->canRequestPayout($minimumPayout)) {
            return redirect()->route('marketplace.payouts.index')
                ->with('error', 'You need at least $' . $minimumPayout . ' in available balance to request a payout.');
        }

        // Get available earnings
        $availableEarnings = MarketplaceEarning::where('creator_id', $user->id)
            ->available()
            ->whereNull('payout_id')
            ->with('item')
            ->orderByDesc('available_at')
            ->get();

        $totalAvailable = $availableEarnings->sum('amount');

        return view('marketplace.creator.payout-request', compact(
            'profile',
            'availableEarnings',
            'totalAvailable'
        ));
    }

    /**
     * Submit payout request
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $profile = MarketplaceCreatorProfile::where('user_id', $user->id)->first();

        if (!$profile) {
            return redirect()->route('marketplace.creator.dashboard')
                ->with('error', 'Creator profile not found.');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:50|max:' . $profile->available_balance,
            'method' => 'required|in:stripe,paypal,bank_transfer',
        ]);

        try {
            DB::beginTransaction();

            // Get available earnings up to requested amount
            $earnings = MarketplaceEarning::where('creator_id', $user->id)
                ->available()
                ->whereNull('payout_id')
                ->orderBy('available_at')
                ->get();

            $remainingAmount = $validated['amount'];
            $selectedEarnings = collect();

            foreach ($earnings as $earning) {
                if ($remainingAmount <= 0) {
                    break;
                }

                $selectedEarnings->push($earning);
                $remainingAmount -= $earning->amount;
            }

            // Create payout request
            $payout = MarketplacePayout::create([
                'creator_id' => $user->id,
                'amount' => $validated['amount'],
                'earnings_count' => $selectedEarnings->count(),
                'method' => $validated['method'],
                'status' => 'pending',
                'requested_at' => now(),
            ]);

            // Link earnings to payout
            MarketplaceEarning::whereIn('id', $selectedEarnings->pluck('id'))
                ->update(['payout_id' => $payout->id]);

            // Update profile balances
            $profile->updateBalances();

            DB::commit();

            // TODO: Notify admin about payout request

            Log::info('Payout request created', [
                'payout_id' => $payout->id,
                'creator_id' => $user->id,
                'amount' => $validated['amount'],
                'method' => $validated['method'],
            ]);

            return redirect()->route('marketplace.payouts.index')
                ->with('success', 'Payout request submitted successfully! You will receive your payment within 5-7 business days.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create payout request', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Failed to submit payout request. Please try again.');
        }
    }

    /**
     * Cancel pending payout request
     */
    public function cancel(MarketplacePayout $payout)
    {
        if ($payout->creator_id !== Auth::id()) {
            abort(403);
        }

        if ($payout->status !== 'pending') {
            return back()->with('error', 'Only pending payouts can be cancelled.');
        }

        try {
            DB::beginTransaction();

            // Update payout status
            $payout->update(['status' => 'cancelled']);

            // Return earnings to available status
            $payout->earnings()->update([
                'payout_id' => null,
            ]);

            // Update profile balances
            $profile = MarketplaceCreatorProfile::where('user_id', Auth::id())->first();
            if ($profile) {
                $profile->updateBalances();
            }

            DB::commit();

            return redirect()->route('marketplace.payouts.index')
                ->with('success', 'Payout request cancelled successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to cancel payout', [
                'payout_id' => $payout->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to cancel payout. Please try again.');
        }
    }

    /**
     * Show payout details
     */
    public function show(MarketplacePayout $payout)
    {
        if ($payout->creator_id !== Auth::id()) {
            abort(403);
        }

        $payout->load(['earnings.item', 'earnings.purchase']);

        return view('marketplace.creator.payout-details', compact('payout'));
    }

    /**
     * Update creator profile payout settings
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        $profile = MarketplaceCreatorProfile::firstOrCreate(['user_id' => $user->id]);

        $validated = $request->validate([
            'business_name' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:1000',
            'website' => 'nullable|url',
            'github' => 'nullable|string',
            'twitter' => 'nullable|string',
            'preferred_payout_method' => 'required|in:stripe,paypal,bank_transfer',
            'paypal_email' => 'required_if:preferred_payout_method,paypal|nullable|email',
            'country' => 'required|string',
            'address_line1' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'postal_code' => 'nullable|string',
        ]);

        try {
            $profile->update($validated);

            return back()->with('success', 'Profile updated successfully!');

        } catch (\Exception $e) {
            Log::error('Failed to update creator profile', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Failed to update profile. Please try again.');
        }
    }
}
