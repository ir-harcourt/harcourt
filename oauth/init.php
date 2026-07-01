<?php
chdir($_SERVER['DOCUMENT_ROOT']);
require_once 'oauth/session.php';
require_once 'oauth/MicrosoftAuth.php';
$auth = new MicrosoftAuth();
$auth->init();
