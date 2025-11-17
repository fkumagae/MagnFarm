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
        $sql = 'SELECT id, email, password_hash, name, created_at FROM users WHERE email = :email LIMIT 1';
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
        $sql = 'SELECT id, email, password_hash, name, created_at FROM users WHERE id = :id LIMIT 1';
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
            $sql = 'INSERT INTO users (email, password_hash, name) VALUES (:email, :password_hash, :name)';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':email' => $email,
                ':password_hash' => $password_hash,
                ':name' => $name,
            ]);

            return (int)$pdo->lastInsertId();
        } catch (PDOException $e) {
            // Se for duplicate entry (email), retorna false.
            // Em desenvolvimento podemos inspecionar $e->getMessage().
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
        $sql = 'SELECT id, email, name, created_at FROM users ORDER BY id DESC';
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
