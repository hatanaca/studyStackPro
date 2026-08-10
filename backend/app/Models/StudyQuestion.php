<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudyQuestion extends BaseModel
{
    protected $fillable = [
        'sub_topic_id',
        'kind',
        'prompt',
        'parameters_spec',
        'answer_expression',
        'answer_type',
        'choices_spec',
        'solution_latex',
        'explanation',
        'hint',
        'difficulty',
        'has_graph',
        'graph_config',
        'visual_type',
        'visual_config',
    ];

    protected function casts(): array
    {
        return [
            'parameters_spec' => 'array',
            'choices_spec' => 'array',
            'difficulty' => 'integer',
            'has_graph' => 'boolean',
            'graph_config' => 'array',
            'visual_config' => 'array',
        ];
    }

    public function subTopic(): BelongsTo
    {
        return $this->belongsTo(StudySubTopic::class, 'sub_topic_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(StudyQuestionVariant::class, 'question_id');
    }
}
