<?php

namespace App\Modules\StudySessions\DTOs;

final readonly class StudySessionFilterDTO
{
    public function __construct(
        public ?string $technologyId = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public ?int $minDuration = null,
        public ?int $mood = null,
        public ?string $status = null,
        public int $perPage = 15,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            technologyId: $data['technology_id'] ?? null,
            dateFrom: $data['date_from'] ?? null,
            dateTo: $data['date_to'] ?? null,
            minDuration: isset($data['min_duration']) && $data['min_duration'] !== '' ? (int) $data['min_duration'] : null,
            mood: isset($data['mood']) && $data['mood'] !== '' ? (int) $data['mood'] : null,
            status: $data['status'] ?? null,
            perPage: min((int) ($data['per_page'] ?? 15), 50),
        );
    }

    public function toArray(): array
    {
        $arr = array_filter([
            'technology_id' => $this->technologyId,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'min_duration' => $this->minDuration,
            'mood' => $this->mood,
            'status' => $this->status,
        ], fn ($v) => $v !== null && $v !== '');

        $arr['per_page'] = $this->perPage;

        return $arr;
    }
}
