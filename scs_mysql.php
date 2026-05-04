<?php
require_once "scs_header.php";
require_once "mysql_rev1.php";
$database->content->meta->error_abort=FALSE;
$database->language->meta->error_abort=FALSE;
$options=array();
$options['timeout']=75;
$options['multiple']=0;
$obj=new mysql_toolbox_class("Administrator", $options);
?>