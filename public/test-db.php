<?php
// public/test-db.php - quick DB connectivity test
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../app/core/db.php';

try {
    $pdo = db();
    echo "PDO connection established.\n";

    // current database
    $stmt = $pdo->query('SELECT DATABASE() AS dbname');
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Current database: " . ($row['dbname'] ?? '(unknown)') . "\n";

    // check users table exists and count rows
    $count = null;
    try {
        $stmt2 = $pdo->query('SELECT COUNT(*) AS c FROM users');
        $r2 = $stmt2->fetch(PDO::FETCH_ASSOC);
        $count = $r2['c'] ?? 'N/A';
        echo "Users table exists. Row count: " . $count . "\n";
    } catch (Throwable $e) {
        echo "Users table check failed: " . $e->getMessage() . "\n";
    }

    // show some PDO attributes
    echo "PDO driver: " . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . "\n";
    echo "PDO server version: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n";

} catch (Throwable $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
}

// Helpful note for next steps
echo "\nIf connection fails: check C:/xampp/mysql is running, verify config/env.php DB_* values, and check php error logs.\n";
