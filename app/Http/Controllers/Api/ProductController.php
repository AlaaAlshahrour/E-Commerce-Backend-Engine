<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Build base query
        $query = Product::with('category');

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->min_price) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->max_price) {
            $query->where('price', '<=', $request->max_price);
        }

        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        // Cache Aside
        // Generate cache key from relevant filters (page, category_id, min_price, max_price, search)
        $filters = [
            'page' => $request->get('page', 1),
            'category_id' => $request->get('category_id'),
            'min_price' => $request->get('min_price'),
            'max_price' => $request->get('max_price'),
            'search' => $request->get('search'),
        ];

        $cacheKey = 'products:list:' . md5(json_encode($filters));

        // TTL: 5 minutes
        $products = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($query, $cacheKey) {
            $paginator = $query->paginate(10);

            try {
                Redis::connection(config('cache.stores.redis.connection'))
                    ->sadd('products:list:keys', $cacheKey);
            } catch (\Exception $e) {
                // If Redis tracking fails, silently continue to avoid breaking the API
            }

            $items = array_map(function ($model) {
                return $model->toArray();
            }, $paginator->items());

            return [
                'data' => $items,
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ];
        });

        // Cache Hit or Miss returns from Cache::remember
        return ResponseHelper::jsonResponse($products, 'Products retrieved successfully');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $data['photo_url'] = Storage::url($path);
        }

        unset($data['image']);

        $product = Product::create($data);
        Inventory::create([
            'product_id' => $product->id,
            'quantity' => 0,
        ]);

        // Cache Invalidation
        $this->clearProductListCache();

        return ResponseHelper::jsonResponse($product, 'Product created successfully', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        // Cache Aside for product detail
        $cacheKey = 'product:' . $product->id;

        // TTL: 10 minutes
        $productData = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($product) {
            return $product->load('category')->toArray();
        });

        return ResponseHelper::jsonResponse($productData, 'Product retrieved successfully');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($product->photo_url) {
                $oldPath = str_replace('/storage/', '', $product->photo_url);
                Storage::disk('public')->delete($oldPath);
            }

            $path = $request->file('image')->store('products', 'public');
            $data['photo_url'] = Storage::url($path);
        }

        unset($data['image']);

        $product->update($data);

        // Cache Invalidation
        Cache::forget("product:{$product->id}");
        $this->clearProductListCache();

        return ResponseHelper::jsonResponse($product, 'Product updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        if ($product->photo_url) {
            $oldPath = str_replace('/storage/', '', $product->photo_url);
            Storage::disk('public')->delete($oldPath);
        }

        $product->delete();

        // Cache Invalidation
        Cache::forget("product:{$product->id}");
        $this->clearProductListCache();

        return ResponseHelper::jsonResponse(null, 'Product deleted successfully');
    }

    /**
     * Clear product listing cache keys stored in Redis set 'products:list:keys'
     */
    private function clearProductListCache(): void
    {
        try {
            $redis = Redis::connection(config('cache.stores.redis.connection'));
            $keys = $redis->smembers('products:list:keys') ?: [];
            foreach ($keys as $key) {
                Cache::forget($key);
            }
            $redis->del('products:list:keys');
        } catch (\Exception $e) {
            // Ignore errors to ensure availability
        }
    }
}
