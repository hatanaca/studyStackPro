<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notifications\StoreNotificationRequest;
use App\Http\Resources\NotificationResource;
use App\Modules\Notifications\Services\NotificationService;
use App\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $unreadOnly = $request->query('unread') === 'true' ? true : null;
        $notifications = $this->notificationService->listForUser($request->user()->id, $unreadOnly);

        return $this->success(NotificationResource::collection($notifications));
    }

    public function store(StoreNotificationRequest $request): JsonResponse
    {
        $notification = $this->notificationService->create($request->user()->id, $request->validated());

        return $this->success(new NotificationResource($notification), 'Notificação criada.', 201);
    }

    public function markRead(Request $request, string $notification): JsonResponse
    {
        $this->notificationService->markRead($notification, $request->user()->id);

        return $this->success(null, 'Notificação marcada como lida.');
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $this->notificationService->markAllRead($request->user()->id);

        return $this->success(null, 'Todas as notificações marcadas como lidas.');
    }

    public function destroy(Request $request, string $notification): JsonResponse
    {
        $this->notificationService->delete($notification, $request->user()->id);

        return $this->success(null, 'Notificação excluída.');
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = $this->notificationService->unreadCount($request->user()->id);

        return $this->success(['count' => $count]);
    }
}
