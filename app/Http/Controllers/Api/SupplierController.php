<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SupplierController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $suppliers = Supplier::query()
            ->latest()
            ->paginate(10);

        return SupplierResource::collection($suppliers);
    }

    public function store(
        StoreSupplierRequest $request
    ): SupplierResource {
        $supplier = Supplier::create(
            $request->validated()
        );

        return new SupplierResource($supplier);
    }

    public function show(
        Supplier $supplier
    ): SupplierResource {
        return new SupplierResource($supplier);
    }

    public function update(
        UpdateSupplierRequest $request,
        Supplier $supplier
    ): SupplierResource {
        $supplier->update(
            $request->validated()
        );

        return new SupplierResource(
            $supplier->fresh()
        );
    }

    public function destroy(
        Supplier $supplier
    ): JsonResponse {
        if ($supplier->purchases()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Supplier tidak dapat dihapus karena sudah memiliki transaksi pembelian.',
            ], 422);
        }

        $supplier->delete();

        return response()->json([
            'success' => true,
            'message' => 'Supplier berhasil dihapus.',
        ]);
    }
}
