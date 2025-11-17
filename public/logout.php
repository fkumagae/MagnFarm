<?php
require_once __DIR__ . '/../app/core/session.php';
require_once __DIR__ . '/../app/core/csrf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check_request()) {
        flash('error', 'Requisição inválida (CSRF).');
        header('Location: dashboard.php');
        exit;
    }
    logout_user();
}
flash('success', 'Você saiu com sucesso.');
header('Location: login.php');
exit;
