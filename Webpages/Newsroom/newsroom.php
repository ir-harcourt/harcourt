<?php
include "scs_header.php";
switch (TRUE) {
  case (!$_SESSION['user']->id):
  case ($_SESSION['user']->status == "Pending"):
  case ($_SESSION['user']->status == "Denied"):
	fn_url_redirect($menu->page['home']);
	exit;
}
$menu->title="Harcourt News Room";
$menu->head();
$menu->document_viewer(154);
$menu->copyright();
?>