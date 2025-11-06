<?php

namespace App\Http\Controllers;

use App\Models\Marketplace\MarketplaceCategory;
use App\Models\Marketplace\MarketplaceCreatorProfile;
use App\Models\Marketplace\MarketplaceItem;
use App\Models\Marketplace\MarketplaceItemVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MarketplaceCreatorController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Creator dashboard
     */
    public function dashboard()
    {
        $user = Auth::user();

        // Get or create creator profile
        $profile = MarketplaceCreatorProfile::firstOrCreate(
            ['user_id' => $user->id]
        );

        // Update balances
        $profile->updateBalances();

        $items = MarketplaceItem::where('user_id', $user->id)
            ->withCount(['purchases', 'reviews', 'downloads' => function ($query) {
                $query->distinct('user_id');
            }])
            ->orderByDesc('created_at')
            ->get();

        $totalEarnings = $profile->total_earnings;
        $availableBalance = $profile->available_balance;
        $pendingBalance = $profile->pending_balance;
        $totalSales = $profile->total_sales;

        return view('marketplace.creator.dashboard', compact(
            'profile',
            'items',
            'totalEarnings',
            'availableBalance',
            'pendingBalance',
            'totalSales'
        ));
    }

    /**
     * Show create item form
     */
    public function create()
    {
        $categories = MarketplaceCategory::where('active', true)
            ->orderBy('name')
            ->get();

        return view('marketplace.creator.create', compact('categories'));
    }

    /**
     * Store new item
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:marketplace_items,name',
            'category_id' => 'required|exists:marketplace_categories,id',
            'short_description' => 'required|string|max:500',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0|max:9999.99',
            'is_free' => 'boolean',
            'file' => 'required|file|mimes:zip|max:102400', // Max 100MB
            'icon' => 'nullable|image|max:2048',
            'banner' => 'nullable|image|max:5120',
            'screenshots.*' => 'nullable|image|max:5120',
            'demo_url' => 'nullable|url',
            'documentation_url' => 'nullable|url',
            'repository_url' => 'nullable|url',
            'minimum_php_version' => 'nullable|string',
            'minimum_laravel_version' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $slug = Str::slug($validated['name']);

            // Handle file upload
            $filePath = $request->file('file')->store('marketplace/items', 'private');
            $fileSize = $request->file('file')->getSize();

            // Handle images
            $iconPath = $request->hasFile('icon')
                ? $request->file('icon')->store('marketplace/icons', 'public')
                : null;

            $bannerPath = $request->hasFile('banner')
                ? $request->file('banner')->store('marketplace/banners', 'public')
                : null;

            $screenshots = [];
            if ($request->hasFile('screenshots')) {
                foreach ($request->file('screenshots') as $screenshot) {
                    $screenshots[] = $screenshot->store('marketplace/screenshots', 'public');
                }
            }

            // Create item
            $item = MarketplaceItem::create([
                'user_id' => Auth::id(),
                'category_id' => $validated['category_id'],
                'name' => $validated['name'],
                'slug' => $slug,
                'short_description' => $validated['short_description'],
                'description' => $validated['description'],
                'price' => $validated['price'],
                'is_free' => $request->boolean('is_free'),
                'file_path' => $filePath,
                'file_size' => $fileSize,
                'icon' => $iconPath,
                'banner' => $bannerPath,
                'screenshots' => $screenshots,
                'demo_url' => $validated['demo_url'] ?? null,
                'documentation_url' => $validated['documentation_url'] ?? null,
                'repository_url' => $validated['repository_url'] ?? null,
                'minimum_php_version' => $validated['minimum_php_version'] ?? null,
                'minimum_laravel_version' => $validated['minimum_laravel_version'] ?? null,
                'current_version' => '1.0.0',
                'status' => 'draft',
            ]);

            // Create initial version
            MarketplaceItemVersion::create([
                'marketplace_item_id' => $item->id,
                'version' => '1.0.0',
                'changelog' => 'Initial release',
                'file_path' => $filePath,
                'file_size' => $fileSize,
                'is_current' => true,
            ]);

            DB::commit();

            return redirect()->route('marketplace.creator.edit', $item)
                ->with('success', 'Item created successfully! Complete the details and submit for review.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create marketplace item', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Failed to create item. Please try again.');
        }
    }

    /**
     * Show edit form
     */
    public function edit(MarketplaceItem $item)
    {
        if ($item->user_id !== Auth::id()) {
            abort(403);
        }

        $categories = MarketplaceCategory::where('active', true)
            ->orderBy('name')
            ->get();

        return view('marketplace.creator.edit', compact('item', 'categories'));
    }

    /**
     * Update item
     */
    public function update(Request $request, MarketplaceItem $item)
    {
        if ($item->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:marketplace_items,name,' . $item->id,
            'category_id' => 'required|exists:marketplace_categories,id',
            'short_description' => 'required|string|max:500',
            'description' => 'required|string',
            'installation_instructions' => 'nullable|string',
            'price' => 'required|numeric|min:0|max:9999.99',
            'is_free' => 'boolean',
            'file' => 'nullable|file|mimes:zip|max:102400',
            'icon' => 'nullable|image|max:2048',
            'banner' => 'nullable|image|max:5120',
            'screenshots.*' => 'nullable|image|max:5120',
            'demo_url' => 'nullable|url',
            'documentation_url' => 'nullable|url',
            'repository_url' => 'nullable|url',
            'minimum_php_version' => 'nullable|string',
            'minimum_laravel_version' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $updateData = [
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']),
                'category_id' => $validated['category_id'],
                'short_description' => $validated['short_description'],
                'description' => $validated['description'],
                'installation_instructions' => $validated['installation_instructions'] ?? null,
                'price' => $validated['price'],
                'is_free' => $request->boolean('is_free'),
                'demo_url' => $validated['demo_url'] ?? null,
                'documentation_url' => $validated['documentation_url'] ?? null,
                'repository_url' => $validated['repository_url'] ?? null,
                'minimum_php_version' => $validated['minimum_php_version'] ?? null,
                'minimum_laravel_version' => $validated['minimum_laravel_version'] ?? null,
            ];

            // Handle new file upload
            if ($request->hasFile('file')) {
                $filePath = $request->file('file')->store('marketplace/items', 'private');
                $fileSize = $request->file('file')->getSize();

                $updateData['file_path'] = $filePath;
                $updateData['file_size'] = $fileSize;
            }

            // Handle icon upload
            if ($request->hasFile('icon')) {
                if ($item->icon) {
                    Storage::disk('public')->delete($item->icon);
                }
                $updateData['icon'] = $request->file('icon')->store('marketplace/icons', 'public');
            }

            // Handle banner upload
            if ($request->hasFile('banner')) {
                if ($item->banner) {
                    Storage::disk('public')->delete($item->banner);
                }
                $updateData['banner'] = $request->file('banner')->store('marketplace/banners', 'public');
            }

            // Handle screenshots
            if ($request->hasFile('screenshots')) {
                $screenshots = $item->screenshots ?? [];
                foreach ($request->file('screenshots') as $screenshot) {
                    $screenshots[] = $screenshot->store('marketplace/screenshots', 'public');
                }
                $updateData['screenshots'] = $screenshots;
            }

            $item->update($updateData);

            DB::commit();

            return back()->with('success', 'Item updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update marketplace item', [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Failed to update item. Please try again.');
        }
    }

    /**
     * Submit item for review
     */
    public function submitForReview(MarketplaceItem $item)
    {
        if ($item->user_id !== Auth::id()) {
            abort(403);
        }

        if ($item->status !== 'draft' && $item->status !== 'rejected') {
            return back()->with('error', 'Item cannot be submitted in current status.');
        }

        $item->submitForReview();

        // TODO: Notify admins about new submission

        return back()->with('success', 'Item submitted for review! You will be notified once it is approved.');
    }

    /**
     * Delete item
     */
    public function destroy(MarketplaceItem $item)
    {
        if ($item->user_id !== Auth::id()) {
            abort(403);
        }

        // Can only delete draft items
        if ($item->status !== 'draft') {
            return back()->with('error', 'Only draft items can be deleted.');
        }

        try {
            // Delete files
            if ($item->file_path) {
                Storage::disk('private')->delete($item->file_path);
            }
            if ($item->icon) {
                Storage::disk('public')->delete($item->icon);
            }
            if ($item->banner) {
                Storage::disk('public')->delete($item->banner);
            }
            if ($item->screenshots) {
                foreach ($item->screenshots as $screenshot) {
                    Storage::disk('public')->delete($screenshot);
                }
            }

            $item->delete();

            return redirect()->route('marketplace.creator.dashboard')
                ->with('success', 'Item deleted successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to delete marketplace item', [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to delete item. Please try again.');
        }
    }

    /**
     * Show item analytics
     */
    public function analytics(MarketplaceItem $item)
    {
        if ($item->user_id !== Auth::id()) {
            abort(403);
        }

        $item->load(['purchases', 'earnings', 'reviews', 'downloadLogs']);

        // Get purchase trends
        $purchasesByMonth = $item->purchases()
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Get earnings by month
        $earningsByMonth = $item->earnings()
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(amount) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return view('marketplace.creator.analytics', compact('item', 'purchasesByMonth', 'earningsByMonth'));
    }
}
