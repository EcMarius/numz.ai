<?php

namespace App\Http\Controllers;

use App\Models\Marketplace\MarketplaceCategory;
use App\Models\Marketplace\MarketplaceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MarketplaceController extends Controller
{
    /**
     * Display marketplace homepage
     */
    public function index(Request $request)
    {
        $categories = MarketplaceCategory::where('active', true)
            ->orderBy('sort_order')
            ->withCount('approvedItems')
            ->get();

        $query = MarketplaceItem::approved()
            ->active()
            ->with(['creator', 'category']);

        // Apply filters
        if ($request->has('category') && $request->category) {
            $query->where('category_id', $request->category);
        }

        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('short_description', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->has('price_type')) {
            if ($request->price_type === 'free') {
                $query->free();
            } elseif ($request->price_type === 'paid') {
                $query->paid();
            }
        }

        if ($request->has('rating') && $request->rating) {
            $query->where('average_rating', '>=', $request->rating);
        }

        // Apply sorting
        $sort = $request->get('sort', 'popular');
        switch ($sort) {
            case 'newest':
                $query->orderByDesc('created_at');
                break;
            case 'rating':
                $query->orderByDesc('average_rating')->orderByDesc('reviews_count');
                break;
            case 'price_low':
                $query->orderBy('price');
                break;
            case 'price_high':
                $query->orderByDesc('price');
                break;
            case 'popular':
            default:
                $query->orderByDesc('purchases_count')->orderByDesc('downloads_count');
                break;
        }

        $items = $query->paginate(24)->withQueryString();

        $featuredItems = MarketplaceItem::approved()
            ->active()
            ->featured()
            ->with(['creator', 'category'])
            ->orderByDesc('featured_at')
            ->limit(6)
            ->get();

        return view('marketplace.index', compact('items', 'categories', 'featuredItems'));
    }

    /**
     * Display item details
     */
    public function show(MarketplaceItem $item)
    {
        if ($item->status !== 'approved' || !$item->is_active) {
            // Only creators and admins can view unapproved items
            if (!Auth::check() || (Auth::id() !== $item->user_id && !Auth::user()->hasRole('admin'))) {
                abort(404);
            }
        }

        // Increment view count
        $item->incrementViews();

        // Load relationships
        $item->load([
            'creator.creatorProfile',
            'category',
            'approvedReviews.user',
            'versions' => function ($query) {
                $query->orderByDesc('created_at');
            }
        ]);

        // Check if user has purchased
        $hasPurchased = false;
        if (Auth::check()) {
            $hasPurchased = $item->isPurchasedBy(Auth::user());
        }

        // Get more items from same creator
        $moreFromCreator = MarketplaceItem::approved()
            ->active()
            ->where('user_id', $item->user_id)
            ->where('id', '!=', $item->id)
            ->orderByDesc('purchases_count')
            ->limit(4)
            ->get();

        // Get similar items
        $similarItems = MarketplaceItem::approved()
            ->active()
            ->where('category_id', $item->category_id)
            ->where('id', '!=', $item->id)
            ->orderByDesc('average_rating')
            ->limit(4)
            ->get();

        return view('marketplace.show', compact('item', 'hasPurchased', 'moreFromCreator', 'similarItems'));
    }

    /**
     * Display category items
     */
    public function category(MarketplaceCategory $category, Request $request)
    {
        if (!$category->active) {
            abort(404);
        }

        $query = MarketplaceItem::approved()
            ->active()
            ->where('category_id', $category->id)
            ->with(['creator', 'category']);

        // Apply sorting
        $sort = $request->get('sort', 'popular');
        switch ($sort) {
            case 'newest':
                $query->orderByDesc('created_at');
                break;
            case 'rating':
                $query->orderByDesc('average_rating')->orderByDesc('reviews_count');
                break;
            case 'price_low':
                $query->orderBy('price');
                break;
            case 'price_high':
                $query->orderByDesc('price');
                break;
            case 'popular':
            default:
                $query->orderByDesc('purchases_count')->orderByDesc('downloads_count');
                break;
        }

        $items = $query->paginate(24)->withQueryString();

        return view('marketplace.category', compact('category', 'items'));
    }

    /**
     * Download purchased item
     */
    public function download(MarketplaceItem $item)
    {
        if (!Auth::check()) {
            abort(403, 'You must be logged in to download items.');
        }

        $user = Auth::user();

        // Check if item is free or user has purchased it
        if (!$item->is_free && !$item->isPurchasedBy($user)) {
            abort(403, 'You must purchase this item before downloading.');
        }

        // Get purchase if exists
        $purchase = $item->purchases()
            ->where('user_id', $user->id)
            ->where('payment_status', 'completed')
            ->first();

        if (!$item->is_free && !$purchase) {
            abort(403, 'No valid purchase found for this item.');
        }

        // Log the download
        \App\Models\Marketplace\MarketplaceDownloadLog::create([
            'user_id' => $user->id,
            'marketplace_item_id' => $item->id,
            'purchase_id' => $purchase->id ?? null,
            'version_downloaded' => $item->current_version,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Increment download count
        $item->incrementDownloads();

        // Return file download
        return response()->download(
            storage_path('app/private/' . $item->file_path),
            $item->name . '-v' . $item->current_version . '.zip'
        );
    }
}
