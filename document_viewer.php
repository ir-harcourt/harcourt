<?php
require_once "scs_header.php";
if ($_REQUEST['document']) {
	$data=fn_base64($_REQUEST['document'],FALSE);
    $record_id=intval($data[0]);
    $timestamp=intval($data[1]);
  } else {
	$record_id=$_SESSION['document_viewer'];
    $timestamp=strtotime("now");
}
$query_where=array();
$query_where[]=new query_where("record_type","=","document");
$query_where[]=new query_where("record_id","=",$record_id);
$query_where[]=new query_where("language_code","in",array("EN",$_SESSION['user']->language_code));
$query=array("select");
$query[]="if (language_code='EN',2,1) as sort_language,";
$query[]="content.* from content";
$query[]=$database->where($query_where);
$query[]="order by sort_language";
$database->content->query($query);
$database->content->fetch(TRUE);
$database->content->free_result();
unset($_SESSION['document_viewer']);
unset($_SESSION['document_viewer_override']);
switch (TRUE) {
  case (($_REQUEST['login_url']) && (!isset($_SESSION['login_url']))):
	$forms->html_open();
	print "<p class='center large'><a href='" . $_REQUEST['login_url'] . "' target='_top'>Click here to continue</a></p>\n";
    $forms->html_close();
    break;
  case ( ($database->content->meta->rows) && ($database->content->data->meta->mime == "text/html") ):
	echo $database->content->data->content;
	break;
}
?>