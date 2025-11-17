<?php
require_once __DIR__ . '/../app/core/session.php';

// set a test flash and redirect to front-controller
flash('success', 'Mensagem de teste: flash funcionando!');
header('Location: index.php');
exit;
