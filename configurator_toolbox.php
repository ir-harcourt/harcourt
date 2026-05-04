<?php
require_once "scs_header.php";
require_once "scsps_toolbox.php";
$database->profile->load();
$options=array();
$options['nolog']=TRUE;
$options['fixed']=TRUE;
$options['user']=$database->profile->data;
$obj=new scsps_toolbox_class("lookup","Internal",$options);
?>