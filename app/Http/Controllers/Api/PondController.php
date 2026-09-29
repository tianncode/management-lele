<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePondRequest;
use App\Http\Requests\UpdatePondRequest;
use App\Http\Resources\PondResource;
use App\Models\Pond;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PondController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $ponds = Pond::query()
            ->latest()
            ->paginate(10);

        return PondResource::collection($ponds);
    }

    public function store(StorePondRequest $request): PondResource
    {
        $data = $request->validated();

        if (
            empty($data['volume']) &&
            isset($data['length'], $data['width'], $data['depth'])
        ) {
            $data['volume'] =
                $data['length']
                * $data['width']
                * $data['depth'];
        }

        $pond = Pond::create($data);

        return new PondResource($pond);
    }

    public function show(Pond $pond): PondResource
    {
        return new PondResource($pond);
    }

    public function update(
        UpdatePondRequest $request,
        Pond $pond
    ): PondResource {
        $data = $request->validated();

        if (
            isset($data['length'], $data['width'], $data['depth'])
        ) {
            $data['volume'] =
                $data['length']
                * $data['width']
                * $data['depth'];
        }

        $pond->update($data);

        return new PondResource($pond->fresh());
    }

    public function destroy(Pond $pond): JsonResponse
    {
        if ($pond->fishCycles()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Kolam tidak dapat dihapus karena sudah memiliki siklus budidaya.',
            ], 422);
        }

        $pond->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kolam berhasil dihapus.',
        ]);
    }
}
