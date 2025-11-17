<?php
require_once __DIR__ . '/../app/core/session.php';
require_once __DIR__ . '/../app/core/csrf.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';

$errors = [];
$res = AuthController::handleLogin();
if (is_array($res) && isset($res['errors'])) {
    $errors = $res['errors'];
}

// include layout partials
require_once __DIR__ . '/../app/views/partials/header.php';
?>

<main class="auth-page">
    <h1>Entrar</h1>

    <?php if (!empty($errors)): ?>
        <div class="errors" style="color:#b00">
            <ul>
                <?php foreach ($errors as $e): ?>
                    <li><?php echo htmlspecialchars($e, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form class="auth-form" method="post" action="">
        <?php echo csrf_field(); ?>
        <div><label>Email:<br><input type="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); ?>"></label></div>
        <div><label>Senha:<br><input type="password" name="password" required></label></div>
        <div><label><input type="checkbox" name="remember" value="1"> Lembrar-me neste dispositivo</label></div>
        <div style="margin-top:10px"><button type="submit">Entrar</button></div>
    </form>

    <p><a href="register.php">Criar uma conta</a></p>
</main>

<?php
require_once __DIR__ . '/../app/views/partials/footer.php';
?>
