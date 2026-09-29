<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductCategoryRequest;
use App\Http\Requests\UpdateProductCategoryRequest;
use App\Http\Resources\ProductCategoryResource;
use App\Models\ProductCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductCategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $categories = ProductCategory::query()
            ->withCount('products')
            ->latest()
            ->paginate(10);

        return ProductCategoryResource::collection($categories);
    }

    public function store(
        StoreProductCategoryRequest $request
    ): ProductCategoryResource {
        $category = ProductCategory::create(
            $request->validated()
        );

        return new ProductCategoryResource($category);
    }

    public function show(
        ProductCategory $productCategory
    ): ProductCategoryResource {
        $productCategory->loadCount('products');

        return new ProductCategoryResource($productCategory);
    }

    public function update(
        UpdateProductCategoryRequest $request,
        ProductCategory $productCategory
    ): ProductCategoryResource {
        $productCategory->update(
            $request->validated()
        );

        $productCategory->loadCount('products');

        return new ProductCategoryResource($productCategory);
    }

    public function destroy(
        ProductCategory $productCategory
    ): JsonResponse {
        if ($productCategory->products()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori tidak dapat dihapus karena masih digunakan oleh produk.',
            ], 422);
        }

        $productCategory->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kategori produk berhasil dihapus.',
        ]);
    }
}
