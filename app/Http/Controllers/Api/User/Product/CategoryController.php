<?php

namespace App\Http\Controllers\Api\User\Product;

use App\Http\Controllers\Controller;
use App\Models\User\Product\Category as ProductCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    /**
     * Display a listing of the authenticated user's product categories.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        $query = ProductCategory::query()
            ->where('user_id', $user->id)
            ->withCount('products');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->input('sort_by', 'sort_order');
        $sortDirection = $request->input('sort_direction', 'asc');
        $allowedSortFields = ['name', 'sort_order', 'created_at', 'updated_at'];

        if (!in_array($sortDirection, ['asc', 'desc'], true)) {
            $sortDirection = 'asc';
        }

        if (in_array($sortBy, $allowedSortFields, true)) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->ordered();
        }

        if ($sortBy !== 'sort_order') {
            $query->orderBy('sort_order')->orderBy('name');
        }

        $categories = $query->paginate($request->integer('per_page', 10));

        return response()->json([
            'success' => true,
            'message' => 'User product categories',
            'data' => $categories,
        ]);
    }

    /**
     * Store a newly created category.
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:user_product_categories,name,NULL,id,user_id,' . $user->id,
            'description' => 'nullable|string',
            'sort_order' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $category = ProductCategory::create([
            'user_id' => $user->id,
            'name' => $request->name,
            'description' => $request->description,
            'sort_order' => $request->integer('sort_order'),
        ])->loadCount('products');

        return response()->json([
            'success' => true,
            'message' => 'Product category created successfully',
            'data' => $category,
        ], 201);
    }

    /**
     * Display the specified category.
     */
    public function show(int $id): JsonResponse
    {
        $productCategory = ProductCategory::find($id);

        if (!$productCategory) {
            return response()->json([
                'success' => false,
                'message' => 'Product category not found',
            ], 404);
        }

        if ($productCategory->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $productCategory->loadCount('products');

        return response()->json([
            'success' => true,
            'data' => $productCategory,
        ]);
    }

    /**
     * Update the specified category.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $productCategory = ProductCategory::find($id);

        if (!$productCategory) {
            return response()->json([
                'success' => false,
                'message' => 'Product category not found',
            ], 404);
        }

        if ($productCategory->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => [
                //'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('user_product_categories', 'name')
                    ->ignore($productCategory->id)
                    ->where(function ($query) use ($productCategory) {
                        $query->where('user_id', $productCategory->user_id);
                    }),
            ],
            'description' => 'nullable|string',
            'sort_order' => 'sometimes|required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $productCategory->update($request->only([
            'name',
            'description',
            'sort_order',
        ]));

        $productCategory->loadCount('products');

        return response()->json([
            'success' => true,
            'message' => 'Product category updated successfully',
            'data' => $productCategory,
        ]);
    }

    /**
     * Remove the specified category.
     */
    public function destroy(int $id): JsonResponse
    {
        $productCategory = ProductCategory::find($id);

        if (!$productCategory) {
            return response()->json([
                'success' => false,
                'message' => 'Product category not found',
            ], 404);
        }

        if ($productCategory->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Keep products intact by clearing the category assignment before deletion.
        $productCategory->products()->update(['category_id' => null]);
        $productCategory->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product category deleted successfully',
        ]);
    }

    public function list(Request $request): JsonResponse
    {
        $user = Auth::user();

        $query = ProductCategory::query()
            ->where('user_id', $user->id);

        $sortBy = 'sort_order';
        $sortDirection = 'asc';

        $query->orderBy($sortBy, $sortDirection);

        $categories = $query->get();

        return response()->json([
            'success' => true,
            'message' => 'User product category list',
            'data' => $categories,
        ]);
    }

}
