<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $products = Product::query()
            ->with('category')
            ->latest()
            ->paginate(10);

        return ProductResource::collection($products);
    }

    public function store(StoreProductRequest $request): ProductResource
    {
        $product = Product::create(
            $request->validated()
        );

        $product->load('category');

        return new ProductResource($product);
    }

    public function show(Product $product): ProductResource
    {
        $product->load('category');

        return new ProductResource($product);
    }

    public function update(
        UpdateProductRequest $request,
        Product $product
    ): ProductResource {
        $product->update(
            $request->validated()
        );

        $product->load('category');

        return new ProductResource($product);
    }

    public function destroy(Product $product): JsonResponse
    {
        if ($product->stockMovements()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak dapat dihapus karena sudah memiliki riwayat stok.',
            ], 422);
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil dihapus.',
        ]);
    }
}
