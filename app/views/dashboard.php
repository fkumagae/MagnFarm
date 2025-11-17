<?php
// Dashboard view served via front controller.
// Primeiro: dados simulados de plantação hidropônica.
// Abaixo: resumo de usuários (mostrado apenas para admins).

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/HydroReading.php';
require_once __DIR__ . '/../models/HydroAI.php';
require_once __DIR__ . '/../models/HydroAutoencoder.php';

// Parte hidroponia (visível para qualquer usuário autenticado)
$points = isset($_GET['points']) ? (int) $_GET['points'] : 10;
if ($points < 4) {
    $points = 4;
} elseif ($points > 200) {
    $points = 200;
}

$hydroSummary = HydroReading::summary();
$hydroLatest  = HydroReading::latest($points);
$hydroAI      = HydroAI::analyze($hydroLatest);

// prepara dados para gráficos (ordenados por data crescente)
$chartRows = array_reverse($hydroLatest);
$chartData = [
    'labels' => [],
    'ph' => [],
    'solution_temp_c' => [],
    'air_temp_c' => [],
    'humidity_percent' => [],
    'light_lux' => [],
    'ec_mScm' => [],
    'recon_error' => [],
];
foreach ($chartRows as $row) {
    $chartData['labels'][] = $row['recorded_at'];
    $chartData['ph'][] = (float) $row['ph'];
    $chartData['solution_temp_c'][] = (float) $row['solution_temp_c'];
    $chartData['air_temp_c'][] = (float) $row['air_temp_c'];
    $chartData['humidity_percent'][] = (float) $row['humidity_percent'];
    $chartData['light_lux'][] = (float) $row['light_lux'];
    $chartData['ec_mScm'][] = (float) $row['ec_mScm'];
}

// calcula erros de reconstrução alinhados com a ordem cronológica de $chartRows
$autoencoder  = HydroAutoencoder::reconstructionMetrics($chartRows);
if (isset($autoencoder['status']) && $autoencoder['status'] === 'ok' && !empty($autoencoder['errors']) && is_array($autoencoder['errors'])) {
    foreach ($autoencoder['errors'] as $err) {
        $chartData['recon_error'][] = (float) $err;
    }
}

// Parte usuários (somente admins)
$isAdmin = function_exists('is_admin') && is_admin();
$users = [];
$totalUsers = $admins = $regulars = $recent7 = 0;
$adminPercent = $userPercent = 0;
$recentUsers = [];

if ($isAdmin) {
    $users = User::all();
    $totalUsers = count($users);
    $admins = 0;
    $regulars = 0;
    $recent7 = 0;

    $sevenDaysAgo = new DateTimeImmutable('-7 days');

    foreach ($users as $u) {
        $role = $u['role'] ?? 'user';
        if ($role === 'admin') {
            $admins++;
        } else {
            $regulars++;
        }

        if (!empty($u['created_at'])) {
            try {
                $createdAt = new DateTimeImmutable($u['created_at']);
                if ($createdAt >= $sevenDaysAgo) {
                    $recent7++;
                }
            } catch (Throwable $e) {
                // ignore parsing issues
            }
        }
    }

    $maxForChart = max($admins, $regulars, 1);
    $adminPercent = $admins > 0 ? ($admins / $maxForChart) * 100 : 0;
    $userPercent  = $regulars > 0 ? ($regulars / $maxForChart) * 100 : 0;

    $recentUsers = array_slice($users, 0, 10);
}
?>

<main class="dashboard-main" role="main" aria-labelledby="dashboard-title">
  <h2 id="dashboard-title">Dashboard</h2>

  <section class="dashboard-table" aria-label="Dados da plantação hidropônica">
    <h3>Monitoramento hidropônico (simulado)</h3>

    <form method="get" action="index.php" class="dashboard-filter">
      <input type="hidden" name="action" value="dashboard">
      <label for="points">
        Quantidade de leituras a exibir
        <span class="dashboard-hint">(mín: 4, máx: 200)</span>
      </label>
      <input
        type="number"
        id="points"
        name="points"
        min="4"
        max="200"
        value="<?php echo (int) $points; ?>"
      />
      <button type="submit">Atualizar</button>
    </form>

    <div class="dashboard-grid">
      <article class="dashboard-card">
        <h4>pH médio</h4>
        <p class="dashboard-kpi"><?php echo number_format($hydroSummary['ph']['avg'] ?? 0, 2, ',', '.'); ?></p>
        <p>Ideal entre 5,8 e 6,3.</p>
      </article>
      <article class="dashboard-card">
        <h4>Temp. solução (°C)</h4>
        <p class="dashboard-kpi"><?php echo number_format($hydroSummary['solution_temp_c']['avg'] ?? 0, 1, ',', '.'); ?></p>
        <p>Faixa típica: 18–24 °C.</p>
      </article>
      <article class="dashboard-card">
        <h4>Luz média (lux)</h4>
        <p class="dashboard-kpi"><?php echo number_format($hydroSummary['light_lux']['avg'] ?? 0, 0, ',', '.'); ?></p>
        <p>Depende da cultura e estágio.</p>
      </article>
    </div>

    <?php if (empty($hydroLatest)): ?>
      <p>Sem leituras hidropônicas cadastradas ainda.</p>
    <?php endif; ?>
  </section>

  <?php if (!empty($hydroLatest)): ?>
    <section class="dashboard-chart" aria-label="Gráficos de leituras hidropônicas">
      <h3>Gráficos das últimas leituras</h3>
      <div class="hydro-charts">
        <figure class="hydro-chart-figure">
          <figcaption>pH e temperatura da solução</figcaption>
          <canvas id="chart-ph-temp" width="600" height="260"></canvas>
        </figure>
        <figure class="hydro-chart-figure">
          <figcaption>Luz (lux) e umidade (%)</figcaption>
          <canvas id="chart-light-hum" width="600" height="260"></canvas>
        </figure>
        <figure class="hydro-chart-figure">
          <figcaption>Erro de reconstru��ǜo do modelo</figcaption>
          <canvas id="chart-recon-error" width="600" height="260"></canvas>
        </figure>
      </div>
    </section>

    <section class="dashboard-table" aria-label="Tabela de leituras hidropônicas recentes">
      <h3>Dados das últimas leituras</h3>
      <div class="dashboard-table-wrapper">
        <table>
          <thead>
            <tr>
              <th scope="col">Data/hora</th>
              <th scope="col">pH</th>
              <th scope="col">Temp. solução (°C)</th>
              <th scope="col">Temp. ar (°C)</th>
              <th scope="col">Umidade (%)</th>
              <th scope="col">Luz (lux)</th>
              <th scope="col">Condutividade EC (mS/cm)</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($hydroLatest as $row): ?>
              <tr>
                <td><?php echo htmlspecialchars($row['recorded_at'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                <td><?php echo number_format((float) $row['ph'], 2, ',', '.'); ?></td>
                <td><?php echo number_format((float) $row['solution_temp_c'], 1, ',', '.'); ?></td>
                <td><?php echo number_format((float) $row['air_temp_c'], 1, ',', '.'); ?></td>
                <td><?php echo number_format((float) $row['humidity_percent'], 1, ',', '.'); ?></td>
                <td><?php echo number_format((float) $row['light_lux'], 0, ',', '.'); ?></td>
                <td><?php echo number_format((float) $row['ec_mScm'], 2, ',', '.'); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

    <section class="dashboard-table" aria-label="Insights de IA sobre a plantação">
      <h3>Insights gerados por IA (regras)</h3>
      <p><strong>Nível de risco atual:</strong> <?php echo htmlspecialchars($hydroAI['risk'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>

      <?php if (!empty($hydroAI['issues'])): ?>
        <h4>Pontos de atenção detectados</h4>
        <ul>
          <?php foreach ($hydroAI['issues'] as $issue): ?>
            <li><?php echo htmlspecialchars($issue, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <?php if (!empty($hydroAI['suggestions'])): ?>
        <h4>Recomendações para o comprador/operador</h4>
        <ul>
          <?php foreach ($hydroAI['suggestions'] as $s): ?>
            <li><?php echo htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>

    <section class="dashboard-table" aria-label="Modelo autoencoder e erros de reconstrução">
      <h3>Modelo Autoencoder (Python)</h3>
      <?php if ($autoencoder['status'] === 'ok'): ?>
        <p>
          Erros de reconstrução nas <?php echo count($hydroLatest); ?> últimas leituras:
          mínimo <?php echo number_format((float) $autoencoder['min'], 4, ',', '.'); ?>,
          médio <?php echo number_format((float) $autoencoder['avg'], 4, ',', '.'); ?>,
          máximo <?php echo number_format((float) $autoencoder['max'], 4, ',', '.'); ?>.
        </p>
        <p>
          Valores de erro mais altos indicam leituras mais anômalas em relação ao padrão aprendido pelo modelo.
        </p>
      <?php elseif ($autoencoder['status'] === 'empty'): ?>
        <p>Nenhuma leitura disponível para calcular erros de reconstrução.</p>
      <?php else: ?>
        <p>
          Não foi possível consultar o modelo autoencoder no momento.
          <?php if (!empty($autoencoder['detail'])): ?>
            Detalhe: <?php echo htmlspecialchars($autoencoder['detail'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
          <?php endif; ?>
        </p>
      <?php endif; ?>
    </section>
  <?php endif; ?>

  <?php if ($isAdmin): ?>
    <section class="dashboard-grid" aria-label="Resumo de usuários">
      <article class="dashboard-card">
        <h3>Total de usuários</h3>
        <p class="dashboard-kpi"><?php echo (int) $totalUsers; ?></p>
      </article>
      <article class="dashboard-card">
        <h3>Admins</h3>
        <p class="dashboard-kpi"><?php echo (int) $admins; ?></p>
      </article>
      <article class="dashboard-card">
        <h3>Novos nos últimos 7 dias</h3>
        <p class="dashboard-kpi"><?php echo (int) $recent7; ?></p>
      </article>
    </section>

    <section class="dashboard-chart" aria-label="Usuários por tipo">
      <h3>Usuários por tipo</h3>
      <div class="dashboard-chart-bars" role="img"
           aria-label="Gráfico de barras mostrando a quantidade de usuários admins e usuários comuns">
        <div class="dashboard-chart-bar">
          <div class="dashboard-bar dashboard-bar-admin"
               style="height: <?php echo (float) $adminPercent; ?>%;"></div>
          <span class="dashboard-bar-label">Admins (<?php echo (int) $admins; ?>)</span>
        </div>
        <div class="dashboard-chart-bar">
          <div class="dashboard-bar dashboard-bar-user"
               style="height: <?php echo (float) $userPercent; ?>%;"></div>
          <span class="dashboard-bar-label">Usuários (<?php echo (int) $regulars; ?>)</span>
        </div>
      </div>
    </section>

    <section class="dashboard-table" aria-label="Usuários mais recentes">
      <h3>Usuários mais recentes</h3>
      <?php if (empty($recentUsers)): ?>
        <p>Não há usuários cadastrados ainda.</p>
      <?php else: ?>
        <div class="dashboard-table-wrapper">
          <table>
            <thead>
              <tr>
                <th scope="col">ID</th>
                <th scope="col">Nome</th>
                <th scope="col">Email</th>
                <th scope="col">Role</th>
                <th scope="col">Criado em</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentUsers as $u): ?>
                <tr>
                  <td><?php echo (int) $u['id']; ?></td>
                  <td><?php echo htmlspecialchars($u['name'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($u['email'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($u['role'] ?? 'user', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($u['created_at'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>
  <?php endif; ?>

  <script>
    (function () {
      const data = <?php echo json_encode($chartData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
      if (!data.labels.length || typeof Chart === 'undefined') return;

      const commonOptions = {
        responsive: true,
        maintainAspectRatio: true,
        interaction: {
          mode: 'index',
          intersect: false
        },
        plugins: {
          legend: { display: true },
          tooltip: { enabled: true }
        }
      };

      const phTempCanvas = document.getElementById('chart-ph-temp');
      if (phTempCanvas && phTempCanvas.getContext) {
        new Chart(phTempCanvas.getContext('2d'), {
          type: 'line',
          data: {
            labels: data.labels,
            datasets: [
              {
                label: 'pH',
                data: data.ph,
                borderColor: '#4c9cc8',
                backgroundColor: 'rgba(76, 156, 200, 0.15)',
                tension: 0.2,
                yAxisID: 'y'
              },
              {
                label: 'Temp solução (°C)',
                data: data.solution_temp_c,
                borderColor: '#90ee90',
                backgroundColor: 'rgba(144, 238, 144, 0.15)',
                tension: 0.2,
                yAxisID: 'y1'
              }
            ]
          },
          options: {
            ...commonOptions,
            scales: {
              x: {
                display: true,
                title: {
                  display: true,
                  text: 'Data/hora'
                }
              },
              y: {
                display: true,
                position: 'left',
                title: {
                  display: true,
                  text: 'pH'
                }
              },
              y1: {
                display: true,
                position: 'right',
                grid: {
                  drawOnChartArea: false
                },
                title: {
                  display: true,
                  text: 'Temp solução (°C)'
                }
              }
            }
          }
        });
      }

      const lightHumCanvas = document.getElementById('chart-light-hum');
      if (lightHumCanvas && lightHumCanvas.getContext) {
        new Chart(lightHumCanvas.getContext('2d'), {
          type: 'line',
          data: {
            labels: data.labels,
            datasets: [
              {
                label: 'Luz (lux)',
                data: data.light_lux,
                borderColor: '#f9a825',
                backgroundColor: 'rgba(249, 168, 37, 0.15)',
                tension: 0.2,
                yAxisID: 'y'
              },
              {
                label: 'Umidade (%)',
                data: data.humidity_percent,
                borderColor: '#8e24aa',
                backgroundColor: 'rgba(142, 36, 170, 0.15)',
                tension: 0.2,
                yAxisID: 'y1'
              }
            ]
          },
          options: {
            ...commonOptions,
            scales: {
              x: {
                display: true,
                title: {
                  display: true,
                  text: 'Data/hora'
                }
              },
              y: {
                display: true,
                position: 'left',
                title: {
                  display: true,
                  text: 'Luz (lux)'
                }
              },
              y1: {
                display: true,
                position: 'right',
                grid: {
                  drawOnChartArea: false
                },
                title: {
                  display: true,
                  text: 'Umidade (%)'
                }
              }
            }
          }
        });
      }

      const reconCanvas = document.getElementById('chart-recon-error');
      if (reconCanvas && reconCanvas.getContext && Array.isArray(data.recon_error) && data.recon_error.length) {
        new Chart(reconCanvas.getContext('2d'), {
          type: 'line',
          data: {
            labels: data.labels,
            datasets: [
              {
                label: 'Erro de reconstrução',
                data: data.recon_error,
                borderColor: '#e53935',
                backgroundColor: 'rgba(229, 57, 53, 0.15)',
                tension: 0.2,
              }
            ]
          },
          options: {
            ...commonOptions,
            scales: {
              x: {
                display: true,
                title: {
                  display: true,
                  text: 'Data/hora'
                }
              },
              y: {
                display: true,
                position: 'left',
                title: {
                  display: true,
                  text: 'Erro de reconstrução'
                }
              }
            }
          }
        });
      }
    })();
  </script>
</main>
