<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use Illuminate\Http\Request;

class AdminCategoryController extends Controller
{
    /**
     * Display a listing of the categories.
     */
    public function index(Request $request)
    {
        $categories = Category::withCount('products')
            ->orderBy('name', 'asc')
            ->get();

        $totalCategories = $categories->count();
        $totalProductsInCategories = $categories->sum('products_count');
        $topCategory = $categories->sortByDesc('products_count')->first();

        return view('admin.categories.index', compact('categories', 'totalCategories', 'totalProductsInCategories', 'topCategory'));
    }

    /**
     * Store a newly created category in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'code' => 'nullable|string|max:50|unique:categories,code',
            'description' => 'nullable|string|max:1000',
        ], [
            'name.required' => 'The category name is required.',
            'name.unique' => 'A category with this name already exists.',
            'code.unique' => 'This category code is already taken.',
        ]);

        $category = Category::create([
            'name' => trim($validated['name']),
            'code' => !empty($validated['code']) ? strtoupper(trim($validated['code'])) : null,
            'description' => !empty($validated['description']) ? trim($validated['description']) : null,
        ]);

        AuditLog::log(
            'category_created',
            Category::class,
            $category->id,
            null,
            $category->toArray(),
            "Created category: {$category->name}" . ($category->code ? " ({$category->code})" : "")
        );

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Category '{$category->name}' created successfully!",
                'category' => $category,
            ]);
        }

        return redirect()->route('admin.categories.index')->with('success', "Category '{$category->name}' created successfully!");
    }

    /**
     * Update the specified category in storage.
     */
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
            'code' => 'nullable|string|max:50|unique:categories,code,' . $category->id,
            'description' => 'nullable|string|max:1000',
        ], [
            'name.required' => 'The category name is required.',
            'name.unique' => 'Another category is already using this name.',
            'code.unique' => 'Another category is already using this code.',
        ]);

        $oldValues = $category->toArray();

        $category->update([
            'name' => trim($validated['name']),
            'code' => !empty($validated['code']) ? strtoupper(trim($validated['code'])) : null,
            'description' => !empty($validated['description']) ? trim($validated['description']) : null,
        ]);

        AuditLog::log(
            'category_updated',
            Category::class,
            $category->id,
            $oldValues,
            $category->toArray(),
            "Updated category: {$category->name}"
        );

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Category '{$category->name}' updated successfully!",
                'category' => $category,
            ]);
        }

        return redirect()->route('admin.categories.index')->with('success', "Category '{$category->name}' updated successfully!");
    }

    /**
     * Remove the specified category from storage.
     */
    public function destroy(Request $request, Category $category)
    {
        $productsCount = $category->products()->count();

        if ($productsCount > 0) {
            $msg = "Cannot delete category '{$category->name}' because it currently has {$productsCount} linked product(s). Please reassign or update those products first.";
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                ], 422);
            }

            return back()->with('error', $msg);
        }

        $oldValues = $category->toArray();
        $name = $category->name;

        $category->delete();

        AuditLog::log(
            'category_deleted',
            Category::class,
            $oldValues['id'] ?? null,
            $oldValues,
            null,
            "Deleted category: {$name}"
        );

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Category '{$name}' deleted successfully!",
            ]);
        }

        return redirect()->route('admin.categories.index')->with('success', "Category '{$name}' deleted successfully!");
    }
}
