<?php
// app/models/HydroReading.php
// Modelo simples para leituras da tabela `hydro_readings`.

declare(strict_types=1);

require_once __DIR__ . '/../core/db.php';

class HydroReading
{
    /**
     * Retorna as últimas N leituras ordenadas por data (mais recentes primeiro).
     *
     * @param int $limit
     * @return array<array<string,mixed>>
     */
    public static function latest(int $limit = 20): array
    {
        $pdo = db();
        $sql = 'SELECT id, recorded_at, ph, solution_temp_c, air_temp_c, humidity_percent, light_lux, ec_mScm
                FROM hydro_readings
                ORDER BY recorded_at DESC
                LIMIT :limit';
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Calcula estatísticas simples (mínimo, máximo, média) para campos principais.
     *
     * @return array<string,array<string,float>>
     */
    public static function summary(): array
    {
        $pdo = db();
        $sql = 'SELECT
                    MIN(ph)  AS ph_min,
                    MAX(ph)  AS ph_max,
                    AVG(ph)  AS ph_avg,
                    MIN(solution_temp_c) AS sol_temp_min,
                    MAX(solution_temp_c) AS sol_temp_max,
                    AVG(solution_temp_c) AS sol_temp_avg,
                    MIN(air_temp_c) AS air_temp_min,
                    MAX(air_temp_c) AS air_temp_max,
                    AVG(air_temp_c) AS air_temp_avg,
                    MIN(humidity_percent) AS hum_min,
                    MAX(humidity_percent) AS hum_max,
                    AVG(humidity_percent) AS hum_avg,
                    MIN(light_lux) AS lux_min,
                    MAX(light_lux) AS lux_max,
                    AVG(light_lux) AS lux_avg,
                    MIN(ec_mScm) AS ec_min,
                    MAX(ec_mScm) AS ec_max,
                    AVG(ec_mScm) AS ec_avg
                FROM hydro_readings';

        $stmt = $pdo->query($sql);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $fmt = static function ($value): float {
            return $value !== null ? (float)$value : 0.0;
        };

        return [
            'ph' => [
                'min' => $fmt($row['ph_min'] ?? null),
                'max' => $fmt($row['ph_max'] ?? null),
                'avg' => $fmt($row['ph_avg'] ?? null),
            ],
            'solution_temp_c' => [
                'min' => $fmt($row['sol_temp_min'] ?? null),
                'max' => $fmt($row['sol_temp_max'] ?? null),
                'avg' => $fmt($row['sol_temp_avg'] ?? null),
            ],
            'air_temp_c' => [
                'min' => $fmt($row['air_temp_min'] ?? null),
                'max' => $fmt($row['air_temp_max'] ?? null),
                'avg' => $fmt($row['air_temp_avg'] ?? null),
            ],
            'humidity_percent' => [
                'min' => $fmt($row['hum_min'] ?? null),
                'max' => $fmt($row['hum_max'] ?? null),
                'avg' => $fmt($row['hum_avg'] ?? null),
            ],
            'light_lux' => [
                'min' => $fmt($row['lux_min'] ?? null),
                'max' => $fmt($row['lux_max'] ?? null),
                'avg' => $fmt($row['lux_avg'] ?? null),
            ],
            'ec_mScm' => [
                'min' => $fmt($row['ec_min'] ?? null),
                'max' => $fmt($row['ec_max'] ?? null),
                'avg' => $fmt($row['ec_avg'] ?? null),
            ],
        ];
    }
}

