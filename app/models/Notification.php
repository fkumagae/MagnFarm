<?php
// app/models/Notification.php
// Modelo simples para registrar notificações na tabela `notifications`.

declare(strict_types=1);

require_once __DIR__ . '/../core/db.php';

class Notification
{
    /**
     * Cria uma nova notificação.
     *
     * @param int    $userId   ID do usuário que receberá a notificação
     * @param string $type     Tipo da notificação (ex: 'info', 'alert')
     * @param string $data     Mensagem ou payload da notificação
     * @param string $priority Prioridade (ex: 'normal', 'high')
     *
     * @return bool
     */
    public static function create(
        int $userId,
        string $type,
        string $data,
        string $priority = 'normal'
    ): bool {
        if ($userId <= 0 || trim($data) === '') {
            return false;
        }

        $pdo = db();
        $sql = 'INSERT INTO notifications (user_id, type, data, is_read, priority)
                VALUES (:user_id, :type, :data, :is_read, :priority)';

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':user_id' => $userId,
            ':type' => $type !== '' ? $type : 'info',
            ':data' => $data,
            ':is_read' => 0,
            ':priority' => $priority !== '' ? $priority : 'normal',
        ]);
    }
}

