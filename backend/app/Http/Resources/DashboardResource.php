<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'user_metrics' => $this->resource['user_metrics'] ?? [],
            'technology_metrics' => $this->resource['technology_metrics'] ?? [],
            'time_series_30d' => $this->resource['time_series_30d'] ?? [],
            'top_technologies' => $this->resource['top_technologies'] ?? [],
        ];
    }
}
