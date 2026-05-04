<?php
switch (TRUE) {
  case (isset($_GET['content_id'])):
  case (isset($_GET['watermark'])):
	require_once "scs_header.php";
}
require_once "file_rev1.php";
$scs_file=new scs_file_class();
$options=$_GET;
switch (TRUE) {
  case (isset($_GET['content_id'])):
	if (!$database->content->decode($_GET['content_id'],TRUE)) die("Content missing: {$_GET['content_id']}");
	if (isset($_GET['local_log'])) {
		$options=array();
		list($subtype,$options['comment'],$options['sku'],$options['subcategory_id'])=explode("\t",base64_decode($_GET['local_log'])) + array(NULL,NULL,NULL,NULL);
		$database->log->update("user:{$subtype}",$options);
    }
	$scs_file->output($options,$database->content->data->content,$database->content->data->meta);
	break;
  default:
	$scs_file->output($options);
}
?>