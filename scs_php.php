<?php
require_once "scs_header.php";

$database->user->read(307);
$database->user->data->domain="suburbancomputer.com";
$database->user->update(TRUE);
fn_pre($database->user->data,1);

print "<pre>";
print_r($_SESSION);
print "</pre>";
?>
