<?php
chdir($_SERVER['DOCUMENT_ROOT']);
$_scs_https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 80) == 443;
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_secure', $_scs_https ? '1' : '0');
ini_set('session.cookie_httponly', 'true');
session_start();
require_once 'oauth/MicrosoftAuth.php';
$auth = new MicrosoftAuth();
$auth->init();
?>
