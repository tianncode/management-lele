<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExpenseCategoryResource;
use App\Models\ExpenseCategory;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ExpenseCategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $categories = ExpenseCategory::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return ExpenseCategoryResource::collection(
            $categories
        );
    }
}
