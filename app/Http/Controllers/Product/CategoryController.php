<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Facades\Gate;
use Illuminate\Database\QueryException;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (!Gate::allows('read')) {
            return response()->json(['message' => 'You are not authorized for this activity'], 403);
        }

        $categories = Category::all();
    

        if($categories) {
            return response()->json([
                'message' => 'Supplier created successfully!',
                'data' => $categories
            ]);
        }
    }
    

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        // sleep(5);
        if (!Gate::allows('create')) {
            return response()->json(['message' => 'You are not authorized for this activity'], 403);
        }

        $validated = $request->validate([
            'cat_name' => 'required|string|max:255|unique:category,cat_name',
            'cat_desc' => 'nullable|string',
            // 'cat_hireable' => 'required|boolean',
            // 'cat_slug' => 'required|string|unique:categories,cat_slug',
            // 'cat_status' => 'required|in:active,inactive',
        ]);

        $category = Category::create($validated);

        return response()->json([
            'message' => 'Category successfully created',
            'data' => $category], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        \Log::info("category: " . $category);
        return $category;
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        if (!Gate::allows('edit')) {
            return response()->json(['message' => 'You are not authorized for this activity'], 403);
        }

        $validated = $request->validate([
            'cat_name' => 'sometimes|required|string|max:255',
            'cat_desc' => 'nullable|string',
            // 'cat_hireable' => 'sometimes|required|boolean',
            // 'cat_slug' => 'sometimes|required|string|unique:categories,cat_slug,' . $category->id,
            // 'cat_status' => 'sometimes|required|in:active,inactive',
        ]);

        $category->update($validated);

        return response()->json([
            'message' => 'Category successfully updated',
            'data' => $category
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        if (!Gate::allows('delete')) {
            return response()->json(['message' => 'You are not authorized for this activity'], 403);
        }

        // $category->delete();

        // return response()->json(['message' => 'Category deleted successfully']);

        try {
            $category->delete();

            return response()->json(['message' => 'Category deleted successfully'], 200);
        } catch (QueryException $e) {
            // Check if it's a foreign key constraint violation (MySQL error code 1451, PostgreSQL 23503)
            if ($e->getCode() == '23000' || $e->getCode() == '23503') {
                return response()->json([
                    'message' => 'Cannot delete this category because it is referenced by other records.',
                    'error' => $e->getMessage()
                ], 409); // 409 Conflict is appropriate for this case
            }

            // Handle other database errors
            return response()->json([
                'message' => 'An error occurred while deleting the category.',
                'error' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            // Handle any other unexpected errors
            return response()->json([
                'message' => 'An unexpected error occurred.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
