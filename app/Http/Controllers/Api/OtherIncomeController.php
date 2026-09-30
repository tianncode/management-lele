<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOtherIncomeRequest;
use App\Http\Requests\UpdateOtherIncomeRequest;
use App\Http\Resources\OtherIncomeResource;
use App\Models\OtherIncome;
use App\Services\OtherIncomeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OtherIncomeController extends Controller
{
    public function __construct(
        protected OtherIncomeService $otherIncomeService
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $incomes = OtherIncome::query()
            ->latest('income_date')
            ->paginate(15);

        return OtherIncomeResource::collection($incomes);
    }

    public function store(
        StoreOtherIncomeRequest $request
    ): OtherIncomeResource {
        $income = $this->otherIncomeService->create(
            $request->validated(),
            $request->user()?->id
        );

        return new OtherIncomeResource($income);
    }

    public function show(
        OtherIncome $otherIncome
    ): OtherIncomeResource {
        return new OtherIncomeResource($otherIncome);
    }

    public function update(
        UpdateOtherIncomeRequest $request,
        OtherIncome $otherIncome
    ): OtherIncomeResource {
        $income = $this->otherIncomeService->update(
            $otherIncome,
            $request->validated()
        );

        return new OtherIncomeResource($income);
    }

    public function destroy(
        OtherIncome $otherIncome
    ): JsonResponse {
        $this->otherIncomeService->delete($otherIncome);

        return response()->json([
            'success' => true,
            'message' => 'Pendapatan berhasil dihapus.',
        ]);
    }
}
