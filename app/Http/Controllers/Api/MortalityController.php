<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMortalityRequest;
use App\Http\Requests\UpdateMortalityRequest;
use App\Http\Resources\MortalityResource;
use App\Models\Mortality;
use App\Services\MortalityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MortalityController extends Controller
{
    public function __construct(
        protected MortalityService $mortalityService
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $mortalities = Mortality::query()
            ->with('fishCycle')
            ->latest('mortality_date')
            ->paginate(15);

        return MortalityResource::collection($mortalities);
    }

    public function store(
        StoreMortalityRequest $request
    ): MortalityResource {
        $mortality = $this->mortalityService->create(
            $request->validated()
        );

        $mortality->load('fishCycle');

        return new MortalityResource($mortality);
    }

    public function show(
        Mortality $mortality
    ): MortalityResource {
        $mortality->load('fishCycle');

        return new MortalityResource($mortality);
    }

    public function update(
        UpdateMortalityRequest $request,
        Mortality $mortality
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => 'Update mortality akan kita aktifkan setelah mekanisme koreksi jumlah ikan selesai.',
        ], 422);
    }

    public function destroy(
        Mortality $mortality
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => 'Penghapusan mortality akan kita aktifkan setelah mekanisme koreksi jumlah ikan selesai.',
        ], 422);
    }
}
