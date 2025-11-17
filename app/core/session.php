<?php
// app/core/session.php
// Helpers de sessão e inicialização segura da sessão.
// Comentários curtos em português em pontos críticos.

declare(strict_types=1);

// Inicia sessão se ainda não iniciada
if (session_status() === PHP_SESSION_NONE) {
    // cookie_secure só em HTTPS
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

    // parâmetros seguros para cookie de sessão
    // Normalize host to avoid including a port (e.g. "localhost:8080") in the
    // cookie domain. Including a port in the cookie domain can cause the
    // browser to ignore the cookie which leads to missing session data.
    $rawHost = $_SERVER['HTTP_HOST'] ?? '';
    $host = $rawHost === '' ? '' : preg_replace('/:\d+$/', '', $rawHost);

    $cookieParams = [
        'lifetime' => 0,           // até fechar o navegador
        'path' => '/',
        'domain' => $host,
        'secure' => $secure,
        'httponly' => true,        // evita acesso via JS
        'samesite' => 'Lax',       // proteção CSRF básica
    ];

    // nome da sessão específico da aplicação
    session_name('magalface_sid');

    // aplica params compatíveis com a versão do PHP
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params($cookieParams);
    } else {
        session_set_cookie_params(
            $cookieParams['lifetime'],
            $cookieParams['path'],
            $cookieParams['domain'],
            $cookieParams['secure'],
            $cookieParams['httponly']
        );
    }

    session_start();
}

// If the user is not logged in but has a remember-me cookie, attempt to restore session.
if (!is_logged_in() && !empty($_COOKIE['magalface_rem'])) {
    $cookie = $_COOKIE['magalface_rem'];
    $decoded = base64_decode($cookie, true);
    if ($decoded !== false) {
        $parts = explode('|', $decoded);
        if (count($parts) === 3) {
            [$uid, $expiry, $sig] = $parts;
            if (ctype_digit((string)$uid) && ctype_digit((string)$expiry) && (int)$expiry > time()) {
                // lazy-load User model to avoid circular require in some contexts
                require_once __DIR__ . '/../models/User.php';
                $user = User::findById((int)$uid);
                if ($user && !empty($user['password_hash'])) {
                    $data = $uid . '|' . $expiry;
                    $expected = hash_hmac('sha256', $data, $user['password_hash']);
                    if (hash_equals($expected, $sig)) {
                        // login silently
                        login_user($user);
                    } else {
                        // invalid signature -> clear cookie
                        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
                        setcookie('magalface_rem', '', [
                            'expires' => time() - 3600,
                            'path' => '/',
                            'domain' => $host,
                            'secure' => $secure,
                            'httponly' => true,
                            'samesite' => 'Lax',
                        ]);
                    }
                }
            }
        }
    }
}

/**
 * Mensagens flash simples: se $message fornecido -> seta; se null -> retorna e remove.
 * Uso: flash('success', 'Registrado com sucesso'); // set
 *      $msg = flash('success'); // get e remove
 */
function flash(string $key, $message = null)
{
    if (!isset($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }

    if ($message === null) {
        if (isset($_SESSION['flash'][$key])) {
            $m = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
            return $m;
        }
        return null;
    }

    $_SESSION['flash'][$key] = $message;
    return null;
}

/**
 * Verifica se usuário está autenticado (presença de id em session)
 */
function is_logged_in(): bool
{
    return !empty($_SESSION['user']['id']);
}

/**
 * Registra usuário autenticado na sessão.
 * Recebe um array mínimo com 'id' e 'email' (pode conter 'name').
 */
function login_user(array $user): void
{
    $_SESSION['user'] = [
        'id' => $user['id'] ?? null,
        'email' => $user['email'] ?? null,
        'name' => $user['name'] ?? null,
        'role' => $user['role'] ?? ($user['role'] ?? 'user'),
    ];
    // previne fixation attack
    session_regenerate_id(true);
}

/**
 * Desloga usuário: limpa sessão e cookie
 */
function logout_user(): void
{
    // limpa dados
    $_SESSION = [];

    // remove cookie de sessão se existir
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'] ?? '/',
            $params['domain'] ?? '',
            $params['secure'] ?? false,
            $params['httponly'] ?? true
        );
    }

    // remove remember-me cookie if set
    if (!empty($_COOKIE['magalface_rem'])) {
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        // use normalized host to avoid port issues
        $rawHost = $_SERVER['HTTP_HOST'] ?? '';
        $host = $rawHost === '' ? '' : preg_replace('/:\d+$/', '', $rawHost);
        setcookie('magalface_rem', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => $host,
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    // destrói sessão
    session_destroy();
}

/**
 * Protege páginas: se não autenticado, seta flash e redireciona para login.
 * O redirect padrão é relativo e assume que o roteador público é `index.php`.
 */
function require_auth(string $redirect = 'index.php?action=login'): void
{
    if (!is_logged_in()) {
        // remember requested URL so we can return after login
        if (!empty($_SERVER['REQUEST_URI'])) {
            // store full request URI
            $_SESSION['return_to'] = $_SERVER['REQUEST_URI'];
        }

        flash('error', 'É necessário fazer login para acessar essa página.');
        header('Location: ' . $redirect);
        exit;
    }
}

/**
 * Retorna o usuário atual (array) ou null.
 */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

/**
 * Verifica se o usuário atual é admin (role == 'admin').
 */
function is_admin(): bool
{
    return !empty($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin';
}

/**
 * Protege páginas que exigem permissão de administrador.
 * Se não for admin, redireciona para a página passada (padrão: login).
 */
function require_admin(string $redirect = 'index.php?action=login'): void
{
    if (!is_admin()) {
        // preserve return_to for after login
        if (!empty($_SERVER['REQUEST_URI'])) {
            $_SESSION['return_to'] = $_SERVER['REQUEST_URI'];
        }
        flash('error', 'É necessário ter privilégios de administrador para acessar essa página.');
        header('Location: ' . $redirect);
        exit;
    }
}

?>