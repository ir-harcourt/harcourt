<?php
chdir($_SERVER['DOCUMENT_ROOT']);
require_once 'oauth/session.php';
require_once 'oauth/GoogleAuth.php';
$auth = new GoogleAuth();
$auth->callback();
