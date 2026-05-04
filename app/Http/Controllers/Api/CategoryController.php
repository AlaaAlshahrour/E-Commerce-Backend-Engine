<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = Category::all();
        return ResponseHelper::jsonResponse($categories, 'Categories retrieved successfully');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoryRequest $request)
    {
        $category = Category::create($request->validated());
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
        return ResponseHelper::jsonResponse($category, 'Category updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        $category->delete();
        return ResponseHelper::jsonResponse(null, 'Category deleted successfully');
    }
}
