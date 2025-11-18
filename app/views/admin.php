<?php
// admin view: expects $users (array) and csrf helpers available
?>
<main class="admin-page" role="main" style="max-width:980px;margin:1rem auto;padding:1rem;">
  <h1>Painel de Administracao</h1>
  <?php if ($m = flash('success')): ?><div class="flash flash-success"><?php echo htmlspecialchars($m); ?></div><?php endif; ?>
  <?php if ($m = flash('error')): ?><div class="flash flash-error"><?php echo htmlspecialchars($m); ?></div><?php endif; ?>

  <section style="margin-top:1rem;background:#fff;padding:1rem;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.05);">
    <h2>Modelo de IA (Autoencoder)</h2>
    <p style="margin-bottom:0.75rem;">
      Usa as leituras hidroponicas recentes (pH, temperaturas, umidade, luz, EC) para treinar um
      autoencoder self-supervised. O modelo pode ser usado depois para detectar anomalias.
    </p>
    <div style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-top:0.5rem;">
      <form method="post" action="admin.php">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="train_model">
        <button type="submit" class="btn-formulario">Treinar modelo com leituras recentes</button>
      </form>
      <form method="post" action="admin.php">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="online_train_model">
        <button type="submit" class="btn-formulario">Retreinar modelo (online) com leituras recentes</button>
      </form>
      <form method="post" action="admin.php">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="test_model">
        <button type="submit" class="btn-formulario">Testar modelo com leituras recentes</button>
      </form>
      <form method="post" action="admin.php">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="generate_readings">
        <button type="submit" class="btn-formulario">Gerar mais leituras simuladas</button>
      </form>
    </div>
  </section>

  <section style="margin-top:1rem;background:#fff;padding:1rem;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.05);">
    <h2>Comentários e notificações</h2>
    <div style="display:flex;flex-wrap:wrap;gap:1.5rem;">
      <div style="flex:1 1 260px;min-width:260px;">
        <h3>Adicionar comentário</h3>
        <form method="post" action="admin.php">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="action" value="add_comment">
          <label for="item_id" style="display:block;margin-top:0.5rem;">ID do item relacionado</label>
          <input type="number" name="item_id" id="item_id" min="1" style="width:100%;padding:0.35rem;">

          <label for="comment_content" style="display:block;margin-top:0.5rem;">Comentário</label>
          <textarea name="content" id="comment_content" rows="3" style="width:100%;padding:0.35rem;"></textarea>

          <button type="submit" class="btn-formulario" style="margin-top:0.5rem;">Salvar comentário</button>
        </form>
      </div>

      <div style="flex:1 1 260px;min-width:260px;">
        <h3>Enviar notificação</h3>
        <form method="post" action="admin.php">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="action" value="send_notification">

          <label for="notif_user_id" style="display:block;margin-top:0.5rem;">Usuário destino</label>
          <select name="notif_user_id" id="notif_user_id" style="width:100%;padding:0.35rem;">
            <option value="">Selecione...</option>
            <?php foreach ($users as $u): ?>
              <option value="<?php echo (int)$u['id']; ?>">
                <?php echo htmlspecialchars(($u['email'] ?? '') . ' (ID ' . (int)$u['id'] . ')', ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); ?>
              </option>
            <?php endforeach; ?>
          </select>

          <label for="notif_type" style="display:block;margin-top:0.5rem;">Tipo</label>
          <input type="text" name="notif_type" id="notif_type" value="info" style="width:100%;padding:0.35rem;">

          <label for="notif_priority" style="display:block;margin-top:0.5rem;">Prioridade</label>
          <select name="notif_priority" id="notif_priority" style="width:100%;padding:0.35rem;">
            <option value="normal">Normal</option>
            <option value="high">Alta</option>
          </select>

          <label for="notif_message" style="display:block;margin-top:0.5rem;">Mensagem</label>
          <textarea name="notif_message" id="notif_message" rows="3" style="width:100%;padding:0.35rem;"></textarea>

          <button type="submit" class="btn-formulario" style="margin-top:0.5rem;">Registrar notificação</button>
        </form>
      </div>
    </div>
  </section>

  <section style="margin-top:1rem;background:#fff;padding:1rem;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.05);">
    <h2>Usuarios</h2>
    <table style="width:100%;border-collapse:collapse;margin-top:0.5rem;">
      <thead>
        <tr style="text-align:left;border-bottom:1px solid #eee;">
          <th>ID</th>
          <th>Email</th>
          <th>Nome</th>
          <th>Role</th>
          <th>Criado em</th>
          <th>Acoes</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
          <tr style="border-bottom:1px solid #f3f3f3;">
            <td><?php echo (int)$u['id']; ?></td>
            <td><?php echo htmlspecialchars($u['email'], ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($u['name'] ?? '', ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($u['role'] ?? 'user', ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($u['created_at'] ?? '', ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); ?></td>
            <td>
              <?php if (($u['role'] ?? 'user') !== 'admin'): ?>
                <form method="post" action="admin.php" style="display:inline;margin-right:.25rem;">
                  <?php echo csrf_field(); ?>
                  <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
                  <input type="hidden" name="action" value="promote">
                  <button type="submit" class="nav-like">Promover a admin</button>
                </form>
              <?php else: ?>
                <form method="post" action="admin.php" style="display:inline;margin-right:.25rem;">
                  <?php echo csrf_field(); ?>
                  <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
                  <input type="hidden" name="action" value="demote">
                  <button type="submit" class="nav-like">Remover admin</button>
                </form>
              <?php endif; ?>

              <form method="post" action="admin.php" style="display:inline;margin-right:.25rem;" onsubmit="return confirm('Confirma remocao deste usuario?');">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
                <input type="hidden" name="action" value="delete">
                <button type="submit" class="nav-like">Excluir</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </section>
</main>
