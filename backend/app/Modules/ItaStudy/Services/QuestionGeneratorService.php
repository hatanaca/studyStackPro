<?php

namespace App\Modules\ItaStudy\Services;

use App\Models\StudyQuestion;
use App\Modules\ItaStudy\Repositories\Contracts\StudyQuestionRepositoryInterface;
use App\Services\MathService;

class QuestionGeneratorService
{
    public function __construct(
        private readonly StudyQuestionRepositoryInterface $questions,
        private readonly MathService $math,
    ) {}

    /**
     * Generate a random variant for a question.
     */
    public function generateVariant(StudyQuestion $question, string $userId): array
    {
        $data = $this->generateVariantData($question, $userId);

        $variant = $this->questions->createVariant(
            $question->id,
            $userId,
            $data['seed'],
            $data['parameters'],
            $data['prompt_resolved'],
            $data['answer_value'],
            $data['choices_resolved'],
        );

        return [
            'variant' => $variant,
            'question' => $question,
        ];
    }

    /**
     * Generate variant data without persisting (used for batch inserts).
     *
     * @return array{seed: int, parameters: array, prompt_resolved: string, answer_value: string, choices_resolved: array|null}
     */
    public function generateVariantData(StudyQuestion $question, string $userId): array
    {
        $seed = random_int(1, 1_000_000);
        $parameters = $this->generateParameters($question->parameters_spec, $seed);
        $promptResolved = $this->resolvePrompt($question->prompt, $parameters);
        $answerValue = $this->evaluateAnswer($question->answer_expression, $parameters);

        $choicesResolved = null;
        if ($question->choices_spec) {
            $choicesResolved = $this->resolveChoices($question->choices_spec, $parameters);
        }

        return [
            'seed' => $seed,
            'parameters' => $parameters,
            'prompt_resolved' => $promptResolved,
            'answer_value' => $answerValue,
            'choices_resolved' => $choicesResolved,
        ];
    }

    /**
     * Grade a user's answer against the expected answer.
     */
    public function gradeAnswer(string $userAnswer, string $expectedAnswer, string $answerType): bool
    {
        if ($answerType === 'choice') {
            return strtolower(trim($userAnswer)) === strtolower(trim($expectedAnswer));
        }

        try {
            $result = $this->math->evaluate("Eq({$userAnswer}, {$expectedAnswer})");

            return $result['result'] === 'True';
        } catch (\Throwable) {
            return strtolower(trim($userAnswer)) === strtolower(trim($expectedAnswer));
        }
    }

    private function generateParameters(array $spec, int $seed): array
    {
        mt_srand($seed);
        $params = [];

        foreach ($spec as $name => $definition) {
            $type = $definition['type'] ?? 'float';

            switch ($type) {
                case 'int':
                    $min = $definition['min'] ?? 1;
                    $max = $definition['max'] ?? 100;
                    $params[$name] = random_int($min, $max);
                    break;

                case 'float':
                    $min = $definition['min'] ?? 1;
                    $max = $definition['max'] ?? 100;
                    $step = $definition['step'] ?? 0.5;
                    $steps = (int) (($max - $min) / $step);
                    $params[$name] = $min + random_int(0, $steps) * $step;
                    break;

                case 'choice':
                    $values = $definition['values'] ?? [];
                    $params[$name] = $values[array_rand($values)];
                    break;

                case 'set':
                    $min = $definition['min'] ?? -10;
                    $max = $definition['max'] ?? 10;
                    $sizeRange = $definition['size'] ?? '3-5';
                    [$sizeMin, $sizeMax] = explode('-', $sizeRange);
                    $size = random_int((int) $sizeMin, (int) $sizeMax);
                    $elements = [];
                    for ($i = 0; $i < $size; $i++) {
                        $elements[] = random_int($min, $max);
                    }
                    $params[$name] = array_unique($elements);
                    break;

                default:
                    $params[$name] = $definition['default'] ?? 0;
            }
        }

        return $params;
    }

    private function resolvePrompt(string $prompt, array $parameters): string
    {
        foreach ($parameters as $name => $value) {
            if (is_array($value)) {
                $value = '{'.implode(', ', $value).'}';
            }
            $prompt = str_replace("{{$name}}", (string) $value, $prompt);
        }

        return $prompt;
    }

    private function evaluateAnswer(string $expression, array $parameters): string
    {
        try {
            $result = $this->math->evaluate($expression, $parameters);

            return $result['result'] ?? (string) $result;
        } catch (\Throwable) {
            $expr = $expression;
            foreach ($parameters as $name => $value) {
                if (! is_array($value)) {
                    $expr = str_replace($name, (string) $value, $expr);
                }
            }
            try {
                return (string) $this->evaluateSafeExpression($expr);
            } catch (\Throwable) {
                return $expression;
            }
        }
    }

    /**
     * Avalia expressões aritméticas simples sem eval() (fallback quando o math-service falha).
     * Apenas números, +, -, *, /, %, parênteses e ponto decimal são aceitos.
     */
    private function evaluateSafeExpression(string $expression): float
    {
        $expr = preg_replace('/[^0-9+\-*/().% ]/', '', $expression);
        $tokens = $this->tokenize($expr);
        $result = $this->parseExpression($tokens);

        return $result;
    }

    /** @return array<int, string> */
    private function tokenize(string $expr): array
    {
        $tokens = [];
        $length = strlen($expr);
        $i = 0;

        while ($i < $length) {
            $char = $expr[$i];
            if ($char === ' ') {
                $i++;

                continue;
            }
            if (is_numeric($char) || $char === '.') {
                $number = '';
                while ($i < $length && (is_numeric($expr[$i]) || $expr[$i] === '.')) {
                    $number .= $expr[$i];
                    $i++;
                }
                $tokens[] = $number;

                continue;
            }
            if (str_contains('+-*/%()', $char)) {
                $tokens[] = $char;
                $i++;

                continue;
            }
            throw new \InvalidArgumentException("Caractere inválido: {$char}");
        }

        return $tokens;
    }

    /** @param  array<int, string>  $tokens */
    private function parseExpression(array &$tokens): float
    {
        $value = $this->parseTerm($tokens);

        while (count($tokens) > 0 && ($tokens[0] === '+' || $tokens[0] === '-')) {
            $operator = array_shift($tokens);
            $rhs = $this->parseTerm($tokens);
            $value = $operator === '+' ? $value + $rhs : $value - $rhs;
        }

        return $value;
    }

    /** @param  array<int, string>  $tokens */
    private function parseTerm(array &$tokens): float
    {
        $value = $this->parseFactor($tokens);

        while (count($tokens) > 0 && ($tokens[0] === '*' || $tokens[0] === '/' || $tokens[0] === '%')) {
            $operator = array_shift($tokens);
            $rhs = $this->parseFactor($tokens);
            $value = match ($operator) {
                '*' => $value * $rhs,
                '/' => $rhs == 0 ? throw new \DivisionByZeroError : $value / $rhs,
                '%' => $rhs == 0 ? throw new \DivisionByZeroError : fmod($value, $rhs),
            };
        }

        return $value;
    }

    /** @param  array<int, string>  $tokens */
    private function parseFactor(array &$tokens): float
    {
        if (count($tokens) === 0) {
            throw new \InvalidArgumentException('Expressão vazia');
        }

        $token = array_shift($tokens);

        if ($token === '-') {
            return -$this->parseFactor($tokens);
        }
        if ($token === '+') {
            return $this->parseFactor($tokens);
        }
        if ($token === '(') {
            $value = $this->parseExpression($tokens);
            if (count($tokens) === 0 || array_shift($tokens) !== ')') {
                throw new \InvalidArgumentException('Parêntese não fechado');
            }

            return $value;
        }
        if (is_numeric($token)) {
            return (float) $token;
        }

        throw new \InvalidArgumentException("Token inesperado: {$token}");
    }

    private function resolveChoices(array $choicesSpec, array $parameters): array
    {
        $choices = [];
        foreach ($choicesSpec as $choice) {
            $resolved = $choice;
            foreach ($parameters as $name => $value) {
                if (! is_array($value)) {
                    $resolved = str_replace("{{$name}}", (string) $value, $resolved);
                }
            }
            $choices[] = $resolved;
        }

        return $choices;
    }
}
