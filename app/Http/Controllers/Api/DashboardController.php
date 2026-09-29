<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(
        protected ReportService $reportService
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Dashboard data retrieved successfully.',
            'data' => $this->reportService->dashboard(),
        ]);
    }
}
