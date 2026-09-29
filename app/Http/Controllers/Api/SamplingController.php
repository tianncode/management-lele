<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSamplingRequest;
use App\Http\Requests\UpdateSamplingRequest;
use App\Http\Resources\SamplingResource;
use App\Models\Sampling;
use App\Services\SamplingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SamplingController extends Controller
{
    public function __construct(
        protected SamplingService $samplingService
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $samplings = Sampling::query()
            ->with('fishCycle')
            ->latest('sampling_date')
            ->paginate(15);

        return SamplingResource::collection($samplings);
    }

    public function store(
        StoreSamplingRequest $request
    ): SamplingResource {
        $sampling = $this->samplingService->create(
            $request->validated()
        );

        $sampling->load('fishCycle');

        return new SamplingResource($sampling);
    }

    public function show(
        Sampling $sampling
    ): SamplingResource {
        $sampling->load('fishCycle');

        return new SamplingResource($sampling);
    }

    public function update(
        UpdateSamplingRequest $request,
        Sampling $sampling
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => 'Update sampling akan diaktifkan setelah mekanisme koreksi data sampling selesai.',
        ], 422);
    }

    public function destroy(
        Sampling $sampling
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => 'Penghapusan sampling akan diaktifkan setelah mekanisme koreksi data sampling selesai.',
        ], 422);
    }
}
