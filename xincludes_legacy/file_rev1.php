<?php
/* Notes
add watermark!!!!
registry has default watermark
*/
class scs_file_class {
	var $code;
    var $type;
    var $filename;
    var $width=0;
    var $height=0;
    var $override;

// content variables
	var $options=array();
    var $content;
    var $content_meta;

// watermark variables
    var $watermark;
    var $watermark_x=0;
    var $watermark_y=0;

// internal variables
    var $logfile;
    var $log_success;
    var $starttime;
    var $image_handle;
    var $results=array();
    function scs_version() {
    	$results=array();
        $results['03/08/2015']="Add watermark";
        $results['06/10/2014']="Add 1x1 gif if image missing";
        $results['04/15/2014']="rev1 release";
        $results['07/26/2013']="rev0 General cleanup";
		$results['file']= __FILE__;
        return $results;
    }
	function __construct($logfile="",$log_success=FALSE) {
		$this->starttime=microtime(TRUE);
		$this->logfile=$logfile;
        $this->log_success=$log_success;
        $this->content_meta=new stdClass();
	}
    function output($options=array(),$content="",$content_meta=array() ) {
		$this->options=$options;
		foreach ($this->options as $options_code => $options_value) {
			if (in_array($options_code,array("code","type","filename","width","height","override","watermark"))) $this->$options_code=$options_value;
        }
        $this->content=$content;
		if (strlen($this->content)) {
			$this->results['Content size']=number_format(strlen($content));
            $this->content_meta=$content_meta;
		  } else {
        	$this->load();
		}
		if ($this->content_meta->type) {
			$this->results['Output']="Image";
        	$this->image();
		  } else {
			$this->results['Output']="File";
        	$this->file();
		}
        $this->complete();
	}
    function complete($status="") {
		switch (TRUE) {
          case (!$this->logfile):
		  case ( (!$status) && (!$this->log_success) ):
			break;
          default:
			$results=array();
			if ($_SERVER['HTTP_REFERER']) {
				$results[]="Script: " . $_SERVER['HTTP_REFERER'];
			  } else {
				$results[]="Script: Direct call";
			}
			foreach ($this->options as $key => $value) {
				$results[]="Option $key: $value";
            }
			foreach ($this->results as $key => $value) {
				$results[]="$key: $value";
            }
			foreach ($this->content_meta as $key => $value) {
				$results[]="Meta $key: $value";
            }
            $results[]="Date/Time: " . date("m/d/Y g:i:s A");
            $results[]="Elapsed: " . number_format((microtime(TRUE) - $this->starttime),2) . " seconds";
            if ($status) $results[]="Error: $status";
	        $results[]="\r\n";
			$fh=fopen($this->logfile,'a');
			fwrite($fh,implode("\r\n",$results));
            fclose($fh);
		}
        die;
    }
    function load() {
    global $database;
		$this->results['Engine']="File";
	    switch (TRUE) {
		  case ( (!$this->filename) && (!$this->code) ):
          	$this->complete("Filename / code missing");
	      case ($this->filename):
	        $this->results["File source"]="filename";
	        break;
	      case (!$this->type):
	      case (!isset($database->registry)):
	      case (!is_array($database->registry->files)):
	        $this->filename=$this->code;
	        $this->results["File source"]="code";
	        break;
	      case (!is_array($database->registry->files->{$this->type})):
	        $this->filename=$database->registry->files->{$this->type}[$this->code];
	        $this->results["File source"]="Registry static name";
	        break;
	      case (!array_key_exists($this->type,$database->registry->files)):
          	$this->complete("Registry files entry missing "  . $this->type);
	      case (substr($database->registry->files->{$this->type},-1,1)=="/"):
	        $this->filename=$database->registry->files->{$this->type} . $this->code;
	        $this->results["File source"]="Registry files-> type + code (variable name)";
	        break;
	      default:
	        $this->filename=$database->registry->files->{$this->type} . "/" . $this->code;
	        $this->results["File source"]="Registry files-> type / code";
	    }
	    if (!$this->filename) $this->complete("File name missing");
        if (substr($this->filename,0,1) != "/") {
        	$this->filename=$_SERVER['DOCUMENT_ROOT'] . "/" . $this->filename;
		  } else {
        	$this->filename=$_SERVER['DOCUMENT_ROOT'] . $this->filename;
		}
        $this->results['Filename']=$this->filename;
	    if (!file_exists($this->filename)) $this->complete("File does not exist");
		$this->content_meta=new file_meta_class($this->filename);
    }
	function image() {
		if ((!$this->content_meta->width) || (!$this->content_meta->height)) $this->complete("Not an image");
		$this->width=intval($this->width);
		$this->height=intval($this->height);
	    if ($this->width > $this->content_meta->width) $this->width=0;
	    if ($this->height > $this->content_meta->height) $this->height=0;
	    switch (TRUE) {
	      case (($this->width) && ($this->height)):
	        $display_width=$this->width;
	        $display_height=$this->height;
	        break;
	      case ($this->width):
	        $display_width=$this->width;
	        $display_height=round($this->content_meta->height * ($this->width / $this->content_meta->width));
	        break;
	      case ($this->height):
	        $display_height=$this->height;
	        $display_width=round($this->content_meta->width * ($this->height / $this->content_meta->height));
	        break;
	      default;
	        $display_width=$this->content_meta->width;
	        $display_height=$this->content_meta->height;
	        break;
	    }
	    if (!$display_width) $display_width=1;
	    if (!$display_height) $display_height=1;
		$this->results["Display width"]=$display_width;
		$this->results["Display height"]=$display_height;
        header("Content-type: " . $this->content_meta->mime);
        header("Pragma: no-cache");
        header("Expires: 0");
        if ($this->override) {
			$this->results['File']="Attachment";
        	header("Content-Disposition: attachment; filename=" . $this->content_meta->name);
		}
		if (($display_width == $this->content_meta->width) && ($display_height == $this->content_meta->height) && (!$this->watermark) ) {
			$this->results['Image Output']="Raw";
            if ($this->content) {
            	echo $this->content;
			  } else {
            	readfile($this->filename);
			}
	        return;
		}
	    switch ($this->content_meta->type) {
	      case 1:
			$this->results['Image Output']="imagecreatefromgif";
	        $this->image_handle=imagecreatetruecolor($display_width, $display_height);
	        imagealphablending($this->image_handle, FALSE);
			imagesavealpha($this->image_handle,TRUE);
			imagecolorallocatealpha($this->image_handle,255,255,255,127);
			if ($this->content) {
            	$image_source=imagecreatefromstring($this->content);
			  } else {
				$image_source=imagecreatefromgif($this->filename);
			}
	        imagecopyresampled($this->image_handle, $image_source, 0, 0, 0, 0, $display_width, $display_height, $this->content_meta->width, $this->content_meta->height);
            if ($this->watermark) $this->watermark($display_width,$display_height);
	        imagegif($this->image_handle);
	        imagedestroy($this->image_handle);
	        return;
	      case 2:
			$this->results['Image Output']="imagecreatefromjpeg";
	        $this->image_handle=imagecreatetruecolor($display_width, $display_height);
	        imagealphablending($this->image_handle, FALSE);
			imagesavealpha($this->image_handle,TRUE);
			if ($this->content) {
            	$image_source=imagecreatefromstring($this->content);
			  } else {
				$image_source=imagecreatefromjpeg($this->filename);
			}
	        imagecopyresampled($this->image_handle, $image_source, 0, 0, 0, 0, $display_width, $display_height, $this->content_meta->width, $this->content_meta->height);
            if ($this->watermark) $this->watermark($display_width,$display_height);
	        imagejpeg($this->image_handle);
	        imagedestroy($this->image_handle);
	        return;
		  case 3:
			$this->results['Image Output']="imagecreatefrompng";
	        $this->image_handle=imagecreatetruecolor($display_width, $display_height);
	        imagealphablending($this->image_handle, FALSE);
			imagesavealpha($this->image_handle,TRUE);
	        imagecolorallocatealpha($this->image_handle,255,255,255,127);
			if ($this->content) {
            	$image_source=imagecreatefromstring($this->content);
			  } else {
				$image_source=imagecreatefrompng($this->filename);
			}
	        imagecopyresampled($this->image_handle, $image_source, 0, 0, 0, 0, $display_width, $display_height, $this->content_meta->width, $this->content_meta->height);
            if ($this->watermark) $this->watermark($display_width,$display_height);
	        imagepng($this->image_handle);
	        imagedestroy($this->image_handle);
	        return;
		  default:
			$this->complete("content_meta->type not defined: " . $this->content_meta->type);
		}
    }
    function file() {
        if (in_array($this->content_meta->mime,array('text/plain','text/html'))) {
        	$override=0;
		  } else {
			$override=1;
		}
		header("Content-type: " . $this->content_meta->mime);
        header("Pragma: no-cache");
        header("Expires: 0");
        if (intval($this->override) != $override) {
			$this->results['File']="Attachment";
        	header("Content-Disposition: attachment; filename=" . $this->content_meta->name);
		}
		if ($this->content) {
			echo $this->content;
		  } else {
        	readfile($this->filename);
		}
		$this->complete();
    }
    function thumbnail() {
		$thumbnail=exif_thumbnail($this->filename, $width, $height, $type);
		header('Content-type: ' . image_type_to_mime_type($type));
	    echo $thumbnail;
	    die;
    }
    function watermark($display_width,$display_height) {
    global $database;
		switch (TRUE) {
		  case (!isset($database)):
		  case (!isset($database->registry)):
		  case (!isset($database->registry->watermark)):
          case (!array_key_exists($this->watermark,$database->registry->watermark)):
		  case (!$database->registry->watermark->{$this->watermark}['url']);
			$this->results['Watermark Not Defined']=$this->watermark;
			return;
		}
		$this->watermark_filename=$_SERVER['DOCUMENT_ROOT'] . $database->registry->watermark->{$this->watermark}['url'];
        if (!file_exists($this->watermark_filename)) {
			$this->results['Watermark Missing']=$this->watermark_filename;
            return;
        }
        foreach ($database->registry->watermark->{$this->watermark} as $key => $value) {
			$this->results["Watermark $key"]=$value;
        }
		$this->watermark_info=getimagesize($this->watermark_filename);
		switch ($this->watermark_info[2]) {
		  case 1:
			$this->results["Watermark type"]="gif";
			$this->watermark_handle=imagecreatefromgif($this->watermark_filename);
            break;
		  case 2:
			$this->results["Watermark type"]="jpg";
			$this->watermark_handle=imagecreatefromjpr($this->watermark_filename);
            break;
		  case 3:
			$this->results["Watermark type"]="png";
			$this->watermark_handle=imagecreatefrompng($this->watermark_filename);
            break;
		}
	    imageAlphaBlending($this->watermark_handle, FALSE);
	    imageSaveAlpha($this->watermark_handle, TRUE);
        if ($display_width < $this->watermark_info[0]) {
        	$this->watermark_width=$display_width;
		  } else {
        	$this->watermark_width=$this->watermark_info[0];
		}
        if ($display_height < $this->watermark_info[1]) {
        	$this->watermark_height=$display_height;
		  } else {
        	$this->watermark_height=$this->watermark_info[1];
		}
        switch (TRUE) {
          case (in_array($database->registry->watermark->{$this->watermark}['location'],array(1,4,7))): // left
			$this->watermark_x=0;
          	break;
          case (in_array($database->registry->watermark->{$this->watermark}['location'],array(2,5,8))): // center
			$this->watermark_x=intval((($this->width - $this->watermark_width) / 2) + 1);
          	break;
          case (in_array($database->registry->watermark->{$this->watermark}['location'],array(3,6,9))): // right
			$this->watermark_x=(($this->width - $this->watermark_width) + 1);
          	break;
        }
        switch (TRUE) {
          case (in_array($database->registry->watermark->{$this->watermark}['location'],array(1,2,3))): // top
			$this->watermark_y=0;
          	break;
          case (in_array($database->registry->watermark->{$this->watermark}['location'],array(4,5,6))): // middle
			$this->watermark_y=intval((($this->height - $this->watermark_height) /2) + 1);
          	break;
          case (in_array($database->registry->watermark->{$this->watermark}['location'],array(7,8,9))): // bottom
			$this->watermark_y=(($this->height - $this->watermark_height) + 1);
          	break;
        }
	    $this->results["Watermark width"]=$this->watermark_width;
	    $this->results["Watermark height"]=$this->watermark_height;
		$this->results['Watermark XY']="X: " . $this->watermark_x . " Y: " . $this->watermark_y;
		imagecopymerge($this->image_handle,$this->watermark_handle,$this->watermark_x,$this->watermark_y,0,0,$this->watermark_width,$this->watermark_height,$database->registry->watermark->{$this->watermark}['opacity']);
    }

}
class file_meta_class {
	var $name;
    var $mime;
    var $width=0;
    var $height=0;
    var $type=0;
    var $dimensions;
    var $bits=0;
	var $channels=0;
    function __construct($filename="",$upload_file="") {
		if (!$filename) return;
		$this->name=end(preg_split("/\//",$filename));
        if (!$upload_file) $upload_file=$filename;
        if (!file_exists($upload_file)) return;
        $image_info=getimagesize($upload_file);
        if (is_array($image_info)) {
			$this->width=$image_info[0];
			$this->height=$image_info[1];
			$this->type=$image_info[2];
			$this->dimensions=preg_replace("/\"/","'",$image_info[3]);
            $this->bits=$image_info['bits'];
            $this->channels=$image_info['channels'];
            $this->mime=$image_info['mime'];
		  } else {
			$this->mime=$this->mime($filename);
        }
    }
    function mime($filename) {
		$mime_types = array(
			//web content
	        'txt'=>'text/plain',
	        'htm'=>'text/html',
	        'html'=>'text/html',
	        'php'=>'text/html',
	        'css'=>'text/css',
	        'js'=>'application/javascript',
	        'json'=>'application/json',
	        'xml'=>'application/xml',
	        'swf'=>'application/x-shockwave-flash',
			'flv'=>'video/x-flv',

	        //images
	        'png'=>'image/png',
	        'jpe'=>'image/jpeg',
	        'jpeg'=>'image/jpeg',
	        'jpg'=>'image/jpeg',
	        'gif'=>'image/gif',
	        'bmp'=>'image/bmp',
	        'ico'=>'image/vnd.microsoft.icon',
	        'tiff'=>'image/tiff',
	        'tif'=>'image/tiff',
	        'svg'=>'image/svg+xml',
	        'svgz'=>'image/svg+xml',

	        // archives
	        'zip'=>'application/zip',
	        'rar'=>'application/x-rar-compressed',
	        'exe'=>'application/x-msdownload',
	        'msi'=>'application/x-msdownload',
	        'cab'=>'application/vnd.ms-cab-compressed',

	        // audio/video
	        'mp3'=>'audio/mpeg',
	        'qt'=>'video/quicktime',
	        'mov'=>'video/quicktime',

	        // adobe
	        'pdf'=>'application/pdf',
	        'psd'=>'image/vnd.adobe.photoshop',
	        'ai'=>'application/postscript',
	        'eps'=>'application/postscript',
	        'ps'=>'application/postscript',

	        // ms office
	        'doc'=>'application/msword',
	        'docx' => 'application/msword',
	        'rtf'=>'application/rtf',
	        'xls'=>'application/vnd.ms-excel',
	        'xlsx'=>'application/vnd.ms-excel',
	        'ppt'=>'application/vnd.ms-powerpoint',
	        'pptx' => 'application/vnd.ms-powerpoint',

	        // open office
	        'odt'=>'application/vnd.oasis.opendocument.text',
	        'ods'=>'application/vnd.oasis.opendocument.spreadsheet'
        );

		$suffix=end(preg_split("/\./",strtolower($filename)));
		if (array_key_exists($suffix,$mime_types)) {
			return $mime_types[$suffix];
		  } else {
			return "application/octet-stream";
	    }
    }
}
?>