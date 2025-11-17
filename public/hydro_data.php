<?php
// public/hydro_data.php
// Endpoint JSON para fornecer leituras hidropônicas em tempo (quase) real para o dashboard.

require_once __DIR__ . '/../app/core/session.php';
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/models/HydroReading.php';

// Apenas usuários autenticados podem acessar os dados
require_auth('login.php?action=dashboard');

header('Content-Type: application/json; charset=utf-8');

$points = isset($_GET['points']) ? (int)$_GET['points'] : 10;
if ($points < 4) {
    $points = 4;
} elseif ($points > 200) {
    $points = 200;
}

$rows = HydroReading::latest($points);

// ordenar por data crescente para facilitar a plotagem
$rows = array_reverse($rows);

$labels = [];
$ph = [];
$solutionTemp = [];
$airTemp = [];
$humidity = [];
$light = [];
$ec = [];

foreach ($rows as $row) {
    $labels[] = $row['recorded_at'];
    $ph[] = (float)$row['ph'];
    $solutionTemp[] = (float)$row['solution_temp_c'];
    $airTemp[] = (float)$row['air_temp_c'];
    $humidity[] = (float)$row['humidity_percent'];
    $light[] = (float)$row['light_lux'];
    $ec[] = (float)$row['ec_mScm'];
}

echo json_encode([
    'labels' => $labels,
    'ph' => $ph,
    'solution_temp_c' => $solutionTemp,
    'air_temp_c' => $airTemp,
    'humidity_percent' => $humidity,
    'light_lux' => $light,
    'ec_mScm' => $ec,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;

