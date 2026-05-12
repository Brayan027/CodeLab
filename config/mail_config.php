<?php
// Configuración de correo
define('MAIL_HOST', getenv('MAIL_HOST') ?: 'smtp.gmail.com');
define('MAIL_USER', getenv('MAIL_USER') ?: '');
define('MAIL_PASS', getenv('MAIL_PASS') ?: '');
define('MAIL_FROM', getenv('MAIL_FROM') ?: '');
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: 'Codelab');
define('MAIL_PORT', getenv('MAIL_PORT') ?: 587);

// Configuración de roles
define('SECRET_CODE_DOCENTE', getenv('SECRET_CODE_DOCENTE') ?: '12345');
?>
