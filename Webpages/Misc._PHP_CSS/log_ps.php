<?php
include_once "/Webpages/Header_Footer/scs_header.php";
include_once "classes/log.php";
$database->connect();
$option_action=array("cad"=>"CAD","3d"=>"3D");
$report['action']=trim(strtolower($_REQUEST['action']));
$report['ip']=trim($_REQUEST['ip']);
$report['email']=trim(strtolower($_REQUEST['email']));
$report['sku']=trim(strtoupper($_REQUEST['sku']));
$report['cad_code']=trim($_REQUEST['cad_code']);

if (!$report['ip']) $report['ip']=$_SERVER['REMOTE_ADDR'];
if (!$report['email']) $report['email']=$_SESSION['uemail'];

switch (TRUE) {
  case (!array_key_exists($report['action'],$option_action)):
  case ($report['ip'] != (long2ip(ip2long($report['ip'])))):
	break;
  default:
  	$database->log->data=new log_data_class();
  	$database->log->data->ip=$report['ip'];
  	$database->log->data->record_type=$option_action[$report['action']];
  	$database->log->data->sku=$report['sku'];
  	$database->log->data->cad_code=$report['cad_code'];
  	$database->log->data->email=$report['email'];
  	$database->log->update(FALSE);
}
$database->disconnect();
?>