<?php
// app/core/db.php
// Função simples para obter uma conexão PDO usando as constantes definidas em config/env.php
// Comentários curtos em português em pontos críticos do código.

declare(strict_types=1);

require_once __DIR__ . '/../../config/env.php';

/**
 * Retorna uma instância PDO única (singleton dentro da execução).
 * Usa as constantes definidas em config/env.php (DB_DSN, DB_USER, DB_PASS).
 * Lança RuntimeException em caso de falha.
 *
 * @return PDO
 * @throws RuntimeException
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    try {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false, // use prepared statements nativos
            PDO::ATTR_PERSISTENT => false,
        ];

        $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, $options);

        // Garante charset correto
        $pdo->exec("SET NAMES '" . DB_CHARSET . "' COLLATE 'utf8mb4_unicode_ci'");

        return $pdo;
    } catch (PDOException $e) {
        // Em produção, logue o erro e mostre mensagem genérica.
        throw new RuntimeException('Erro ao conectar ao banco de dados: ' . $e->getMessage());
    }
}
