<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFeedingRequest;
use App\Http\Requests\UpdateFeedingRequest;
use App\Http\Resources\FeedingResource;
use App\Models\Feeding;
use App\Services\FeedingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FeedingController extends Controller
{
    public function __construct(
        protected FeedingService $feedingService
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $feedings = Feeding::query()
            ->with([
                'fishCycle',
                'product',
            ])
            ->latest('feeding_date')
            ->paginate(15);

        return FeedingResource::collection($feedings);
    }

    public function store(
        StoreFeedingRequest $request
    ): FeedingResource {
        $feeding = $this->feedingService->create(
            $request->validated(),
            $request->user()?->id
        );

        return new FeedingResource($feeding);
    }

    public function show(
        Feeding $feeding
    ): FeedingResource {
        $feeding->load([
            'fishCycle',
            'product',
        ]);

        return new FeedingResource($feeding);
    }

    public function update(
        UpdateFeedingRequest $request,
        Feeding $feeding
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => 'Untuk menjaga integritas stok, perubahan data pakan akan kita implementasikan setelah Stock Movement Service selesai.',
        ], 422);
    }

    public function destroy(
        Feeding $feeding
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => 'Penghapusan data pakan dinonaktifkan sementara karena harus mengembalikan stok secara otomatis.',
        ], 422);
    }
}
