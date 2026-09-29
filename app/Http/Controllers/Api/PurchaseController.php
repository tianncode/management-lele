<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePurchaseRequest;
use App\Http\Requests\UpdatePurchaseRequest;
use App\Http\Resources\PurchaseResource;
use App\Models\Purchase;
use App\Services\PurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PurchaseController extends Controller
{
    public function __construct(
        protected PurchaseService $purchaseService
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $purchases = Purchase::query()
            ->with([
                'supplier',
                'items.product',
            ])
            ->latest('purchase_date')
            ->paginate(15);

        return PurchaseResource::collection($purchases);
    }

    public function store(
        StorePurchaseRequest $request
    ): PurchaseResource {
        $purchase = $this->purchaseService->create(
            $request->validated()
        );

        return new PurchaseResource($purchase);
    }

    public function show(
        Purchase $purchase
    ): PurchaseResource {
        $purchase->load([
            'supplier',
            'items.product',
        ]);

        return new PurchaseResource($purchase);
    }

    public function update(
        UpdatePurchaseRequest $request,
        Purchase $purchase
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => 'Update purchase akan diaktifkan setelah mekanisme koreksi stok dan transaksi keuangan selesai.',
        ], 422);
    }

    public function destroy(
        Purchase $purchase
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => 'Penghapusan purchase akan diaktifkan setelah mekanisme koreksi stok dan transaksi keuangan selesai.',
        ], 422);
    }
}
