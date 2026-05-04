<?php
$override_landing_page=TRUE;
include "/Webpages/Header_Footer/scs_header.php";
include_once "classes/campaign.php";
include_once "classes/document.php";
include_once "classes/log.php";
$database->connect();
$report=array();
if (strlen($_GET['id']) > 2) {
	$report['campaign_id']=intval(substr($_GET['id'],1,strlen($_GET['id']) - 2));
    $database->campaign->read($report['campaign_id']);
    $url_encode=$database->campaign->url_encode();
    $database->document->meta->viewer_override=TRUE;
	$database->document->read($database->campaign->data->document_id);
}
if ((!$database->campaign->data->restricted) && ($database->document->results->error_code > 3)) $database->document->results->error_code=0;
switch (TRUE) {
  case (strlen($_GET['id']) < 3):
  case (!$database->campaign->meta->rows):
  case (!$database->document->meta->rows):
  case (!$database->campaign->data->active):
  case (($database->campaign->data->restricted) && ($database->document->results->error_code)):
  case ($_GET['id'] <> $url_encode):
  case (($database->campaign->data->from_date) && ($database->campaign->data->from_date > date("Y/m/d"))):
  case (($database->campaign->data->thru_date) && ($database->campaign->data->thru_date < date("Y/m/d"))):
	$menu->landing_page();
}
$database->log->create("Campaign",$report['campaign_id']);
if (($_GET['email']) && (!$_COOKIE['email'])) setcookie("email", $_GET['email'],(time() + (60 * 60 * 24 * 365)));
$menu->title=$database->campaign->data->description;
$menu->head();
$menu->document_viewer($database->campaign->data->document_id,-1,TRUE);
$database->disconnect();
$menu->copyright();
?>