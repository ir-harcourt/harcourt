<?php
include "/Webpages/Header_Footer/scs_header.php";
$image=$_GET['image'];
$width=$_GET['width'];
$height=$_GET['height'];
if (!file_exists($image)) return;
$image_array=getimagesize($image);
if ((!$image_array[0]) || (!$image_array[1])) return;
if ($width > $image_array[0]) $width=0;
if ($height >  $image_array[1]) $height=0;
switch (TRUE) {
  case (($width) && ($height)):
	$display_width=$width;
    $display_height=$height;
	break;
  case ($width):
	$display_width=$width;
    $display_height=round($image_array[1] * ($width / $image_array[0]));
    break;
  case ($height):
	$display_height=$height;
    $display_width=round($image_array[0] * ($height / $image_array[1]));
    break;
  default;
	$display_width=$image_array[0];
	$display_height=$image_array[1];
    break;
}
header("Expires: 0");
header("Content-type: " . $image_array['mime']);
switch (TRUE) {
  case (($display_width==$image_array[0]) && ($display_height==$image_array[1])):
	readfile($image);
	break;
  case ($image_array[2]==1):
	$thumb = imagecreatetruecolor($display_width, $display_height);
	$source = imagecreatefromgif($image);
	imagecopyresized($thumb, $source, 0, 0, 0, 0, $display_width, $display_height, $image_array[0], $image_array[1]);
	imagegif($thumb);
	break;
  case ($image_array[2]==2):
	$thumb = imagecreatetruecolor($display_width, $display_height);
	$source = imagecreatefromjpeg($image);
	imagecopyresized($thumb, $source, 0, 0, 0, 0, $display_width, $display_height, $image_array[0], $image_array[1]);
	imagejpeg($thumb);
	break;
  case ($image_array[2]==3):
	$thumb = imagecreatetruecolor($display_width, $display_height);
	$source = imagecreatefrompng($image);
	imagecopyresized($thumb, $source, 0, 0, 0, 0, $display_width, $display_height, $image_array[0], $image_array[1]);
	imagepng($thumb);
	break;
}
?>