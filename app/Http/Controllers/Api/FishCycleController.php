<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFishCycleRequest;
use App\Http\Requests\UpdateFishCycleRequest;
use App\Http\Resources\FishCycleResource;
use App\Models\FishCycle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FishCycleController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $cycles = FishCycle::query()
            ->with('pond')
            ->latest('start_date')
            ->paginate(10);

        return FishCycleResource::collection($cycles);
    }

    public function store(
        StoreFishCycleRequest $request
    ): FishCycleResource {
        $cycle = FishCycle::create(
            $request->validated()
        );

        $cycle->load('pond');

        return new FishCycleResource($cycle);
    }

    public function show(
        FishCycle $fishCycle
    ): FishCycleResource {
        $fishCycle->load('pond');

        return new FishCycleResource($fishCycle);
    }

    public function update(
        UpdateFishCycleRequest $request,
        FishCycle $fishCycle
    ): FishCycleResource {
        $fishCycle->update(
            $request->validated()
        );

        $fishCycle->load('pond');

        return new FishCycleResource(
            $fishCycle->fresh()
        );
    }

    public function destroy(
        FishCycle $fishCycle
    ): JsonResponse {
        if (
            $fishCycle->feedings()->exists() ||
            $fishCycle->mortalities()->exists() ||
            $fishCycle->samplings()->exists() ||
            $fishCycle->harvests()->exists()
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Siklus tidak dapat dihapus karena sudah memiliki data budidaya.',
            ], 422);
        }

        $fishCycle->delete();

        return response()->json([
            'success' => true,
            'message' => 'Siklus budidaya berhasil dihapus.',
        ]);
    }
}
