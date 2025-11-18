<?php
// app/models/Comment.php
// Modelo simples para registrar comentários na tabela `comments`.

declare(strict_types=1);

require_once __DIR__ . '/../core/db.php';

class Comment
{
    /**
     * Cria um novo comentário.
     *
     * @param int         $userId           ID do usuário autor do comentário
     * @param int         $itemId           ID do item relacionado (por exemplo, leitura, projeto, etc.)
     * @param int|null    $parentId         ID de comentário pai (ou null para comentário raiz)
     * @param string      $content          Texto do comentário
     * @param string      $status           Status opcional (ex: 'pending', 'approved')
     * @param float|null  $sentimentScore   Score opcional de sentimento
     * @param string|null $moderationMeta   JSON ou texto com metadados de moderação
     *
     * @return bool
     */
    public static function create(
        int $userId,
        int $itemId,
        ?int $parentId,
        string $content,
        string $status = 'pending',
        ?float $sentimentScore = null,
        ?string $moderationMeta = null
    ): bool {
        if ($userId <= 0 || $itemId <= 0 || trim($content) === '') {
            return false;
        }

        $pdo = db();
        $sql = 'INSERT INTO comments (user_id, item_id, parent_id, content, status, sentiment_score, moderation_meta)
                VALUES (:user_id, :item_id, :parent_id, :content, :status, :sentiment_score, :moderation_meta)';

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':user_id' => $userId,
            ':item_id' => $itemId,
            ':parent_id' => $parentId,
            ':content' => $content,
            ':status' => $status,
            ':sentiment_score' => $sentimentScore,
            ':moderation_meta' => $moderationMeta,
        ]);
    }
}

