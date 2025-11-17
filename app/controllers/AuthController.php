<?php
// Simple AuthController to centralize register/login/logout logic

require_once __DIR__ . '/../core/session.php';
require_once __DIR__ . '/../core/csrf.php';
require_once __DIR__ . '/../models/User.php';

class AuthController
{
    public static function handleRegister(): ?array
    {
        // if already logged in, redirect
        if (is_logged_in()) {
            header('Location: dashboard.php');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return null;
        }

        if (!csrf_check_request()) {
            flash('error', 'Formulário inválido (CSRF).');
            header('Location: register.php');
            exit;
        }

        $errors = [];
        $email = trim($_POST['email'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email inválido.';
        }
        if (strlen($password) < 6) {
            $errors[] = 'Senha deve ter pelo menos 6 caracteres.';
        }

        if (!empty($errors)) {
            return ['errors' => $errors];
        }

        try {
            $id = User::create(['email' => $email, 'name' => $name, 'password' => $password]);
        } catch (Throwable $e) {
            // capture DB/other errors
            return ['errors' => ['Erro ao criar usuário: ' . $e->getMessage()]];
        }

        if ($id) {
            $user = User::findByEmail($email);
            if ($user) {
                login_user($user);
                flash('success', 'Registrado com sucesso.');
                // redirect to front-controller (site pages) after register
                header('Location: index.php');
                exit;
            }
        }

        return ['errors' => ['Não foi possível criar o usuário (email pode já existir).']];
    }

    public static function handleLogin(): ?array
    {
        if (is_logged_in()) {
            header('Location: dashboard.php');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return null;
        }

        if (!csrf_check_request()) {
            flash('error', 'Formulário inválido (CSRF).');
            header('Location: login.php');
            exit;
        }

        $errors = [];
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $user = User::findByEmail($email);
        if (!$user) {
            $errors[] = 'Usuário não encontrado.';
            return ['errors' => $errors];
        }

        if (User::verifyPassword($email, $password)) {
            login_user($user);
            // If user requested "remember me", set a signed cookie valid for 30 days.
            if (!empty($_POST['remember'])) {
                $expiry = time() + (60 * 60 * 24 * 30); // 30 days
                $data = $user['id'] . '|' . $expiry;
                // Use the user's password_hash as key so cookie invalidates if password changes.
                $sig = hash_hmac('sha256', $data, $user['password_hash']);
                $cookieValue = base64_encode($data . '|' . $sig);
                $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
                setcookie('magalface_rem', $cookieValue, [
                    'expires' => $expiry,
                    'path' => '/',
                    'domain' => $_SERVER['HTTP_HOST'] ?? '',
                    'secure' => $secure,
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]);
            }

            flash('success', 'Bem-vindo de volta!');

            // If we previously saved a requested URL, return there (basic safety checks).
            $redirectTo = 'index.php?action=home';
            if (!empty($_SESSION['return_to'])) {
                $rt = $_SESSION['return_to'];
                unset($_SESSION['return_to']);
                $parsed = parse_url($rt);
                // Reject absolute URLs (with scheme/host) for safety; allow relative/absolute-paths on same host.
                if (empty($parsed['scheme']) && empty($parsed['host']) && is_string($rt) && strlen($rt) > 0) {
                    $redirectTo = $rt;
                }
            }

            header('Location: ' . $redirectTo);
            exit;
        }

        // use flash for errors and redirect to login page
        flash('error', 'Senha incorreta.');
        header('Location: login.php');
        exit;
    }

    public static function logout(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrf_check_request()) {
                flash('error', 'Requisição inválida (CSRF).');
                header('Location: dashboard.php');
                exit;
            }
            logout_user();
        }

        header('Location: login.php');
        exit;
    }
}
