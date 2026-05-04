<?php
include_once "/Webpages/Header_Footer/scs_header.php";
$menu->access(13);
$menu->head(TRUE);
$_SESSION['document_viewer']=46;
include_once "document_viewer.php";
$menu->copyright();
?>