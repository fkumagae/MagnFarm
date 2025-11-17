<?php
// public/test-csrf.php - simple page to test CSRF helpers
require_once __DIR__ . '/../app/core/csrf.php';
require_once __DIR__ . '/../app/core/session.php';

$result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (csrf_check_request()) {
        $result = ['ok' => true, 'message' => 'CSRF válido — requisição aceita.'];
    } else {
        $result = ['ok' => false, 'message' => 'CSRF inválido ou ausente — requisição rejeitada.'];
    }
}

?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Test CSRF</title>
    <style>
        body{font-family:system-ui,-apple-system,Segoe UI,Roboto,'Helvetica Neue',Arial;line-height:1.4;padding:20px}
        .ok{color:green}
        .err{color:red}
        pre{white-space:break-spaces;background:#f6f6f6;padding:10px;border-radius:6px}
    </style>
</head>
<body>
    <h1>Teste CSRF</h1>

    <?php if ($result !== null): ?>
        <p class="<?php echo $result['ok'] ? 'ok' : 'err'; ?>"><?php echo htmlspecialchars($result['message'], ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); ?></p>
    <?php endif; ?>

    <h2>Token atual (session)</h2>
    <pre><?php echo htmlspecialchars(csrf_token(), ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); ?></pre>

    <h2>Formulário de teste (envia token)</h2>
    <form method="post" action="">
        <?php echo csrf_field(); ?>
        <div>
            <label>Teste<input type="text" name="dummy" value="teste"></label>
        </div>
        <div style="margin-top:10px"><button type="submit">Enviar (POST)</button></div>
    </form>

    <h2>Testes manuais</h2>
    <ul>
        <li>Envie o formulário normalmente — deve mostrar CSRF válido.</li>
        <li>Abra o DevTools, remova o campo <code>csrf_token</code> do formulário e envie — deve falhar.</li>
        <li>Altere o valor do token e envie — deve falhar.</li>
    </ul>

</body>
</html>
