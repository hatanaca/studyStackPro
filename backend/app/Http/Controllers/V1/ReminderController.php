<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reminders\StoreReminderRequest;
use App\Http\Requests\Reminders\UpdateReminderRequest;
use App\Http\Resources\ReminderResource;
use App\Modules\Reminders\Services\ReminderService;
use App\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReminderController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly ReminderService $reminderService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $technologyId = $request->query('technology_id');
        $reminders = $this->reminderService->listForUser($request->user()->id, $technologyId);

        return $this->success(ReminderResource::collection($reminders));
    }

    public function store(StoreReminderRequest $request): JsonResponse
    {
        $reminder = $this->reminderService->create($request->user()->id, $request->validated());

        return $this->success(new ReminderResource($reminder), 'Lembrete criado.', 201);
    }

    public function show(Request $request, string $reminder): JsonResponse
    {
        $model = $this->reminderService->findForUser($reminder, $request->user()->id);

        return $this->success(new ReminderResource($model));
    }

    public function update(UpdateReminderRequest $request, string $reminder): JsonResponse
    {
        $updated = $this->reminderService->update($reminder, $request->user()->id, $request->validated());

        return $this->success(new ReminderResource($updated), 'Lembrete atualizado.');
    }

    public function destroy(Request $request, string $reminder): JsonResponse
    {
        $this->reminderService->delete($reminder, $request->user()->id);

        return $this->success(null, 'Lembrete excluído.');
    }
}
