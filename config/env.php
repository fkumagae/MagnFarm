<?php
// config/env.php
// Configurações simples do banco para uso local (XAMPP)
// Mantenha este arquivo fora do controle de versão em produção (adicionar a .gitignore).

declare(strict_types=1);

// Altere estes valores conforme sua instalação XAMPP/MySQL
if (!defined('DB_HOST')) define('DB_HOST', '127.0.0.1');
if (!defined('DB_NAME')) define('DB_NAME', 'magnfarm');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', ''); // XAMPP geralmente usa senha vazia
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

// DSN PDO
if (!defined('DB_DSN')) define('DB_DSN', 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET);

// Pequeno helper para ler variáveis de ambiente (opcional)
function env(string $key, $default = null) {
    $v = getenv($key);
    return $v !== false ? $v : $default;
}

// Exemplo de uso:
// require_once __DIR__ . '/env.php';
// $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, [...]);

?>