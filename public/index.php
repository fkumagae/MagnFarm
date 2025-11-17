<?php
// Front controller: serves views from app/views based on ?action=...
require_once __DIR__ . '/../app/core/session.php';
require_once __DIR__ . '/../app/core/csrf.php';

$action = $_GET['action'] ?? 'home';

$allowed = [
	'home' => 'home.php',
	'projeto' => 'projeto.php',
	'planejamento' => 'planejamento.php',
	'fluxograma' => 'fluxograma.php',
	'esquemas' => 'esquemas.php',
	'dispositivos' => 'dispositivos.php',
	'dashboard' => 'dashboard.php',
];

if (!array_key_exists($action, $allowed)) {
	http_response_code(404);
	echo "Página não encontrada.";
	exit;
}

// protect certain actions that require authentication
$protected = [
	'projeto',
	'planejamento',
	'fluxograma',
	'esquemas',
	'dispositivos',
	'dashboard',
];

if (in_array($action, $protected, true)) {
	// redirect user to login with the requested action so URL becomes login.php?action=xyz
	require_auth('login.php?action=' . urlencode($action));
}

$viewFile = __DIR__ . '/../app/views/' . $allowed[$action];
if (!file_exists($viewFile)) {
	http_response_code(500);
	echo "Erro: view ausente.";
	exit;
}

// include header partial, view content (content files should be body-only), and footer
require_once __DIR__ . '/../app/views/partials/header.php';
include $viewFile;
require_once __DIR__ . '/../app/views/partials/footer.php';
exit;
