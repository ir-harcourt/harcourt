<?php
/*
http://localhost:8093/scs_file.php?url=C:/www/group_obsolete/nwmachine/inventory_images/I5001-2.jpg
http://localhost:8093/scs_file.php?url=Qzovd3d3L2dyb3VwX29ic29sZXRlL253bWFjaGluZS9pbnZlbnRvcnlfaW1hZ2VzL0k1MDAxLTIuanBn
*/
function fn_pre($text) {
	print "<pre>" . print_r($text,TRUE) . "</pre>";
    if (func_num_args() ) die();
}
class scs_file_class {
    var $type;
    var $filename;
    var $width=0;
    var $height=0;
    var $output_width=0;
    var $output_height=0;
    var $rotate=1;
    var $override;

// content variables
	var $options=array();
    var $content;
    var $content_meta;
    var $output_name;

// watermark variables
    var $watermark;
    var $watermark_x=0;
    var $watermark_y=0;

// internal variables
    var $logfile;
    var $log_success;
    var $starttime=0;
    var $image_handle;
    var $trace=array();
    var $results=array();
    function scs_version() {
    	$results=array();
        $results['11/03/2023']="Rotation issues";        
        $results['03/04/2023']="Allow for url files, rotation";
        $results['01/15/2020']="Based on file_rev1 (03/08/2015)";
        $results['06/10/2014']="Add 1x1 gif if image missing";
        $results['04/15/2014']="rev1 release";
        $results['07/26/2013']="rev0 General cleanup";
		$results['file']= __FILE__;
        return $results;
    }
	function __construct($logfile="",$log_success=FALSE) {
    	$this->trace[]=__FUNCTION__;
		$this->starttime=microtime(TRUE);
		$this->logfile=$logfile;
        $this->log_success=$log_success;
        $this->content_meta=new stdClass();
        $this->options=new stdClass();
	}
    function output($options=array(),$content="",$content_meta=array() ) {
    	$this->trace[]=__FUNCTION__;
        if (strlen($options['logfile'])) {
        	$this->logfile=$options['logfile'];
            $this->log_success=TRUE;
		}
        if (strlen($options['url'])) {
	        $this->options->filename=base64_decode($options['url']);
	        $this->options->url=TRUE;
          } else {
			$this->options->filename=(strlen($options['filename'])) ?  $options['filename'] : "";
	        $this->options->url=FALSE;
		}
        $this->rotate=(strlen($options['rotate'])) ? $options['rotate'] : 0;
        $this->filename=$this->options->filename;
		$this->options->code=( (strlen($options['code'])) ?  $options['code'] : "");
		$this->options->width=( (strlen($options['width'])) ?  $options['width'] : 0);
		$this->options->height=( (strlen($options['height'])) ?  $options['height'] : 0);
		$this->options->override=( (strlen($options['override'])) ?  $options['override'] : 0);
		$this->options->watermark=( (strlen($options['watermark'])) ?  $options['watermark'] : 0);
		$this->options->obfuscate=( (strlen($options['obfuscate'])) ?  $options['obfuscate'] : 0);
        $this->content=$content;
		if (strlen($this->content)) {
			$this->results[]="Content size: " . number_format(strlen($content));
            $this->content_meta=$content_meta;
		  } else {
        	$this->load();
		}
		if ($this->content_meta->type) {
			$this->results[]="Output: Image";
        	$this->image();
		  } else {
			$this->results[]="Output: File";
        	$this->file();
		}
        $this->complete();
	}
    function complete($status="") {
    	$this->trace[]=__FUNCTION__;
		switch (TRUE) {
          case (!$this->logfile):
		  case ( (!$status) && (!$this->log_success) ):
			die();
		}
        $this->results[]="Trace: " . implode(", ",$this->trace);
        if ($_SERVER['HTTP_REFERER']) {
            $this->results[]="Script: " . $_SERVER['HTTP_REFERER'];
          } else {
            $this->results[]="Script: Direct call";
        }
        foreach ($this->options as $key => $value) {
            $this->results[]="Option $key: $value";
        }
        foreach ($this->content_meta as $key => $value) {
            $this->results[]="Meta {$key}: {$value}";
        }
        $this->results[]="Output name: " . $this->output_name;
        $this->results[]="Date/Time: " . date("m/d/Y g:i:s A");
        $this->results[]="Elapsed: " . number_format((microtime(TRUE) - $this->starttime),2) . " seconds";
        if ($status) $this->results[]="Error: $status";
        $this->results[]="\r\n";
        $fh=fopen($this->logfile,'a');
        fwrite($fh,implode("\r\n",$this->results));
        fclose($fh);
        die;
    }
    function load() {
    global $database;
    	$this->trace[]=__FUNCTION__;
	    switch (TRUE) {
		  case ( (!$this->filename) && (!$this->options->code) ):
          	$this->complete("Filename / code missing");
            break;
	      case ($this->filename):
	        $this->results[]="File source: filename";
	        break;
	      case (!$this->type):
	      case (!isset($database->registry)):
	      case (!is_array($database->registry->files)):
	        $this->filename=$this->options->code;
	        $this->results[]="File source: code";
	        break;
	      case (!is_array($database->registry->files->{$this->type})):
	        $this->filename=$database->registry->files->{$this->type}[$this->options->code];
	        $this->results[]="File source: Registry static name";
	        break;
	      case (!array_key_exists($this->type,$database->registry->files)):
          	$this->complete("Registry files entry missing "  . $this->type);
            break;
	      case (substr($database->registry->files->{$this->type},-1,1)=="/"):
	        $this->filename=$database->registry->files->{$this->type} . $this->options->code;
	        $this->results[]="File source: Registry files-> type + code (variable name)";
	        break;
	      default:
	        $this->filename=$database->registry->files->{$this->type} . "/" . $this->options->code;
	        $this->results[]="File source: Registry files-> type / code";
	    }

	    if (!strlen($this->filename)) $this->complete("File name missing");
        switch (TRUE) {
          case ($this->options->url):
          	break;
          case (substr($this->filename,0,1) != "/"):
        	$this->filename=$_SERVER['DOCUMENT_ROOT'] . "/" . $this->filename;
            break;
		  default:
        	$this->filename=$_SERVER['DOCUMENT_ROOT'] . $this->filename;
		}
        $this->results[]="File name: " . $this->filename;
	    if (!file_exists($this->filename)) $this->complete("File does not exist");
		$this->content_meta=new file_meta_class($this->filename);
    }
	function image() {
    	$this->trace[]=__FUNCTION__;
		if ((!$this->content_meta->width) || (!$this->content_meta->height)) $this->complete("Not an image");
		$this->width=intval($this->options->width);
		$this->height=intval($this->options->height);
	    if ($this->width > $this->content_meta->width) $this->width=0;
	    if ($this->height > $this->content_meta->height) $this->height=0;
        //if (!$this->rotate) $this->meta->rotation=0;
	    switch (TRUE) {
	      case (($this->width) && ($this->height)):
	        $this->output_width=$this->width;
	        $this->output_height=$this->height;
	        break;
	      case ($this->width):
	        $this->output_width=$this->width;
	        $this->output_height=round($this->content_meta->height * ($this->width / $this->content_meta->width));
	        break;
	      case ($this->height):
	        $this->output_height=$this->height;
	        $this->output_width=round($this->content_meta->width * ($this->height / $this->content_meta->height));
	        break;
	      default;
	        $this->output_width=$this->content_meta->width;
	        $this->output_height=$this->content_meta->height;
	        break;
	    }
	    if (!$this->output_width) $this->output_width=1;
	    if (!$this->output_height) $this->output_height=1;
        $this->output_name();
		$this->results[]="Display width: " . $this->output_width;
		$this->results[]="Display height: " . $this->output_height;
		$this->results[]="Rotation: " . $this->meta->rotation;
        header("Content-type: " . $this->content_meta->mime);
        header("Pragma: no-cache");
        header("Expires: 0");
        if ($this->override) {
			$this->results[]="File: Attachment";
        	header("Content-Disposition: attachment; filename=" . $this->output_name());
		}
		if (($this->output_width == $this->content_meta->width) && ($this->output_height == $this->content_meta->height) && (!$this->watermark) ) {
			$this->results[]="Output: Raw";
            if ($this->content) {
            	echo $this->content;
			  } else {
            	readfile($this->filename);
			}
	        return;
		}
        if (!in_array($this->content_meta->type,array(1,2,3))) $this->complete("content_meta->type not defined: " . $this->content_meta->type);
		$this->results[]="Memory before: " . number_format(memory_get_usage(TRUE));
        $this->image_handle=imagecreatetruecolor($this->output_width, $this->output_height);
        imagealphablending($this->image_handle, FALSE);
		imagesavealpha($this->image_handle,TRUE);
		if ($this->content) $this->image_source=imagecreatefromstring($this->content);
	    switch ($this->content_meta->type) {
	      case 1:
			$this->results[]="Output: imagecreatefromgif";
			imagecolorallocatealpha($this->image_handle,255,255,255,127);
			if (!$this->content) $this->image_source=imagecreatefromgif($this->filename);
			$this->image_process();
	        imagegif($this->image_output());
	        break;
	      case 2:
			$this->results[]="Output: imagecreatefromjpeg";
            if (!$this->content) $this->image_source=imagecreatefromjpeg($this->filename);
			$this->image_process();
	        imagejpeg($this->image_output());
	        break;
		  case 3:
			$this->results[]="Output: imagecreatefrompng";
	        imagecolorallocatealpha($this->image_handle,255,255,255,127);
            if (!$this->content) $this->image_source=imagecreatefrompng($this->filename);
			$this->image_process();
	        imagepng($this->image_output());
	        break;
		}
		$this->results[]="Memory after: " . number_format(memory_get_usage(TRUE));
        imagedestroy($this->image_handle);
		$this->results[]="Memory final: " . number_format(memory_get_usage(TRUE));
    }
    function image_process() {
    	$this->trace[]=__FUNCTION__;
		imagecopyresampled($this->image_handle, $this->image_source, 0, 0, 0, 0, $this->output_width, $this->output_height, $this->content_meta->width, $this->content_meta->height);
        if ($this->watermark) $this->watermark($this->output_width,$this->output_height);
    }
    function image_output() {
		return (!$this->content_meta->rotation) ? $this->image_handle : imagerotate($this->image_handle, $this->content_meta->rotation, 0);
    }
    function file() {
    	$this->trace[]=__FUNCTION__;
        if (in_array($this->content_meta->mime,array('text/plain','text/html'))) {
        	$override=0;
		  } else {
			$override=1;
		}
		header("Content-type: " . $this->content_meta->mime);
        header("Pragma: no-cache");
        header("Expires: 0");
        if (intval($this->override) != $override) {
			$this->results[]="File: Attachment";
        	header("Content-Disposition: attachment; filename=" . $this-output_>name());
		}
		if ($this->content) {
			echo $this->content;
		  } else {
        	readfile($this->filename);
		}
		$this->complete();
    }
    function thumbnail() {
    	$this->trace[]=__FUNCTION__;
		$thumbnail=exif_thumbnail($this->filename, $width, $height, $type);
		header('Content-type: ' . image_type_to_mime_type($type));
	    echo $thumbnail;
	    die;
    }
    function watermark() {
    global $database;
    	$this->trace[]=__FUNCTION__;
		switch (TRUE) {
		  case (!isset($database)):
		  case (!isset($database->registry)):
		  case (!isset($database->registry->watermark)):
          case (!array_key_exists($this->watermark,$database->registry->watermark)):
		  case (!$database->registry->watermark->{$this->watermark}['url']);
			$this->results[]="Watermark Not Defined: " . $this->watermark;
			return;
		}
		$this->watermark_filename=$_SERVER['DOCUMENT_ROOT'] . $database->registry->watermark->{$this->watermark}['url'];
        if (!file_exists($this->watermark_filename)) {
			$this->results[]="Watermark Missing: " .$this->watermark_filename;
            return;
        }
        foreach ($database->registry->watermark->{$this->watermark} as $key => $value) {
			$this->results[]="Watermark $key: $value";
        }
		$this->watermark_info=getimagesize($this->watermark_filename);
		switch ($this->watermark_info[2]) {
		  case 1:
			$this->results[]="Watermark: gif";
			$this->watermark_handle=imagecreatefromgif($this->watermark_filename);
            break;
		  case 2:
			$this->results[]="Watermark: jpg";
			$this->watermark_handle=imagecreatefromjpr($this->watermark_filename);
            break;
		  case 3:
			$this->results[]="Watermark: png";
			$this->watermark_handle=imagecreatefrompng($this->watermark_filename);
            break;
		}
	    imageAlphaBlending($this->watermark_handle, FALSE);
	    imageSaveAlpha($this->watermark_handle, TRUE);
        if ($this->output_width < $this->watermark_info[0]) {
        	$this->watermark_width=$this->output_width;
		  } else {
        	$this->watermark_width=$this->watermark_info[0];
		}
        if ($this->output_height < $this->watermark_info[1]) {
        	$this->watermark_height=$this->output_height;
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
	    $this->results[]="Watermark width: " . $this->watermark_width;
	    $this->results[]="Watermark height: " . $this->watermark_height;
		$this->results[]="Watermark XY X: " . $this->watermark_x . " Y: " . $this->watermark_y;
		imagecopymerge($this->image_handle,$this->watermark_handle,$this->watermark_x,$this->watermark_y,0,0,$this->watermark_width,$this->watermark_height,$database->registry->watermark->{$this->watermark}['opacity']);
    }
    function output_name() {
    	$this->trace[]=__FUNCTION__;
        if ($this->options->obfuscate) {
        	$url=explode(".",$this->content_meta->name);
            $this->output_name=bin2hex(openssl_random_pseudo_bytes(40)) . "." . end($url);
		  } else {
			$this->output_name=$this->content_meta->name;
		}
		return $this->content_meta->name;
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
    var $rotation=0;
    function __construct($filename="", $upload_file="") {
		if (!$filename) return;
		$this->name=end(preg_split("/\//",$filename));
        if (!$upload_file) $upload_file=$filename;
        if (!file_exists($upload_file)) return;
		$this->mime=$this->mime($filename);
        $this->image($upload_file);
    }
    function image($upload_file) {
        $image_info=getimagesize($upload_file);
        if (!is_array($image_info)) return;
        $this->width=$image_info[0];
        $this->height=$image_info[1];
        $this->type=$image_info[2];
        $this->dimensions=preg_replace("/\"/","'",$image_info[3]);
        $this->bits=$image_info['bits'];
        $this->channels=$image_info['channels'];
        $this->mime=$image_info['mime'];

		if (!extension_loaded("exif")) return;
		$this->exif=@exif_read_data($upload_file);
        if (empty($this->exif['Orientation'])) return;
        switch ($this->exif['Orientation']) {
          case 8:
            $this->rotation=90;
            break;
          case 3:
            $this->rotation=180;
            break;
          case 6:
            $this->rotation=270;
            break;
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