<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

/**
 * Trait para respostas JSON padronizadas da API.
 *
 * success(): retorna { success: true, data, message, meta? }
 * error(): retorna { success: false, error: { code, message, details } }
 */
trait HasApiResponse
{
    /**
     * Resposta de sucesso padronizada.
     *
     * @param  array<string, mixed>|null  $meta  Metadados adicionais (ex.: paginação).
     */
    protected function success(mixed $data = null, string $message = '', int $code = 200, ?array $meta = null): JsonResponse
    {
        $payload = [
            'success' => true,
            'data' => $data,
            'message' => $message,
        ];
        if ($meta !== null) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $code);
    }

    /** Resposta de erro padronizada (código de negócio + HTTP status). */
    protected function error(string $message, string $code = 'ERROR', mixed $details = null, int $httpCode = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details,
            ],
        ], $httpCode);
    }
}
