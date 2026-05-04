<?php
require_once "scs_header.php";
switch (TRUE) {
  case (!isset($_SESSION['user'])):
  case (!array_key_exists($_REQUEST['language_code'],$menu->language)):
  case (!$_SERVER['HTTP_REFERER']):
	fn_url_redirect($_SERVER['HTTP_REFERER']);
}
if ($_SESSION['user']->id) {
	$database->user->read($_SESSION['user']->id);
	$database->user->data->language_code=$_REQUEST['language_code'];
    if ($database->user->meta->rows) $database->user->update(TRUE);
}
$_SESSION['user']->language_code=$_REQUEST['language_code'];
fn_url_redirect($_SERVER['HTTP_REFERER']);
?>