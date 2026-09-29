<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHarvestRequest;
use App\Http\Requests\UpdateHarvestRequest;
use App\Http\Resources\HarvestResource;
use App\Models\Harvest;
use App\Services\HarvestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class HarvestController extends Controller
{
    public function __construct(
        protected HarvestService $harvestService
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $harvests = Harvest::query()
            ->with('fishCycle')
            ->latest('harvest_date')
            ->paginate(15);

        return HarvestResource::collection($harvests);
    }

    public function store(
        StoreHarvestRequest $request
    ): HarvestResource {
        $harvest = $this->harvestService->create(
            $request->validated()
        );

        return new HarvestResource($harvest);
    }

    public function show(
        Harvest $harvest
    ): HarvestResource {
        $harvest->load('fishCycle');

        return new HarvestResource($harvest);
    }

    public function update(
        UpdateHarvestRequest $request,
        Harvest $harvest
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => 'Update harvest akan diaktifkan setelah mekanisme koreksi stok dan transaksi penjualan selesai.',
        ], 422);
    }

    public function destroy(
        Harvest $harvest
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => 'Penghapusan harvest akan diaktifkan setelah mekanisme koreksi stok dan transaksi penjualan selesai.',
        ], 422);
    }
}
