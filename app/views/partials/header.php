<?php
// header partial: assumes session and CSRF helpers are already loaded
// compute a base path relative to the currently executing script so
// asset links work whether this partial is included from files in
// the `public/` folder or served through the front controller.
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
if ($base === '') { $base = '/'; }
?>
<!doctype html>
<html lang="pt-BR">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Magalface</title>
    <link rel="stylesheet" href="<?php echo $base; ?>/css/style.css" />
  </head>
  <body>
    <header>
      <div class="topo-barra">
        <div class="title-area">
          <img src="<?php echo $base; ?>/img/logo_bola.png" alt="Logo Magalface" class="logo-site" style="cursor:pointer" onclick="document.getElementById('form-contato')?.scrollIntoView({behavior: 'smooth'})" />
          <div class="title-and-name">
            <h1><a href="<?php echo $base; ?>/index.php?action=home" class="site-title" title="Voltar para a página inicial" style="color:#fff !important; text-decoration:none !important;">Magalface</a></h1>
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
            <form method="post" action="logout.php" class="nav-auth-form"><?php echo csrf_field(); ?><button type="submit">Sair</button></form>
            <?php else: ?>
            <div class="auth-links"><a href="<?php echo $base; ?>/login.php">Entrar</a> | <a href="<?php echo $base; ?>/register.php">Registrar</a></div>
          <?php endif; ?>
          <button class="menu-toggle" aria-expanded="false" aria-controls="site-menu" aria-label="Abrir menu">
            <svg width="28" height="28" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M3 6h18M3 12h18M3 18h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </button>
        </div>
      </div>

<?php
// build auth HTML to place inside the nav as a right-aligned list item
$flashSuccess = flash('success');
$flashError = flash('error');
$authHtml = '';
if (is_logged_in()) {
  $user = current_user();
  // try common keys for the display name: 'name' (english) or 'nome' (pt-BR)
  $displayName = $user['name'] ?? $user['nome'] ?? $user['email'] ?? '';
  $authHtml .= '<li class="nav-auth">';
  $authHtml .= '<span class="nav-auth-user">' . htmlspecialchars($displayName, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8') . '</span>';
  $authHtml .= '<form method="post" action="logout.php" class="nav-auth-form">' . csrf_field() . '<button type="submit">Sair</button></form>';
  $authHtml .= '</li>';
} else {
  $authHtml .= '<li class="nav-auth"><a href="' . $base . '/login.php">Entrar</a> | <a href="' . $base . '/register.php">Registrar</a></li>';
}
?>

      <nav class="nav-fina">
        <ul id="site-menu">
          <li><a href="<?php echo $base; ?>/index.php?action=projeto">Projeto</a></li>
          <li><a href="<?php echo $base; ?>/index.php?action=planejamento">Planejamento</a></li>
          <li><a href="<?php echo $base; ?>/index.php?action=fluxograma">Fluxograma</a></li>
          <li><a href="<?php echo $base; ?>/index.php?action=esquemas">Esquemas eletrônicos</a></li>
          <li><a href="<?php echo $base; ?>/index.php?action=dispositivos">Dispositivos</a></li>
          <li><a href="<?php echo $base; ?>/index.php?action=dashboard">Dashboard</a></li>
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
