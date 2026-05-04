<?php
include_once "/Webpages/Header_Footer/scs_header.php";
include_once "classes/document.php";
$menu->access();
$database->connect();

$report['action']=$_POST['action'];
$report['document_id']=$_POST['document_id'];
ob_start();
switch (TRUE) {
  case (!$report['action']):
	break;
  case ($report['document_id']):
	$menu->document_viewer($report['document_id'],-1,TRUE);
    break;
  default:
	$menu->errors['document_id']=$menu->background['red'];
}
$contents=ob_get_contents();
ob_clean();
$menu->title="Document viewer utility";
$menu->messages[]=$menu->title;
$menu->head();
$menu->message();
print "<form name=scs_form method=post>\n";
print "<input type=hidden name=action value='list'>\n";
print "<p class=center>Select document ";
$database->document->select($report['document_id']);
print " <input type=submit value='Go!'>\n";
print "</p>\n";
print "</form>\n";
if ($contents) {
	print "<hr>\n";
	print $contents;
	print "<hr>\n";
}
$database->disconnect();
$menu->copyright();
?>