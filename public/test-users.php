<?php
// public/test-users.php
// Script de teste manual para o model User.

require __DIR__ . '/../app/core/db.php';
require __DIR__ . '/../app/models/User.php';

header('Content-Type: text/plain; charset=utf-8');

// Gera e-mail único para teste (timestamp)
$timestamp = time();
$testEmail = "test+{$timestamp}@example.local";
$testPassword = 'Teste1234';
$testName = 'Usuário Teste';

echo "=== Teste do model User ===\n";

echo "1) Tentando criar usuário: {$testEmail}\n";
$id = User::create([
    'email' => $testEmail,
    'password' => $testPassword,
    'name' => $testName,
]);

if ($id === false) {
    echo "Criação falhou (email duplicado ou validação).\n";
} else {
    echo "Usuário criado com id: {$id}\n";
}

echo "\n2) Buscando usuário por email...\n";
$user = User::findByEmail($testEmail);
if ($user === null) {
    echo "Usuário não encontrado.\n";
} else {
    // não exibe password_hash em texto claro
    $safe = [
        'id' => $user['id'],
        'email' => $user['email'],
        'name' => $user['name'],
        'created_at' => $user['created_at'],
        'password_hash_exists' => !empty($user['password_hash']) ? true : false,
    ];
    echo "Usuário: " . print_r($safe, true) . "\n";
}

echo "\n3) Verificando senha (correta)...\n";
$ok = User::verifyPassword($testEmail, $testPassword);
echo $ok ? "verifyPassword => true\n" : "verifyPassword => false\n";

echo "\n4) Verificando senha (errada)...\n";
$ok2 = User::verifyPassword($testEmail, 'senhaerrada');
echo $ok2 ? "verifyPassword(senhaerrada) => true\n" : "verifyPassword(senhaerrada) => false\n";

echo "\n5) Listando últimos usuários (limit 5):\n";
$all = User::all();
$recent = array_slice($all, 0, 5);
echo print_r($recent, true);

echo "\nTeste finalizado.\n";
