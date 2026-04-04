<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\User\Product;
use App\Models\User\Product\Category as ProductCategory;
use App\Models\User\Product\Image as ProductImage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /**
     * Display a listing of the user's products.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        $query = Product::where('user_id', $user->id)
            ->with(['images' => function ($query) {
                $query->ordered();
            }]);

        // Filter by status if provided
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        // Filter by category if provided
        if ($request->has('category_id') && !empty($request->category_id)) {
            $query->where('category_id', $request->category_id);
        }

        // Search by name or description
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sort options
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');

        $allowedSortFields = ['name', 'price', 'created_at', 'updated_at', 'status'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        $products = $query->paginate($request->input('per_page', 5));

        return response()->json([
            'data' => $products,
            'message' => 'User products',
            'success' => true,
        ]);
    }

    /**
     * Store a newly created product.
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0|max:999999.99',
            'category_id' => [
                //'nullable',
                'integer',
                Rule::exists(ProductCategory::class, 'id')->where(function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                }),
            ],
            'status' => ['required', Rule::in(['active', 'inactive', 'draft'])],
            'stock_quantity' => 'required|integer|min:0',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $product = Product::create([
            'user_id' => $user->id,
            'category_id' => $request->category_id,
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'status' => $request->status,
            'stock_quantity' => $request->stock_quantity,
        ]);

        // Handle image uploads
        if ($request->hasFile('images')) {
            $this->handleImageUploads($product, $request->file('images'));
        }

        $product->load(['images' => function ($query) {
            $query->ordered();
        }]);

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
            'data' => $product,
        ], 201);
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product): JsonResponse
    {
        // Check if the product belongs to the authenticated user
        if ($product->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $product->load(['images' => function ($query) {
            $query->ordered();
        }]);

        return response()->json([
            'success' => true,
            'data' => $product,
        ]);
    }

    /**
     * Update the specified product.
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        // Check if the product belongs to the authenticated user
        if ($product->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0|max:999999.99',
            'category_id' => [
                //'nullable',
                'integer',
                Rule::exists(ProductCategory::class, 'id')->where(function ($query) use ($product) {
                    $query->where('user_id', $product->user_id);
                }),
            ],
            'status' => ['sometimes', 'required', Rule::in(['active', 'inactive', 'draft'])],
            'stock_quantity' => 'sometimes|required|integer|min:0',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $product->update($request->only([
            'name', 'description', 'price', 'category_id', 'status', 'stock_quantity'
        ]));

        // Handle image uploads
        if ($request->hasFile('images')) {
            $this->handleImageUploads($product, $request->file('images'));
        }

        $product->load(['images' => function ($query) {
            $query->ordered();
        }]);

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
            'data' => $product,
        ]);
    }

    /**
     * Remove the specified product.
     */
    public function destroy(Product $product): JsonResponse
    {
        // Check if the product belongs to the authenticated user
        if ($product->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Delete associated images (this will also delete files via model event)
        $product->images()->delete();

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully',
        ]);
    }

    /**
     * Handle image uploads for a product.
     */
    private function handleImageUploads(Product $product, array $images): void
    {
        $sortOrder = $product->images()->max('sort_order') ?? 0;

        foreach ($images as $image) {
            $sortOrder++;

            // Generate unique filename
            $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();

            // Store the image
            $path = $image->storeAs('products', $filename, 'public');

            // Create database record
            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $path,
                'image_name' => $image->getClientOriginalName(),
                'mime_type' => $image->getMimeType(),
                'file_size' => $image->getSize(),
                'sort_order' => $sortOrder,
                'is_primary' => $sortOrder === 1, // First image is primary by default
                'metadata' => [
                    'width' => null, // Could be populated with image dimensions
                    'height' => null,
                ],
            ]);
        }
    }

    /**
     * Delete a specific product image.
     */
    public function deleteImage(Product $product, ProductImage $image): JsonResponse
    {
        // Check if the product belongs to the authenticated user
        if ($product->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Check if the image belongs to the product
        if ($image->product_id !== $product->id) {
            return response()->json([
                'success' => false,
                'message' => 'Image does not belong to this product',
            ], 403);
        }

        $image->delete(); // This will also delete the file via model event

        return response()->json([
            'success' => true,
            'message' => 'Image deleted successfully',
        ]);
    }

    /**
     * Update image sort order.
     */
    public function updateImageOrder(Request $request, Product $product): JsonResponse
    {
        // Check if the product belongs to the authenticated user
        if ($product->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'images' => 'required|array',
            'images.*.id' => 'required|integer|exists:user_product_images,id',
            'images.*.sort_order' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        foreach ($request->images as $imageData) {
            ProductImage::where('id', $imageData['id'])
                ->where('product_id', $product->id)
                ->update(['sort_order' => $imageData['sort_order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Image order updated successfully',
        ]);
    }

    /**
     * Set primary image for a product.
     */
    public function setPrimaryImage(Request $request, Product $product): JsonResponse
    {
        // Check if the product belongs to the authenticated user
        if ($product->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'image_id' => 'required|integer|exists:user_product_images,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check if the image belongs to the product
        $image = ProductImage::where('id', $request->image_id)
            ->where('product_id', $product->id)
            ->first();

        if (!$image) {
            return response()->json([
                'success' => false,
                'message' => 'Image does not belong to this product',
            ], 403);
        }

        // Reset all images to non-primary
        $product->images()->update(['is_primary' => false]);

        // Set the selected image as primary
        $image->update(['is_primary' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Primary image updated successfully',
        ]);
    }
}
