<?php
require_once __DIR__ . '/../app/core/session.php';
require_once __DIR__ . '/../app/core/csrf.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';

$errors = [];
$success = null;

$res = AuthController::handleRegister();
if (is_array($res) && isset($res['errors'])) {
    $errors = $res['errors'];
}

// include layout partials
require_once __DIR__ . '/../app/views/partials/header.php';
?>

<main class="auth-page">
    <h1>Registrar</h1>

    <?php if (!empty($errors)): ?>
        <div class="errors" style="color:#b00">
            <ul>
                <?php foreach ($errors as $e): ?>
                    <li><?php echo htmlspecialchars($e, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <p style="color:green"><?php echo htmlspecialchars($success, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); ?></p>
    <?php endif; ?>

    <form class="auth-form" method="post" action="">
        <?php echo csrf_field(); ?>
        <div><label>Email:<br><input type="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); ?>"></label></div>
        <div><label>Nome:<br><input type="text" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); ?>"></label></div>
        <div><label>Senha:<br><input type="password" name="password" required></label></div>
        <div style="margin-top:10px"><button type="submit">Registrar</button></div>
    </form>

    <p><a href="login.php">Já tem conta? Entrar</a></p>
</main>

<?php
require_once __DIR__ . '/../app/views/partials/footer.php';
?>
