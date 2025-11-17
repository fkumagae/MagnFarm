<?php
// public/contact.php
// Recebe POST do formulário de contato, valida, insere na tabela `contacts` e responde JSON ou redirect.

require_once __DIR__ . '/../app/core/session.php';
require_once __DIR__ . '/../app/core/csrf.php';
require_once __DIR__ . '/../app/core/db.php';

// TEMP DEBUG: log CSRF/session values to help debug invalid token issues.
// Remove these logs after debugging.
error_log('[DEBUG][contact.php] session_name=' . session_name() . ' session_id=' . session_id());
error_log('[DEBUG][contact.php] SESSION csrf_token=' . ($_SESSION['csrf_token'] ?? 'NULL'));
error_log('[DEBUG][contact.php] POST csrf_token=' . ($_POST['csrf_token'] ?? 'NULL'));
error_log('[DEBUG][contact.php] HTTP_COOKIE=' . ($_SERVER['HTTP_COOKIE'] ?? 'NULL'));

// Helper to respond JSON and exit
function json_response($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // only POST allowed
    if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false || !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        json_response(['error' => 'Method not allowed'], 405);
    }
    header('Location: index.php');
    exit;
}

if (!csrf_check_request()) {
    if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false || !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        json_response(['error' => 'Formulário inválido (CSRF).'], 400);
    }
    flash('error', 'Formulário inválido (CSRF).');
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
    exit;
}

$nome = trim((string)($_POST['nome'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$mensagem = trim((string)($_POST['mensagem'] ?? ''));

$errors = [];
if ($nome === '') $errors[] = 'Nome é obrigatório.';
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'E-mail inválido.';
if ($mensagem === '' || strlen($mensagem) < 5) $errors[] = 'Mensagem muito curta.';

if (!empty($errors)) {
    if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false || !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        json_response(['errors' => $errors], 422);
    }
    // Non-AJAX fallback: show first error via flash
    flash('error', $errors[0]);
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
    exit;
}

try {
    $pdo = db();
    $sql = 'INSERT INTO contacts (nome, email, mensagem, created_at) VALUES (:nome, :email, :mensagem, NOW())';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':nome' => $nome, ':email' => $email, ':mensagem' => $mensagem]);
    $insertId = (int)$pdo->lastInsertId();
} catch (Throwable $e) {
    if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false || !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        json_response(['error' => 'Erro ao salvar: ' . $e->getMessage()], 500);
    }
    flash('error', 'Erro ao salvar mensagem. Tente novamente.');
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
    exit;
}

// Success
if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false || !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    json_response(['success' => true, 'id' => $insertId, 'message' => 'Mensagem enviada com sucesso.']);
}

flash('success', 'Mensagem enviada com sucesso. Obrigado pelo contato!');
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
exit;
