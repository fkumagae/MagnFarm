<?php
require_once __DIR__ . '/../app/core/session.php';
require_once __DIR__ . '/../app/core/csrf.php';
require_once __DIR__ . '/../app/models/User.php';

// exige autenticação e privilégios
require_auth('login.php?action=admin');
require_admin('login.php?action=admin');

// manipula ações POST (promote/demote/delete)
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
    if ($target === (int)($me['id'] ?? 0)) {
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
    }

    header('Location: admin.php');
    exit;
}

// GET: mostra a interface
$users = User::all();
require_once __DIR__ . '/../app/views/partials/header.php';
require_once __DIR__ . '/../app/views/admin.php';
require_once __DIR__ . '/../app/views/partials/footer.php';
