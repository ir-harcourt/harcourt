<?php
require_once "scs_header.php";
require_once "classes/inventory.php";
$options=array();
list($options['user_id'],$options['name'])=preg_split("/\|/",$_REQUEST['name'],2);
$email=new email_class($_REQUEST['email'],TRUE);
$options['email']=$email->address;
$options['sku']=trim($_REQUEST['sku']);
$options['cad_code']=trim($_REQUEST['cad']);
$database->user->read($options['user_id']);
$database->inventory->read($options['sku']);
switch (TRUE) {
  case (!strlen($options['name'])):
  case ($email->error):
  case (!strlen($options['cad_code'])):
  case (!$database->user->meta->rows):
  case (!$database->inventory->meta->rows):
	die("Missing parameters");
}
$options['subcategory_id']=$database->inventory->data->subcategory_id;
$database->log->update("partsolutions:cad",$options);
/*
http://www.harcourt.co/stock.php?name=307+%7C+Thomas+Hayes&sku=29001&cad=CATV4IUA3D&email=tom%40suburbancomputer.com
http://localhost:8085/stock.php?name=307+%7C+Thomas+Hayes&sku=29001&cad=CATV4IUA3D&email=tom%40suburbancomputer.com
*/
?>