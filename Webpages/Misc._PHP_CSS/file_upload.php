<?php
include_once "/Webpages/Header_Footer/scs_header.php";
$menu->access(0);
include_once "classes/document.php";
$database->connect();
$database->document->read(intval($_GET['page']));
switch (TRUE) {
  case (!$database->document->meta->rows):
  	print "Document not found for page " . $_GET['page'];
    break;
  case (!file_exists($database->document->results->file)):
  	print "CMS File not found for page " . $_GET['page'];
	break;
  default:
	header("Content-type: " . $database->document->data->mime);
	header("Content-Disposition: attachment; filename=\"" . $database->document->data->file ."\"");
	readfile($database->document->results->file);
}
$database->disconnect();
?>