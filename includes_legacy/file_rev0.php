<?php
$code=trim($_GET['code']);
$type=trim($_GET['type']);
$width=intval($_GET['width']);
$height=intval($_GET['height']);
$debug=$_GET['debug'];
$filename="";
if ($debug) {
	print "<p>File manager debugger version 07/26/2013<br>\n";
    foreach ($_GET as $key => $value) {
    	print "<br>$key: $value";
    }
}
switch (TRUE) {
  case (isset($_GET['filename'])):
	$filename=$_GET['filename'];
    $source="filename";
	break;
  case (!$code):
    $source="No code";
	break;
  case (!isset($database->registry)):
  case (!$type):
	$filename=$code;
    $source="code";
	break;
  case (is_array($database->registry->files->{$type})): //static file name
	$filename=$database->registry->files->{$type}[$code];
    $source="registry: files: type: code (fixed name)";
    break;
  case (!$database->registry->files->{$type}):
	break;
  case (substr($database->registry->files->{$type},-1,1)=="/"):
    $filename=$database->registry->files->{$type} . $code;
    $source="registry: files: type: + code (variable name)";
    break;
  default:
	$filename=$database->registry->files->{$type} . "/" . $code;
    $source="registry: files: type: / code";
}
if ($debug) {
	print "<br>Filename: $filename";
	print "<br>Source: $source";
}
if (!file_exists($filename)) {
	if ($debug) print " <br>File does not exist";
    die;
}
$image=getimagesize($filename);
if ($image) {
    switch (TRUE) {
      case (!is_array($image)):
      case ((!$image[0]) || (!$image[1])):
		if ($debug) print "<br>File not an image";
    	die;
      case ($debug):
		foreach ($image as $key => $value) {
			print "<br>$key: $value";
        }
	}
	if ($width > $image[0]) $width=0;
	if ($height > $image[1]) $height=0;
	switch (TRUE) {
	  case (($width) && ($height)):
	    $display_width=$width;
	    $display_height=$height;
	    break;
	  case ($width):
	    $display_width=$width;
	    $display_height=round($image[1] * ($width / $image[0]));
	    break;
	  case ($height):
	    $display_height=$height;
	    $display_width=round($image[0] * ($height / $image[1]));
	    break;
	  default;
	    $display_width=$image[0];
	    $display_height=$image[1];
	    break;
	}
    if (!$display_width) $display_width=1;
    if (!$display_height) $display_height=1;
    if ($debug) {
		print "<br>Display width: " . $display_width;
		print "<br>Display height: " . $display_height;
		die;
    }
	header("Expires: 0");
	header("Content-type: " . $image['mime']);
	switch (TRUE) {
	  case (($display_width==$image[0]) && ($display_height==$image[1])):
	    readfile($filename);
	    break;
	  case ($image[2]==1):
	    $image_thumbnail=imagecreatetruecolor($display_width, $display_height);
	    $image_source=imagecreatefromgif($filename);
	    imagecopyresampled($image_thumbnail, $image_source, 0, 0, 0, 0, $display_width, $display_height, $image[0], $image[1]);
	    imagegif($image_thumbnail);
        imagedestroy($image_thumbnail);
	    break;
	  case ($image[2]==2):
	    $image_thumbnail=imagecreatetruecolor($display_width, $display_height);
	    $image_source=imagecreatefromjpeg($filename);
	    imagecopyresampled($image_thumbnail, $image_source, 0, 0, 0, 0, $display_width, $display_height, $image[0], $image[1]);
	    imagejpeg($image_thumbnail);
        imagedestroy($image_thumbnail);
	    break;
	  case ($image[2]==3):
	    $image_thumbnail=imagecreatetruecolor($display_width, $display_height);
		imagealphablending($image_thumbnail, FALSE);
		imagesavealpha($image_thumbnail,true);
		imagecolorallocatealpha($image_thumbnail,255,255,255,127);
	    $image_source=imagecreatefrompng($filename);
		imagecopyresampled($image_thumbnail, $image_source, 0, 0, 0, 0, $display_width, $display_height, $image[0], $image[1]);
	    imagepng($image_thumbnail);
        imagedestroy($image_thumbnail);
	    break;
	}
  } else {
	if ($_GET['mime']) {
		$mime=$_GET['mime'];
	  } else {
		$mime=scs_mime($filename);
	}
    if ($_GET['contentname']) {
    	$contentname=$_GET['contentname'];
	  } else {
		$contentname=$filename;
	}
    switch (TRUE) {
	  case ($mime != 'text/html'):
      case ($_GET['upload']):
	    header("Content-type: " . $mime);
	    header("Content-Disposition: attachment; filename=" . $contentname);
	    header("Pragma: no-cache");
	    header("Expires: 0");
	    readfile($filename);
        break;
	  default:
	    header("Content-type: " . $mime);
	    header("Pragma: no-cache");
	    header("Expires: 0");
	    readfile($filename);
	}
}
function scs_mime($filename) {
    $mime_types = array(
         'txt' => 'text/plain',
        'htm' => 'text/html',
        'html' => 'text/html',
        'php' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'xml' => 'application/xml',
        'swf' => 'application/x-shockwave-flash',
        'flv' => 'video/x-flv',

        // images
        'png' => 'image/png',
        'jpe' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'ico' => 'image/vnd.microsoft.icon',
        'tiff' => 'image/tiff',
        'tif' => 'image/tiff',
        'svg' => 'image/svg+xml',
        'svgz' => 'image/svg+xml',

        // archives
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        'exe' => 'application/x-msdownload',
        'msi' => 'application/x-msdownload',
        'cab' => 'application/vnd.ms-cab-compressed',

        // audio/video
        'mp3' => 'audio/mpeg',
        'qt' => 'video/quicktime',
        'mov' => 'video/quicktime',

        // adobe
        'pdf' => 'application/pdf',
        'psd' => 'image/vnd.adobe.photoshop',
        'ai' => 'application/postscript',
        'eps' => 'application/postscript',
        'ps' => 'application/postscript',

        // ms office
        'doc' => 'application/msword',
        'rtf' => 'application/rtf',
        'xls' => 'application/vnd.ms-excel',
        'ppt' => 'application/vnd.ms-powerpoint',
        'docx' => 'application/msword',
        'xlsx' => 'application/vnd.ms-excel',
        'pptx' => 'application/vnd.ms-powerpoint',


        // open office
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        );
	$suffix=strtolower(array_pop(explode('.',$filename)));
	switch (TRUE) {
      case (function_exists('finfo_open')):
	    $finfo = finfo_open(FILEINFO_MIME);
	    $mime = finfo_file($finfo, $filename);
	    finfo_close($finfo);
		break;
      case (function_exists('mime_content_type')):
		$mime=mime_content_type($filename);
        break;
      case (array_key_exists($suffix,$mime_types)):
		$mime=$mime_types[$suffix];
		break;
	}
	if (!$mime) $mime="application/octet-stream";
    return $mime;
}
?>