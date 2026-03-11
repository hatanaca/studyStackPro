<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Analytics\ExportAnalyticsRequest;
use App\Http\Requests\Analytics\HeatmapRequest;
use App\Http\Requests\Analytics\TimeSeriesRequest;
use App\Http\Resources\DashboardResource;
use App\Modules\Analytics\Services\AnalyticsService;
use App\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador de analytics e métricas.
 *
 * Fornece dados para dashboard, métricas gerais, time series, heatmap, exportação
 * e dispara recálculo de métricas em background. Usa cache com tags para performance.
 */
class AnalyticsController extends Controller
{
    use HasApiResponse;

    /**
     * Injeta o AnalyticsService para agregação e cache de métricas.
     */
    public function __construct(
        private AnalyticsService $analyticsService
    ) {}

    /**
     * Retorna dados completos do dashboard (KPIs, heatmap, séries, distribuição etc.).
     */
    public function dashboard(Request $request): JsonResponse
    {
        $data = $this->analyticsService->getDashboardData($request->user()->id);

        return $this->success(new DashboardResource($data));
    }

    /**
     * Retorna métricas gerais do usuário (tempo total, streaks etc.).
     */
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

    /**
     * Retorna séries temporais (minutos por dia) para gráficos de linha.
     */
    public function timeSeries(TimeSeriesRequest $request): JsonResponse
    {
        $data = $this->analyticsService->getTimeSeries($request->user()->id, $request->getDays());

        return $this->success($data);
    }

    /**
     * Retorna comparação semanal (esta semana vs anterior).
     */
    public function weekly(Request $request): JsonResponse
    {
        $data = $this->analyticsService->getWeekly($request->user()->id);

        return $this->success($data);
    }

    public function heatmap(HeatmapRequest $request): JsonResponse
    {
        $data = $this->analyticsService->getHeatmap($request->user()->id, $request->getYear());

        return $this->success($data);
    }

    /**
     * Agenda recálculo de métricas em background. Retorna 202 Accepted.
     * WebSocket notifica quando concluir.
     */
    public function recalculate(Request $request): JsonResponse
    {
        $result = $this->analyticsService->dispatchRecalculate($request->user()->id);

        return $this->success($result, 'Recálculo agendado. Dashboard atualiza em alguns segundos.', 202);
    }

    /**
     * Exporta dados de analytics para período (start/end). Formato JSON.
     */
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
