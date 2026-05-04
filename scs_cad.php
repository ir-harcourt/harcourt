<?php
require_once "scs_header.php";
require_once "cad_toolbox._rev0.php";
$database->user->access("Executive");

$options=array();
$options['administrator']=$database->user->access("Super User",FALSE);
$options['update']=$database->user->access("Administrator",FALSE);
$ps_cad=new ps_cad_class($options);
?>