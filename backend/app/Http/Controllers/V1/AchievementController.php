<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AchievementResource;
use App\Modules\Gamification\Services\AchievementService;
use App\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AchievementController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private AchievementService $achievementService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $achievements = $this->achievementService->getUserAchievements($request->user()->id);

        return $this->success(AchievementResource::collection($achievements));
    }

    public function check(Request $request): JsonResponse
    {
        $newAchievements = $this->achievementService->checkAndAward($request->user()->id);

        return $this->success([
            'new_achievements' => AchievementResource::collection($newAchievements),
            'count' => count($newAchievements),
        ]);
    }
}
