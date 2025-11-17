<?php
// app/models/HydroAI.php
// Módulo simples de "IA" para analisar leituras hidropônicas e gerar insights.

declare(strict_types=1);

class HydroAI
{
    /**
     * Analisa leituras e retorna nível de risco e recomendações.
     *
     * @param array<array<string,mixed>> $rows
     * @return array{risk:string,score:int,issues:array<int,string>,suggestions:array<int,string>}
     */
    public static function analyze(array $rows): array
    {
        if (empty($rows)) {
            return [
                'risk' => 'desconhecido',
                'score' => 0,
                'issues' => ['Nenhuma leitura disponível para análise.'],
                'suggestions' => ['Cadastre novas leituras para permitir recomendações.'],
            ];
        }

        $issues = [];
        $score = 0;

        $phOut = $tempOut = $lightLow = $humidOut = $ecOut = 0;

        $first = end($rows);
        $last  = reset($rows);
        if (!is_array($first)) {
            $first = $rows[count($rows) - 1];
        }
        if (!is_array($last)) {
            $last = $rows[0];
        }

        foreach ($rows as $row) {
            $ph = (float)($row['ph'] ?? 0);
            if ($ph < 5.8 || $ph > 6.3) {
                $phOut++;
                $score += 2;
            }

            $sol = (float)($row['solution_temp_c'] ?? 0);
            if ($sol < 18.0 || $sol > 24.0) {
                $tempOut++;
                $score += 2;
            }

            $lux = (float)($row['light_lux'] ?? 0);
            if ($lux < 15000.0) {
                $lightLow++;
                $score += 1;
            }

            $hum = (float)($row['humidity_percent'] ?? 0);
            if ($hum < 50.0 || $hum > 80.0) {
                $humidOut++;
                $score += 1;
            }

            $ec = (float)($row['ec_mScm'] ?? 0);
            if ($ec < 1.2 || $ec > 2.4) {
                $ecOut++;
                $score += 1;
            }
        }

        if ($phOut > 0) {
            $issues[] = "Várias leituras de pH fora da faixa ideal (5,8–6,3).";
        }
        if ($tempOut > 0) {
            $issues[] = "Temperatura da solução saiu da faixa típica (18–24 °C).";
        }
        if ($lightLow > 0) {
            $issues[] = "Níveis de luz abaixo do recomendado em parte das leituras.";
        }
        if ($humidOut > 0) {
            $issues[] = "Umidade do ar fora da faixa confortável (50–80%).";
        }
        if ($ecOut > 0) {
            $issues[] = "Condutividade (EC) fora da faixa comum de trabalho (1,2–2,4 mS/cm).";
        }

        $suggestions = [];
        if ($phOut > 0) {
            $suggestions[] = "Ajustar o pH da solução nutritiva com corretivos (pH up/down) e monitorar nas próximas horas.";
        }
        if ($tempOut > 0) {
            $suggestions[] = "Verificar sistema de resfriamento/aquecimento da solução e isolamento térmico do reservatório.";
        }
        if ($lightLow > 0) {
            $suggestions[] = "Avaliar distância e potência das lâmpadas ou incidência solar para elevar a luz média.";
        }
        if ($humidOut > 0) {
            $suggestions[] = "Ajustar ventilação ou desumidificação para manter umidade entre 50% e 80%.";
        }
        if ($ecOut > 0) {
            $suggestions[] = "Rever a concentração de nutrientes e programar uma troca parcial da solução.";
        }
        if (empty($suggestions)) {
            $suggestions[] = "Parâmetros dentro de faixas adequadas. Manter rotina de monitoramento.";
        }

        $risk = 'baixo';
        if ($score >= 6) {
            $risk = 'alto';
        } elseif ($score >= 3) {
            $risk = 'médio';
        }

        $trendNotes = [];
        $trendPh = (float)($last['ph'] ?? 0) - (float)($first['ph'] ?? 0);
        if (abs($trendPh) > 0.15) {
            $trendNotes[] = $trendPh > 0 ? "pH em tendência de alta." : "pH em tendência de queda.";
        }

        $trendTemp = (float)($last['solution_temp_c'] ?? 0) - (float)($first['solution_temp_c'] ?? 0);
        if (abs($trendTemp) > 0.5) {
            $trendNotes[] = $trendTemp > 0 ? "Temperatura da solução está subindo ao longo do tempo." : "Temperatura da solução está caindo ao longo do tempo.";
        }

        $issues = array_merge($issues, $trendNotes);

        return [
            'risk' => $risk,
            'score' => $score,
            'issues' => $issues,
            'suggestions' => $suggestions,
        ];
    }
}

