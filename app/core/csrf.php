<?php
// Simple CSRF helpers. Depends on app/core/session.php to have started session.
// Functions:
// - csrf_token(): returns a session-backed token (creates if missing)
// - csrf_check($token): verifies a submitted token (uses hash_equals)
// - csrf_field(): returns an HTML hidden input with the token (escaped)
// - csrf_check_request(): convenience that checks $_POST['csrf_token'] and returns bool

require_once __DIR__ . '/session.php';

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            // 32 bytes -> 64 hex chars
            try {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            } catch (Exception $e) {
                // random_bytes should be available on PHP 7+, if not fallback
                $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
            }
        }
        return (string) $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_check')) {
    function csrf_check(?string $token): bool
    {
        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }
        // Use hash_equals to mitigate timing attacks
        $ok = hash_equals($_SESSION['csrf_token'], $token);
        // NOTE: we keep the token in session after successful check instead of
        // unsetting it. This avoids intermittent "NULL" token problems when
        // the token was generated on a previous request but the session store
        // wasn't available or was rotated. Keeping the token reduces the risk
        // of false negatives at the cost of allowing reuse during the session.
        // If you prefer one-time tokens, re-enable the unset() here.
        return $ok;
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        $t = htmlspecialchars(csrf_token(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return '<input type="hidden" name="csrf_token" value="' . $t . '">';
    }
}

if (!function_exists('csrf_check_request')) {
    function csrf_check_request(): bool
    {
        $token = null;
        if (isset($_POST['csrf_token'])) {
            $token = (string) $_POST['csrf_token'];
        }
        return csrf_check($token);
    }
}
