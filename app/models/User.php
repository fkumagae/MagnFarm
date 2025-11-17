<?php
// app/models/User.php
// Model mínimo para a tabela `users`.
// Regras: usa PDO (db()), prepared statements, password_hash()/password_verify().

declare(strict_types=1);

require_once __DIR__ . '/../core/db.php';

class User
{
    /**
     * Busca um usuário pelo e-mail. Retorna array associativo (inclui password_hash) ou null.
     * @param string $email
     * @return array|null
     */
    public static function findByEmail(string $email): ?array
    {
        $pdo = db();
        $sql = 'SELECT id, email, password_hash, name, role, created_at FROM users WHERE email = :email LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    /**
     * Busca um usuário por id.
     * @param int $id
     * @return array|null
     */
    public static function findById(int $id): ?array
    {
        $pdo = db();
        $sql = 'SELECT id, email, password_hash, name, role, created_at FROM users WHERE id = :id LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    /**
     * Cria um usuário. Espera array com keys: email, password, name(optional).
     * Retorna id (int) em caso de sucesso, ou false em caso de falha (ex: email duplicado).
     * @param array $data
     * @return int|false
     */
    public static function create(array $data)
    {
        // validação mínima
        $email = strtolower(trim($data['email'] ?? ''));
        $password = $data['password'] ?? '';
        $name = isset($data['name']) ? trim($data['name']) : null;

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        if (strlen($password) < 6) { // regra mínima
            return false;
        }

        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $pdo = db();

        try {
            // Try inserting including role column (newer schema)
            $sql = 'INSERT INTO users (email, password_hash, name, role) VALUES (:email, :password_hash, :name, :role)';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':email' => $email,
                ':password_hash' => $password_hash,
                ':name' => $name,
                ':role' => $data['role'] ?? 'user',
            ]);

            return (int)$pdo->lastInsertId();
        } catch (PDOException $e) {
            // If the error is caused by missing `role` column (older schema),
            // fall back to inserting without role. Otherwise, bubble up false.
            $msg = $e->getMessage();
            if (stripos($msg, 'unknown column') !== false || stripos($msg, "column 'role'") !== false) {
                try {
                    $sql2 = 'INSERT INTO users (email, password_hash, name) VALUES (:email, :password_hash, :name)';
                    $stmt2 = $pdo->prepare($sql2);
                    $stmt2->execute([
                        ':email' => $email,
                        ':password_hash' => $password_hash,
                        ':name' => $name,
                    ]);
                    return (int)$pdo->lastInsertId();
                } catch (PDOException $e2) {
                    return false;
                }
            }

            // Other errors (duplicate email, etc.) -> return false
            return false;
        }
    }

    /**
     * Verifica se a senha bate para o usuário com o e-mail informado.
     * Retorna true/false.
     * @param string $email
     * @param string $password
     * @return bool
     */
    public static function verifyPassword(string $email, string $password): bool
    {
        $user = self::findByEmail($email);
        if (!$user) return false;
        return password_verify($password, $user['password_hash']);
    }

    /**
     * Retorna todos os usuários (sem password_hash por segurança).
     * @return array
     */
    public static function all(): array
    {
        $pdo = db();
        $sql = 'SELECT id, email, name, role, created_at FROM users ORDER BY id DESC';
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Atualiza o role de um usuário.
     * @param int $id
     * @param string $role
     * @return bool
     */
    public static function setRole(int $id, string $role): bool
    {
        $allowed = ['user', 'admin'];
        if (!in_array($role, $allowed, true)) return false;
        $pdo = db();
        $sql = 'UPDATE users SET role = :role WHERE id = :id';
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':role' => $role, ':id' => $id]);
    }

    /**
     * Remove usuário do banco.
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        $pdo = db();
        $sql = 'DELETE FROM users WHERE id = :id';
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
}
