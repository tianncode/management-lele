<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOtherIncomeRequest;
use App\Http\Requests\UpdateOtherIncomeRequest;
use App\Http\Resources\OtherIncomeResource;
use App\Models\OtherIncome;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OtherIncomeController extends Controller
{
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
        $income = OtherIncome::create(
            $request->validated()
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
        $otherIncome->update(
            $request->validated()
        );

        return new OtherIncomeResource(
            $otherIncome->refresh()
        );
    }

    public function destroy(
        OtherIncome $otherIncome
    ) {
        $otherIncome->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pendapatan berhasil dihapus.',
        ]);
    }
}
