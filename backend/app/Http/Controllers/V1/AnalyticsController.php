<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Analytics\ExportAnalyticsRequest;
use App\Http\Resources\DashboardResource;
use App\Modules\Analytics\Services\AnalyticsService;
use App\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private AnalyticsService $analyticsService
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        $data = $this->analyticsService->getDashboardData($request->user()->id);

        return $this->success(new DashboardResource($data));
    }

    public function userMetrics(Request $request): JsonResponse
    {
        $data = $this->analyticsService->getUserMetrics($request->user()->id);

        return $this->success($data);
    }

    public function techStats(Request $request): JsonResponse
    {
        $data = $this->analyticsService->getTechStats($request->user()->id);

        return $this->success($data);
    }

    public function timeSeries(Request $request): JsonResponse
    {
        $days = min(90, max(7, (int) $request->query('days', 30)));
        $data = $this->analyticsService->getTimeSeries($request->user()->id, $days);

        return $this->success($data);
    }

    public function weekly(Request $request): JsonResponse
    {
        $data = $this->analyticsService->getWeekly($request->user()->id);

        return $this->success($data);
    }

    public function heatmap(Request $request): JsonResponse
    {
        $year = $request->query('year') ? (int) $request->query('year') : null;
        $data = $this->analyticsService->getHeatmap($request->user()->id, $year);

        return $this->success($data);
    }

    public function recalculate(Request $request): JsonResponse
    {
        $result = $this->analyticsService->dispatchRecalculate($request->user()->id);

        return $this->success($result, 'Recálculo agendado. Dashboard atualiza em alguns segundos.', 202);
    }

    public function export(ExportAnalyticsRequest $request): JsonResponse
    {
        $start = $request->validated('start');
        $end = $request->validated('end');
        $data = $this->analyticsService->getExportData($request->user()->id, $start, $end);

        return $this->success([
            'exported_at' => now()->toIso8601String(),
            'period' => ['start' => $start, 'end' => $end],
            'data' => $data,
        ]);
    }
}
