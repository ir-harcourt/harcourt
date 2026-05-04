<?php
include_once "scs_header.php";
$file=$_SESSION[$_GET['action']];
if (!$file) die;
$image_array=getimagesize($file);
if ((!$image_array[0]) || (!$image_array[1])) return;
header("Expires: 0");
header("Content-type: " . $image_array['mime']);
readfile($file);
unset ($_SESSION[$_GET['action']]);
?>