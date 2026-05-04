<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
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

        $products = $query->paginate(10);

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

        return ResponseHelper::jsonResponse($product, 'Product created successfully', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        return ResponseHelper::jsonResponse($product->load('category'), 'Product retrieved successfully');
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

        return ResponseHelper::jsonResponse(null, 'Product deleted successfully');
    }
}
