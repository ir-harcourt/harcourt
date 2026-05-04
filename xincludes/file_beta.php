<?php
//ndhomes/scs_file.php
$scs_filemanager=new scs_filemanager();
class scs_filemanager {
	var $code;
    var $type;
    var $width;
    var $height;
    var $filename;
    var $image;
    var $image_source;
    var $wm;
    var $wm_x=0;
    var $wm_y=0;
    var $error;
    var $debug=FALSE;

    var $mime_types = array(
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
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet');
    function scs_version() {
    	$results=array();
        $results['09/15/2013']="Add watermark";
        $results['07/26/2013']="Add debug";
        $results['10/09/2012']="Initial release";
        return $results;
    }
    function __construct() {
	    $this->code=trim($_GET['code']);
	    $this->type=trim($_GET['type']);
	    $this->width=intval($_GET['width']);
	    $this->height=intval($_GET['height']);
	    $this->wm=$_GET['wm'];
	    $this->debug=$_GET['debug'];
        $this->error=$this->load();
        switch (TRUE) {
		  case ($this->error):
          	break;
          case (!$this->image_info):
          	$this->error=$this->file();
            break;
          default:
          	$this->error=$this->image();
		}
		if ($this->debug) $this->debug();
    }
    function load() {
    global $database;
	    switch (TRUE) {
	      case (isset($_GET['filename'])):
	        $this->filename=$_GET['filename'];
	        $this->image_source="filename";
	        break;
	      case (!$this->code):
	        return "No code";
	      case (!isset($database->registry)):
	      case (!$this->type):
	        $this->filename=$this->code;
	        $this->image_source="code";
	        break;
	      case (is_array($database->registry->files->{$this->type})): //static file name
	        $this->filename=$database->registry->files->{$this->type}[$this->code];
	        $this->image_source="registry: files: type: code (fixed name)";
	        break;
	      case (!$database->registry->files->{$this->type}):
	        break;
	      case (substr($database->registry->files->{$this->type},-1,1)=="/"):
	        $this->filename=$database->registry->files->{$this->type} . $this->code;
	        $this->image_source="registry: files: type: + code (variable name)";
	        break;
	      default:
	        $this->filename=$database->registry->files->{$this->type} . "/" . $this->code;
	        $this->image_source="registry: files: type: / code";
	    }
        switch (TRUE) {
          case (!$this->filename):
          	return "Empty file name";;
          case (!file_exists($this->filename)):
	        return "File does not exist";
	    }
		$this->image_info=getimagesize($this->filename);
	    if ($this->image_info) {
	        switch (TRUE) {
	          case (!is_array($this->image_info)):
	          case ((!$this->image_info[0]) || (!$this->image_info[1])):
				return "File not an image";
			}
		}
    }
    function file() {
	    if ($_GET['mime']) {
	        $mime=$_GET['mime'];
	      } else {
	        $this->mime();
	    }
	    if ($_GET['contentname']) {
	        $contentname=$_GET['contentname'];
	      } else {
	        $contentname=$this->filename;
	    }
	    switch (TRUE) {
	      case ($mime != 'text/html'):
	      case ($_GET['upload']):
	        header("Content-type: " . $mime);
	        header("Content-Disposition: attachment; filename=" . $contentname);
	        header("Pragma: no-cache");
	        header("Expires: 0");
	        readfile($this->filename);
	        break;
	      default:
	        header("Content-type: " . $mime);
	        header("Pragma: no-cache");
	        header("Expires: 0");
	        readfile($this->filename);
	    }
    }
    function mime() {
	    $suffix=strtolower(array_pop(explode('.',$this->filename)));
	    switch (TRUE) {
	      case (function_exists('finfo_open')):
	        $finfo = finfo_open(FILEINFO_MIME);
	        $this->mime=finfo_file($finfo, $this->filename);
	        finfo_close($finfo);
	        break;
	      case (function_exists('mime_content_type')):
	        $this->mime=mime_content_type($this->filename);
	        break;
	      case (array_key_exists($suffix,$this->mime_types)):
	        $this->mime=$this->mime_types[$suffix];
	        break;
	    }
	    if (!$this->mime) $this->mime="application/octet-stream";
    }
    function image() {
	    switch (TRUE) {
	      case (($this->width) && ($this->height)):
	        $this->image_width=$this->width;
	        $this->image_height=$this->height;
	        break;
	      case ($this->width):
	        $this->image_width=$this->width;
	        $this->image_height=round($this->image_info[1] * ($this->width / $this->image_info[0]));
	        break;
	      case ($this->height):
	        $this->image_height=$this->height;
	        $this->image_width=round($this->image_info[0] * ($this->height / $this->image_info[1]));
	        break;
	      default;
	        $this->image_width=$this->image_info[0];
	        $this->image_height=$this->image_info[1];
	        break;
	    }
	    if (!$this->image_width) $this->image_width=1;
	    if (!$this->image_height) $this->image_height=1;

        if ($this->debug) return;
//	    header("Expires: 0");
//	    header("Content-type: " . $this->image_info['mime']);
	    switch (TRUE) {
	      case ((!$this->wm) && ($this->image_width==$this->image_info[0]) && ($this->image_height==$this->image_info[1])):
	        readfile($this->filename);
	        break;
	      case ($this->image_info[2]==1):
	        $this->image_handle=imagecreatetruecolor($this->image_width, $this->image_height);
	        $this->image_info_source=imagecreatefromgif($this->filename);
	        imagecopyresampled($this->image_handle, $this->image_info_source, 0, 0, 0, 0, $this->image_width, $this->image_height, $this->image_info[0], $this->image_info[1]);
			$this->watermark();
	        imagegif($this->image_handle);
	        break;
	      case ($this->image_info[2]==2):
	        $this->image_handle=imagecreatetruecolor($this->image_width, $this->image_height);
	        $this->image_info_source=imagecreatefromjpeg($this->filename);
	        imagecopyresampled($this->image_handle, $this->image_info_source, 0, 0, 0, 0, $this->image_width, $this->image_height, $this->image_info[0], $this->image_info[1]);
			$this->watermark();
	        imagejpeg($this->image_handle);
	        break;
	      case ($this->image_info[2]==3):
	        $this->image_handle=imagecreatetruecolor($this->image_width, $this->image_height);
	        imagealphablending($this->image_handle, FALSE);
	        imagesavealpha($this->image_handle,TRUE);
	        imagecolorallocatealpha($this->image_handle,255,255,255,127);
	        $this->image_info_source=imagecreatefrompng($this->filename);
	        imagecopyresampled($this->image_handle, $this->image_info_source, 0, 0, 0, 0, $this->image_width, $this->image_height, $this->image_info[0], $this->image_info[1]);
			$this->watermark();
	        imagepng($this->image_handle);
	        break;
	    }
        if (isset($this->image_handle)) imagedestroy($this->image_handle);
        if (isset($this->wm_handle)) imagedestroy($this->wm_handle);
    }
    function watermark() {
    global $database;
	    imagealphablending($this->image_handle, FALSE);
	    imagesavealpha($this->image_handle, TRUE);
        if (!array_key_exists($this->wm,$database->registry->wm)) return;
		$this->wm_filename=$database->registry->wm->{$this->wm}['url'];
        if (!file_exists($this->wm_filename)) return;
		$this->wm_info=getimagesize($this->wm_filename);
	    $this->wm_handle=imagecreatefromgif($this->wm_filename);
	    imageAlphaBlending($this->wm_handle, FALSE);
	    imageSaveAlpha($this->wm_handle, TRUE);

        if ($this->image_width < $this->wm_info[0]) {
        	$this->wm_width=$this->image_width;
		  } else {
        	$this->wm_width=$this->wm_info[0];
		}
        if ($this->image_height < $this->wm_info[1]) {
        	$this->wm_height=$this->image_height;
		  } else {
        	$this->wm_height=$this->wm_info[1];
		}
        switch (TRUE) {
          case (in_array($database->registry->wm->{$this->wm}['location'],array(1,4,7))): // left
			$this->wm_x=0;
          	break;
          case (in_array($database->registry->wm->{$this->wm}['location'],array(2,5,8))): // center
			$this->wm_x=intval((($this->image_width - $this->wm_width) / 2) + 1);
          	break;
          case (in_array($database->registry->wm->{$this->wm}['location'],array(3,6,9))): // right
			$this->wm_x=(($this->image_width - $this->wm_width) + 1);
          	break;
        }
        switch (TRUE) {
          case (in_array($database->registry->wm->{$this->wm}['location'],array(1,2,3))): // top
			$this->wm_y=0;
          	break;
          case (in_array($database->registry->wm->{$this->wm}['location'],array(4,5,6))): // middle
			$this->wm_y=intval((($this->image_height - $this->wm_height) /2) + 1);
          	break;
          case (in_array($database->registry->wm->{$this->wm}['location'],array(7,8,9))): // bottom
			$this->wm_y=(($this->image_height - $this->wm_height) + 1);
          	break;
        }
		imagecopymerge($this->image_handle,$this->wm_handle,$this->wm_x,$this->wm_y,0,0,$this->wm_width,$this->wm_height,$database->registry->wm->{$this->wm}['opacity']);
    }
    function debug() {
	    print "<p>File manager debugger version " . key($this->scs_version()) . "</p>\n";
        print "<p>Called variables";
	    foreach ($_GET as $key => $value) {
	        print "<br>$key: $value";
	    }
        print "</p>\n";
        print "<p>Loaded variables:\n";
		if ($this->type) print "<br>Type: " . $this->type;
		if ($this->code) print "<br>Code: " . $this->code;
		if ($this->width) print "<br>Width: " . $this->width;
		if ($this->image_width) print "<br>Image width: " . $this->image_width;
		if ($this->height) print "<br>Height: " . $this->height;
		if ($this->image_height) print "<br>Image height: " . $this->image_height;
		if ($this->filename) print "<br>File name: " . $this->filename;
		if ($this->image_source) print "<br>Source: " . $this->image_source;
		if ($this->wm) print "<br>Watermark: " . $this->wm;
   		if ($this->error) print "<br> Error: <b>" . $this->error . "</b>";
        print "</p>\n";
		if ($this->image_info) {
        	print "<p>Image information";
            foreach ($this->image_info as $key => $value) {
                print "<br>$key: $value";
            }
            print "</p>\n";
		}
	}
}

?>