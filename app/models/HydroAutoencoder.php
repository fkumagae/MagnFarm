<?php
// app/models/HydroAutoencoder.php
// Wrapper simples para chamar o serviço Python (FastAPI) do autoencoder
// e calcular métricas de erro de reconstrução para o dashboard.

declare(strict_types=1);

require_once __DIR__ . '/../core/db.php';

class HydroAutoencoder
{
    /**
     * Calcula erros de reconstrução para as leituras fornecidas usando o modelo Python.
     *
     * @param array<array<string,mixed>> $rows
     * @return array{
     *   status:string,
     *   detail?:string,
     *   errors?:array<int,float>,
     *   min?:float,
     *   max?:float,
     *   avg?:float
     * }
     */
    public static function reconstructionMetrics(array $rows): array
    {
        if (empty($rows)) {
            return [
                'status' => 'empty',
                'detail' => 'Nenhuma leitura disponível para calcular erros.',
            ];
        }

        $data = [];
        foreach ($rows as $row) {
            $data[] = [
                (float)($row['ph'] ?? 0),
                (float)($row['solution_temp_c'] ?? 0),
                (float)($row['air_temp_c'] ?? 0),
                (float)($row['humidity_percent'] ?? 0),
                (float)($row['light_lux'] ?? 0),
                (float)($row['ec_mScm'] ?? 0),
            ];
        }

        $payload = ['data' => $data];

        $ch = curl_init('http://127.0.0.1:8000/api/model/reconstruction-error');
        if ($ch === false) {
            return [
                'status' => 'error',
                'detail' => 'Falha ao inicializar cliente HTTP para o serviço de IA.',
            ];
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        $response = curl_exec($ch);
        if ($response === false) {
            $err = curl_error($ch);
            curl_close($ch);
            return [
                'status' => 'error',
                'detail' => 'Falha ao chamar serviço de IA (reconstruction-error): ' . $err,
            ];
        }
        curl_close($ch);

        /** @var mixed $decoded */
        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            return [
                'status' => 'error',
                'detail' => 'Resposta inválida do serviço de IA ao calcular erros.',
            ];
        }

        if (isset($decoded['detail']) && !isset($decoded['errors'])) {
            return [
                'status' => 'error',
                'detail' => (string) $decoded['detail'],
            ];
        }

        if (!isset($decoded['errors']) || !is_array($decoded['errors'])) {
            return [
                'status' => 'error',
                'detail' => 'Resposta inesperada do serviço de IA ao calcular erros.',
            ];
        }

        $errors = array_map('floatval', $decoded['errors']);
        if (empty($errors)) {
            return [
                'status' => 'error',
                'detail' => 'Serviço de IA retornou zero erros de reconstrução.',
            ];
        }

        $min = min($errors);
        $max = max($errors);
        $avg = array_sum($errors) / max(count($errors), 1);

        return [
            'status' => 'ok',
            'errors' => $errors,
            'min' => $min,
            'max' => $max,
            'avg' => $avg,
        ];
    }
}

