<?php
$SCS_API=TRUE;
require_once "scs_header.php";
session_start();
$error=0;

switch ($_REQUEST['action']) {
  case "create":
	$code=array();
	$code[]=$_REQUEST['id'];
	$code[]=$_REQUEST['ip'];
	$code[]=harcourt_remote_addr();
	$code[]=strtotime("now");
	die(  http_build_query(array("action"=>"access","code"=>fn_base64($code))) );
  case "access":
	$code=fn_base64($_REQUEST['code'],FALSE);
	$database->user->read($code[0]);
	switch (TRUE) {
      case (sizeof($code) != 4):
      	$error=104;
        break;
      case (!$database->user->meta->rows):
      	$error=100;
        break;
      case ($database->user->data->ip != $code[1]):
      	$error=101;
        break;
      case (harcourt_remote_addr() != $code[2]):
      	$error=102;
        break;
      case (strtotime("-4 hours") > $code[3]):
      	$error=103;
        break;
	}
    $_SESSION['user']=$database->user->data;
  	break;
  default:
	$error=0;
}

die("Success #{$error}");
?>