<?php
// admin view: expects $users (array) and csrf helpers available
?>
<main class="admin-page" role="main" style="max-width:980px;margin:1rem auto;padding:1rem;">
  <h1>Painel de Administração</h1>
  <?php if ($m = flash('success')): ?><div class="flash flash-success"><?php echo htmlspecialchars($m); ?></div><?php endif; ?>
  <?php if ($m = flash('error')): ?><div class="flash flash-error"><?php echo htmlspecialchars($m); ?></div><?php endif; ?>

  <section style="margin-top:1rem;background:#fff;padding:1rem;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.05);">
    <h2>Usuários</h2>
    <table style="width:100%;border-collapse:collapse;margin-top:0.5rem;">
      <thead>
        <tr style="text-align:left;border-bottom:1px solid #eee;">
          <th>ID</th>
          <th>Email</th>
          <th>Nome</th>
          <th>Role</th>
          <th>Criado em</th>
          <th>Ações</th>
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

              <form method="post" action="admin.php" style="display:inline;margin-right:.25rem;" onsubmit="return confirm('Confirma remoção deste usuário?');">
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
