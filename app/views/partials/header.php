<?php
// header partial: assumes session and CSRF helpers are already loaded
// compute a base path relative to the currently executing script so
// asset links work whether this partial is included from files in
// the `public/` folder or served through the front controller.
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
if ($base === '') { $base = '/'; }
$lang = function_exists('current_lang') ? current_lang() : 'pt-BR';
?>
<!doctype html>
<html lang="<?php echo htmlspecialchars($lang, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); ?>">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Magalface</title>
    <link rel="stylesheet" href="<?php echo $base; ?>/css/style.css" />
    <script>
      (function () {
        try {
          var stored = localStorage.getItem('theme');
          var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
          var theme = stored || (prefersDark ? 'dark' : 'light');
          document.documentElement.setAttribute('data-theme', theme);
        } catch (e) {}
      })();
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  </head>
  <body>
    <header role="banner">
      <div class="topo-barra">
        <div class="title-area">
          <img src="<?php echo $base; ?>/img/logo_bola.png" alt="Logo Magalface" class="logo-site" style="cursor:pointer" onclick="document.getElementById('form-contato')?.scrollIntoView({behavior: 'smooth'})" />
          <div class="title-and-name">
            <h1><a href="<?php echo $base; ?>/index.php?action=home" class="site-title" title="Voltar para a pǭgina inicial" style="color:#fff !important; text-decoration:none !important;">Magalface</a></h1>
            <?php if (is_logged_in()):
              $user = current_user();
              $displayName = $user['name'] ?? $user['nome'] ?? $user['email'] ?? '';
            ?>
              <div class="user-name"><?php echo htmlspecialchars($displayName, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); ?></div>
            <?php endif; ?>
          </div>
        </div>

        <div class="top-right-auth">
            <?php if (is_logged_in()): ?>
            <form method="post" action="logout.php" class="nav-auth-form">
              <?php echo csrf_field(); ?>
              <button type="submit"><?php echo function_exists('t') ? t('action.logout') : 'Sair'; ?></button>
            </form>
            <?php else: ?>
            <div class="auth-links">
              <a href="<?php echo $base; ?>/login.php"><?php echo function_exists('t') ? t('action.login') : 'Entrar'; ?></a>
               |
              <a href="<?php echo $base; ?>/register.php"><?php echo function_exists('t') ? t('action.register') : 'Registrar'; ?></a>
            </div>
          <?php endif; ?>

          <?php
            $currentLang = function_exists('current_lang') ? current_lang() : 'pt';
            $qs = $_GET;
            unset($qs['lang']);
            $baseUrl = strtok($_SERVER['REQUEST_URI'], '?');
            $mkUrl = function (string $langCode) use ($baseUrl, $qs): string {
                $params = $qs;
                $params['lang'] = $langCode;
                return htmlspecialchars($baseUrl . '?' . http_build_query($params), ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
            };
          ?>
          <div class="header-toggles">
            <div class="lang-toggle" aria-label="Selecionar idioma">
              <a href="<?php echo $mkUrl('pt'); ?>" class="<?php echo $currentLang === 'pt' ? 'is-active' : ''; ?>">
                <?php echo function_exists('t') ? t('lang.pt') : 'PT'; ?>
              </a>
              |
              <a href="<?php echo $mkUrl('en'); ?>" class="<?php echo $currentLang === 'en' ? 'is-active' : ''; ?>">
                <?php echo function_exists('t') ? t('lang.en') : 'EN'; ?>
              </a>
            </div>
            <button type="button" class="theme-toggle" id="theme-toggle" aria-label="Alternar tema claro/escuro">
              &#9788;
            </button>
          </div>

          <button class="menu-toggle" aria-expanded="false" aria-controls="site-menu" aria-label="Abrir menu">
            <svg width="28" height="28" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M3 6h18M3 12h18M3 18h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </button>
        </div>
      </div>

      <nav class="nav-fina" aria-label="Navega��ǜo principal">
        <ul id="site-menu">
          <li><a href="<?php echo $base; ?>/index.php?action=projeto"><?php echo function_exists('t') ? t('nav.projeto') : 'Projeto'; ?></a></li>
          <li><a href="<?php echo $base; ?>/index.php?action=planejamento"><?php echo function_exists('t') ? t('nav.planejamento') : 'Planejamento'; ?></a></li>
          <li><a href="<?php echo $base; ?>/index.php?action=fluxograma"><?php echo function_exists('t') ? t('nav.fluxograma') : 'Fluxograma'; ?></a></li>
          <li><a href="<?php echo $base; ?>/index.php?action=esquemas"><?php echo function_exists('t') ? t('nav.esquemas') : 'Esquemas eletr��nicos'; ?></a></li>
          <li><a href="<?php echo $base; ?>/index.php?action=dispositivos"><?php echo function_exists('t') ? t('nav.dispositivos') : 'Dispositivos'; ?></a></li>
          <li><a href="<?php echo $base; ?>/index.php?action=dashboard"><?php echo function_exists('t') ? t('nav.dashboard') : 'Dashboard'; ?></a></li>
        </ul>
      </nav>
    </header>
    <?php
    // Opt-in debug: append ?debug=session to any page URL to inspect current session user
    if (!empty($_GET['debug']) && $_GET['debug'] === 'session') {
        echo '<div style="max-width:980px;margin:1rem auto;padding:1rem;background:#fff;border:1px solid #ccc;color:#000;">';
        echo '<strong>DEBUG: $_SESSION[\'user\']</strong><pre>' . htmlspecialchars(print_r($_SESSION['user'] ?? null, true), ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8') . '</pre>';
        echo '</div>';
    }
    ?>
    <script>
      (function () {
        var btn = document.getElementById('theme-toggle');
        if (!btn) return;
        btn.addEventListener('click', function () {
          var current = document.documentElement.getAttribute('data-theme') || 'light';
          var next = current === 'dark' ? 'light' : 'dark';
          document.documentElement.setAttribute('data-theme', next);
          try { localStorage.setItem('theme', next); } catch (e) {}
        });
      })();
    </script>
