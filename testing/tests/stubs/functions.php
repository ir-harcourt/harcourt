<?php
// Stub — replaces includes/functions.php for MigrateTest. Only defines the two
// helpers migrate.php actually calls, so the real (heavy) functions.php never loads.

if (!function_exists('fn_development_server')) {
    function fn_development_server()
    {
        return false;
    }
}

if (!function_exists('fn_escape')) {
    function fn_escape($text, $type = "")
    {
        if ($type === false) {
            return (string) $text;
        }
        return "'" . addslashes((string) $text) . "'";
    }
}
