<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = Cache::remember('categories:all', now()->addHour(), function () {
            return Category::all()->toArray();
        });

        return ResponseHelper::jsonResponse($categories, 'Categories retrieved successfully');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoryRequest $request)
    {
        $category = Category::create($request->validated());

        // Cache Invalidation
        Cache::forget('categories:all');
        $this->clearProductListCache();

        return ResponseHelper::jsonResponse($category, 'Category created successfully', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        return ResponseHelper::jsonResponse($category, 'Category retrieved successfully');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $category->update($request->validated());

        // Cache Invalidation
        Cache::forget('categories:all');
        $this->clearProductListCache();

        return ResponseHelper::jsonResponse($category, 'Category updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        $category->delete();

        // Cache Invalidation
        Cache::forget('categories:all');
        $this->clearProductListCache();
        return ResponseHelper::jsonResponse(null, 'Category deleted successfully');
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
