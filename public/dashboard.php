<?php
require_once __DIR__ . '/../app/core/session.php';
require_once __DIR__ . '/../app/core/csrf.php';

// If accessed directly, redirect to login with an action query so return behavior is consistent
require_auth('login.php?action=dashboard');
$user = current_user();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h1>Dashboard</h1>
    <p>Bem-vindo, <?php echo htmlspecialchars($user['name'] ?? $user['email'], ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); ?>.</p>
    <form method="post" action="logout.php" aria-label="Sair da sessão atual">
        <?php echo csrf_field(); ?>
        <button type="submit">Sair</button>
    </form>
</body>
</html>
