<?php
include_once "/Webpages/Header_Footer/scs_header.php";
include_once "classes/document.php";
$database->connect();
$database->document->read($_SESSION['document_viewer']);
unset($_SESSION['document_viewer']);
unset($_SESSION['document_viewer_override']);
if (($database->document->meta->rows) && ($database->document->data->mime == "text/html")) include_once($database->document->results->file);
$database->disconnect();
?>