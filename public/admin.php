<?php
require_once __DIR__ . '/../app/core/session.php';
require_once __DIR__ . '/../app/core/csrf.php';
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/models/HydroReading.php';

// exige autenticação e privilégios
require_auth('login.php?action=admin');
require_admin('login.php?action=admin');

// manipula ações POST (promote/demote/delete/treinar modelo)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check_request()) {
        flash('error', 'Requisição inválida (CSRF).');
        header('Location: admin.php');
        exit;
    }

    $action = $_POST['action'] ?? '';
    $target = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;

    // impede ação sobre si mesmo (promover/demover/excluir)
    $me = current_user();
    if ($target && $target === (int)($me['id'] ?? 0)) {
        flash('error', 'Ação não permitida sobre seu próprio usuário.');
        header('Location: admin.php');
        exit;
    }

    if ($action === 'promote') {
        if (User::setRole($target, 'admin')) {
            flash('success', 'Usuário promovido a admin.');
        } else {
            flash('error', 'Falha ao promover usuário.');
        }
    } elseif ($action === 'demote') {
        if (User::setRole($target, 'user')) {
            flash('success', 'Usuário demovido a usuário comum.');
        } else {
            flash('error', 'Falha ao demover usuário.');
        }
    } elseif ($action === 'delete') {
        if (User::delete($target)) {
            flash('success', 'Usuário removido.');
        } else {
            flash('error', 'Falha ao remover usuário.');
        }
    } elseif ($action === 'train_model') {
        // Treinamento inicial do modelo de autoencoder com dados de HydroReading
        try {
            $readings = HydroReading::latest(200);
            if (empty($readings)) {
                flash('error', 'Não há leituras hidropônicas suficientes para treinar o modelo.');
                header('Location: admin.php');
                exit;
            }

            $data = [];
            foreach ($readings as $row) {
                $data[] = [
                    (float)$row['ph'],
                    (float)$row['solution_temp_c'],
                    (float)$row['air_temp_c'],
                    (float)$row['humidity_percent'],
                    (float)$row['light_lux'],
                    (float)$row['ec_mScm'],
                ];
            }

            $payload = [
                'data' => $data,
            ];

            $ch = curl_init('http://127.0.0.1:8000/api/model/initial-train');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

            $response = curl_exec($ch);
            if ($response === false) {
                $err = curl_error($ch);
                curl_close($ch);
                flash('error', 'Falha ao chamar serviço de IA: ' . $err);
                header('Location: admin.php');
                exit;
            }
            curl_close($ch);

            $result = json_decode($response, true);
            if (!is_array($result) || ($result['status'] ?? '') !== 'ok') {
                $msg = isset($result['detail']) ? (string)$result['detail'] : 'Resposta inesperada do serviço de IA.';
                flash('error', $msg);
            } else {
                $samples = (int)($result['samples'] ?? 0);
                flash('success', 'Modelo treinado com sucesso usando ' . $samples . ' leituras hidropônicas.');
            }
        } catch (Throwable $e) {
            flash('error', 'Erro ao treinar modelo: ' . $e->getMessage());
        }
    } elseif ($action === 'online_train_model') {
        // Treinamento online do modelo de autoencoder com leituras recentes
        try {
            $readings = HydroReading::latest(200);
            if (empty($readings)) {
                flash('error', 'Nao ha leituras hidropinicas suficientes para treinar o modelo online.');
                header('Location: admin.php');
                exit;
            }

            $data = [];
            foreach ($readings as $row) {
                $data[] = [
                    (float)$row['ph'],
                    (float)$row['solution_temp_c'],
                    (float)$row['air_temp_c'],
                    (float)$row['humidity_percent'],
                    (float)$row['light_lux'],
                    (float)$row['ec_mScm'],
                ];
            }

            $payload = [
                'data' => $data,
            ];

            $ch = curl_init('http://127.0.0.1:8000/api/model/online-train');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

            $response = curl_exec($ch);
            if ($response === false) {
                $err = curl_error($ch);
                curl_close($ch);
                flash('error', 'Falha ao chamar servico de IA (online-train): ' . $err);
                header('Location: admin.php');
                exit;
            }
            curl_close($ch);

            $result = json_decode($response, true);
            if (!is_array($result) || ($result['status'] ?? '') !== 'ok') {
                $msg = isset($result['detail']) ? (string)$result['detail'] : 'Resposta inesperada do servico de IA ao treinar online.';
                flash('error', $msg);
            } else {
                $added = (int)($result['added_samples'] ?? 0);
                $buffer = (int)($result['buffer_size'] ?? 0);
                $msg = sprintf(
                    'Modelo atualizado online com %d novas leituras. Tamanho atual do buffer: %d.',
                    $added,
                    $buffer
                );
                flash('success', $msg);
            }
        } catch (Throwable $e) {
            flash('error', 'Erro ao treinar modelo online: ' . $e->getMessage());
        }
    } elseif ($action === 'test_model') {
        // Testa o modelo treinado calculando erro de reconstru��ǜo para leituras recentes
        try {
            $readings = HydroReading::latest(50);
            if (empty($readings)) {
                flash('error', 'Nǜo hǭ leituras hidrop��nicas suficientes para testar o modelo.');
                header('Location: admin.php');
                exit;
            }

            $data = [];
            foreach ($readings as $row) {
                $data[] = [
                    (float)$row['ph'],
                    (float)$row['solution_temp_c'],
                    (float)$row['air_temp_c'],
                    (float)$row['humidity_percent'],
                    (float)$row['light_lux'],
                    (float)$row['ec_mScm'],
                ];
            }

            $payload = [
                'data' => $data,
            ];

            $ch = curl_init('http://127.0.0.1:8000/api/model/reconstruction-error');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

            $response = curl_exec($ch);
            if ($response === false) {
                $err = curl_error($ch);
                curl_close($ch);
                flash('error', 'Falha ao chamar servi��o de IA para teste: ' . $err);
                header('Location: admin.php');
                exit;
            }
            curl_close($ch);

            $result = json_decode($response, true);
            if (!is_array($result) || !isset($result['errors']) || !is_array($result['errors'])) {
                $msg = isset($result['detail']) ? (string)$result['detail'] : 'Resposta inesperada do servi��o de IA ao testar o modelo.';
                flash('error', $msg);
            } else {
                $errors = array_map('floatval', $result['errors']);
                $count = count($errors);
                if ($count === 0) {
                    flash('error', 'O servi��o de IA retornou zero erros de reconstru��ǜo.');
                } else {
                    $min = min($errors);
                    $max = max($errors);
                    $avg = array_sum($errors) / $count;
                    $msg = sprintf(
                        'Modelo testado com %d leituras. Erro de reconstru��ǜo - m��nimo: %.4f, mǸdio: %.4f, m��ximo: %.4f.',
                        $count,
                        $min,
                        $avg,
                        $max
                    );
                    flash('success', $msg);
                }
            }
        } catch (Throwable $e) {
            flash('error', 'Erro ao testar modelo: ' . $e->getMessage());
        }
    } elseif ($action === 'generate_readings') {
        // Gera mais leituras simuladas na tabela hydro_readings
        try {
            $pdo = db();
            $sql = <<<SQL
INSERT INTO hydro_readings
(recorded_at, ph, solution_temp_c, air_temp_c, humidity_percent, light_lux, ec_mScm)
SELECT
    NOW() - INTERVAL seq.minute MINUTE AS recorded_at,
    6.0 + (RAND() * 0.6 - 0.3)                    AS ph,
    20.0 + (seq.minute / 60.0) * 0.15             AS solution_temp_c,
    22.0 + (seq.minute / 60.0) * 0.20             AS air_temp_c,
    65.0 - (seq.minute / 60.0) * 0.20             AS humidity_percent,
    8000 + (seq.minute * 12)                      AS light_lux,
    1.8 + (seq.minute / 60.0) * 0.01              AS ec_mScm
FROM (
    SELECT (@row := @row + 1) * 5 AS minute
    FROM
        (SELECT 0 UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION
         SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t1,
        (SELECT 0 UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION
         SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t2,
        (SELECT 0 UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION
         SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t3,
        (SELECT @row := -1) t0
) AS seq
WHERE seq.minute <= 200 * 5;
SQL;

            $inserted = $pdo->exec($sql);
            $rows = (int) $inserted;
            if ($rows > 0) {
                flash('success', 'Geradas ' . $rows . ' novas leituras simuladas em hydro_readings.');
            } else {
                flash('error', 'Nenhuma leitura simulada foi gerada.');
            }
        } catch (Throwable $e) {
            flash('error', 'Erro ao gerar leituras simuladas: ' . $e->getMessage());
        }
    }

    header('Location: admin.php');
    exit;
}

// GET: mostra a interface
$users = User::all();
require_once __DIR__ . '/../app/views/partials/header.php';
require_once __DIR__ . '/../app/views/admin.php';
