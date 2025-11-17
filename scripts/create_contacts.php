<?php
// scripts/create_contacts.php
// Small helper to run the sql/create_contacts.sql DDL using the project's DB config.
// Usage (CLI): php scripts/create_contacts.php
// Usage (browser): visit http://localhost/Magalface_v2/scripts/create_contacts.php

declare(strict_types=1);

require_once __DIR__ . '/../app/core/db.php';

try {
    $sql = file_get_contents(__DIR__ . '/../sql/create_contacts.sql');
    if ($sql === false) {
        throw new RuntimeException('Não foi possível ler o arquivo SQL: sql/create_contacts.sql');
    }

    $pdo = db();
    // split on ; that end statements. Keep it simple for this small DDL file.
    $stmts = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($stmts as $stmt) {
        if ($stmt === '') continue;
        $pdo->exec($stmt);
    }

    echo "OK: tabela 'contacts' criada (ou já existia).\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo "ERRO: " . $e->getMessage() . "\n";
    exit(1);
}

?>
