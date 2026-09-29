<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSaleRequest;
use App\Http\Requests\UpdateSaleRequest;
use App\Http\Resources\SaleResource;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SaleController extends Controller
{
    public function __construct(
        protected SaleService $saleService
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $sales = Sale::query()
            ->with([
                'customer',
                'items.harvest',
            ])
            ->latest('sale_date')
            ->paginate(15);

        return SaleResource::collection($sales);
    }

    public function store(
        StoreSaleRequest $request
    ): SaleResource {
        $sale = $this->saleService->create(
            $request->validated()
        );

        return new SaleResource($sale);
    }

    public function show(
        Sale $sale
    ): SaleResource {
        $sale->load([
            'customer',
            'items.harvest',
        ]);

        return new SaleResource($sale);
    }

    public function update(
        UpdateSaleRequest $request,
        Sale $sale
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => 'Update sale akan diaktifkan setelah mekanisme koreksi stok dan transaksi keuangan selesai.',
        ], 422);
    }

    public function destroy(
        Sale $sale
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => 'Penghapusan sale akan diaktifkan setelah mekanisme koreksi stok dan transaksi keuangan selesai.',
        ], 422);
    }
}
