<?php
/*
http://localhost:8085/scs_api.php?email=test@suburbancomputer.com&campaign=remote
https://www.harcourt.co/scs_api.php?email=test@suburbancomputer.com&campaign=remote
*/
$GLOBALS['SCS_API']=TRUE;
print header("Access-Control-Allow-Origin: *");
require_once "scs_header.php";
require_once "classes/campaign.php";
$email=new email_class($_REQUEST['email'],TRUE);
$forms->constant->email=( (!$email->error) ? $email->address : "");
$database->campaign->read($_REQUEST['campaign'],"code");
if ( ($database->campaign->meta->rows) && ($database->campaign->data->type == "Remote") && ($database->campaign->active()) ) {
	$forms->constant->campaign=$database->campaign->data->id;
  } else {
	$forms->constant->campaign=0;
}
$database->user->read(harcourt_remote_addr());
$results=array();
switch (TRUE) {
  case (!$database->user->meta->rows):
  case ($database->user->data->status != "Active"):
  case (!$forms->constant->campaign):
	$results['accesslevel']="public";
    break;
  case (!strlen($forms->constant->email)):
	$results['accesslevel']="semiprivate";
    break;
  default:
	$results['accesslevel']="private";
}
$results['company']=$database->user->data->company_name;
if ($forms->constant->campaign) {
	$query_where=array();
    $query_where[]=new query_where("ip","=",harcourt_remote_addr());
    $query_where[]=new query_where("type","=","initial");
    $query_where[]=new query_where("subtype","=","campaign");
    $query_where[]=new query_where("date_format(from_unixtime(date_time),'%Y/%m/%d')","=",date("Y/m/d"));
    $query_where[]=new query_where("email","=",$forms->constant->email);
	$query=array("select * from log");
    $query[]=$database->where($query_where);
    $database->temp->query($query);
	if (!$database->temp->meta->rows) $database->log->update("initial:campaign",array("date_time"=>strtotime("now"),"user_id"=>$database->user->data->id,"comment"=>$database->campaign->data->code,"email"=>$forms->constant->email));
}
die(json_encode($results));
?>