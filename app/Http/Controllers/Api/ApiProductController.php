<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\HostingProduct;
use Illuminate\Http\Request;

class ApiProductController extends Controller
{
    /**
     * Get all products
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $type = $request->input('type');

        $query = HostingProduct::query();

        // Filter by type
        if ($type) {
            $query->where('type', $type);
        }

        // Only show active products
        $query->where('is_active', true);

        // Order by name
        $query->orderBy('name');

        $products = $query->paginate($perPage);

        return ProductResource::collection($products);
    }

    /**
     * Get single product
     */
    public function show($id)
    {
        $product = HostingProduct::where('is_active', true)->findOrFail($id);

        return new ProductResource($product);
    }
}
