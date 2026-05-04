<?php
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_secure', 'true');
ini_set('session.cookie_httponly', 'true');
session_start();
print "<p>Harcourt.co Cookies</p>";
print "<p>Session ID: " . session_id() . "</p>";
print "<pre>Cookies:";
print_r($_COOKIE);
print "</pre>";
print "<pre>Server:";
print_r($_SERVER);
print "</pre>";
?>