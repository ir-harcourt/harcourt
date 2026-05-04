<?php
$forms->report['REDIRECT_STATUS']=intval($_SERVER['REDIRECT_STATUS']);
$forms->report['HTTP_REFERER']=$_SERVER['HTTP_REFERER'];
$forms->report['REDIRECT_URL']=$_SERVER['REDIRECT_URL'];
if (method_exists($database->log,"ipc")) {
	$comment=array();
    $comment[]="Requested page: " . $_SERVER['REDIRECT_URL'];
    if ($forms->report['HTTP_REFERER']) $comment[]="Referring page: " . $_SERVER['HTTP_REFERER'];
    $database->log->ipc("HTTP Error:" . $forms->report['REDIRECT_STATUS'],$comment);
}
$results=array();
switch (TRUE) {
  case (!$forms->report['REDIRECT_STATUS']): // direct request to error program
	die();
  case (!$forms->report['HTTP_REFERER']): // direct request to missing page
	$results[]="The page you are looking for does not exist: " . $forms->report['REDIRECT_URL'];
    break;
  default:
	$results[]="The link on our page <b>" . $forms->report['HTTP_REFERER'] . "</b> no longer exists. The system administrator has been informed";
}
$forms->title("Internal Error Detected");
$menu->head();
$forms->message();
print "<p>" . implode("<br>\n",$results) . "</p>\n";
$menu->copyright();
?>