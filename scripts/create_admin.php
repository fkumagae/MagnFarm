<?php
// scripts/create_admin.php
// Usage: php scripts/create_admin.php email password "Full Name"

require_once __DIR__ . '/../app/core/session.php';
require_once __DIR__ . '/../app/models/User.php';

if (PHP_SAPI !== 'cli') {
    echo "Run this script from CLI: php scripts/create_admin.php email password \"Full Name\"\n";
    exit(1);
}

$argv_copy = $argv;
array_shift($argv_copy); // script name

if (count($argv_copy) < 2) {
    echo "Usage: php scripts/create_admin.php email password \"Full Name\"\n";
    exit(1);
}

[$email, $password] = [$argv_copy[0], $argv_copy[1]];
$name = $argv_copy[2] ?? 'Admin';

$email = trim($email);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "Email inválido: $email\n";
    exit(1);
}

if (strlen($password) < 6) {
    echo "Senha muito curta. Use pelo menos 6 caracteres.\n";
    exit(1);
}

try {
    $existing = User::findByEmail($email);
    if ($existing) {
        // update password and role
        $pdo = db();
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $sql = 'UPDATE users SET password_hash = :hash, role = :role WHERE id = :id';
        $stmt = $pdo->prepare($sql);
        $ok = $stmt->execute([':hash' => $hash, ':role' => 'admin', ':id' => $existing['id']]);
        if ($ok) {
            echo "Usuário existente atualizado e promovido a admin: {$email}\n";
            exit(0);
        } else {
            echo "Falha ao atualizar usuário existente.\n";
            exit(2);
        }
    } else {
        $id = User::create(['email' => $email, 'password' => $password, 'name' => $name, 'role' => 'admin']);
        if ($id) {
            echo "Admin criado com sucesso (id={$id}) e promovido a admin: {$email}\n";
            exit(0);
        } else {
            echo "Falha ao criar admin. Verifique erros no banco ou duplicidade de email.\n";
            exit(3);
        }
    }
} catch (Throwable $e) {
    echo "Erro: " . $e->getMessage() . "\n";
    exit(4);
}
