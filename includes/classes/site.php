<?php
$database->site=new site_class();
class site_class extends database_class {
    function scs_table_version() {
    	$results=array();
        $results['07/22/2012']="Initial release";
		$results['file']= __FILE__;         
        return $results;
	}
	function __construct() {
    	$this->data=new site_data_class();
        $this->fetch=array();
        $this->constant=new site_constant_class();
    	$this->meta=new database_meta_class();
    }
    function fetch($fetch=FALSE) {
        if ($fetch) $this->fetch=$this->fetch_array();
        $this->data=new site_data_class();
	    $this->data->folder=$this->fetch['folder'];
	    $this->data->file=$this->fetch['file'];
	    $this->data->type=$this->fetch['type'];
	    $this->data->size=$this->fetch['size'];
	    $this->data->md5=$this->fetch['md5'];
	    $this->data->create_datetime=$this->fetch['create_datetime'];
	    $this->data->modify_datetime=$this->fetch['modify_datetime'];
	    $this->data->build_datetime=$this->fetch['build_datetime'];
    }
    function update() {
		switch (TRUE) {
          case ($this->data->size > 1000000):
          case (!fn_development_server()):
			$contents="''";
			break;
          default:
			$contents="LOAD_FILE('" . $_SERVER['DOCUMENT_ROOT'] . substr("{$this->data->folder}/{$this->data->file}",1) . "')";
        }
		$query =
 			"replace site set " .
			"folder=" . fn_escape($this->data->folder) . "," .
			"file=" . fn_escape($this->data->file) . "," .
            "type="  . fn_escape($this->data->type) . "," .
            "size=" . fn_escape($this->data->size,FALSE) . "," .
            "md5=" . fn_escape($this->data->md5) . "," .
	        "contents=$contents, " .
            "create_datetime=" . fn_escape($this->data->create_datetime,FALSE) . "," .
            "modify_datetime=" . fn_escape($this->data->modify_datetime,FALSE) . "," .
            "build_datetime=" . fn_escape($this->constant->build_datetime,FALSE);
		$this->query($query);
    }
    function clear() {
    	$query="delete from site where build_datetime <> " . fn_escape($this->constant->build_datetime);
        $this->query($query);
    }
    function action($build=FALSE) {
		if ($build) $this->constant->action['build']="Build master list";
    }
}
class site_data_class {
	var $folder;
	var $file;
	var $type;
	var $size=0;
    var $contents;
    var $md5;
	var $create_datetime=0;
	var $modify_datetime=0;
	var $build_datetime=0;
    function __construct($folder="",$file="") {
    global $database;
		if ((!$folder) || (!$file)) return;
		$this->folder=$folder;
        $this->file=$file;
        $info=stat("{$folder}/{$file}");
        $this->size=$info['size'];
        $this->md5=md5_file("{$folder}/{$file}");
        foreach ($database->site->constant->type as $type => $item) {
	        switch (TRUE) {
	          case (!$item->pattern):
	            $this->type=$type;
	            break 2;
              case (preg_match("/{$item->pattern}/i",$file)):
	            $this->type=$type;
	            break 2;
	        }
		}
        $this->modify_datetime=$info['mtime'];
        $this->create_datetime=$info['ctime'];
    }
}
class site_constant_class {
	var $starttime;
    var $elapsedtime;
	var $build_datetime;
    var $type=array();
    var $type_list=array();
    var $action=array("compare"=>"Compare site to master list","master"=>"Master List","site"=>"Site List","scan"=>"Rescan site");
    function __construct() {
		$this->starttime=microtime(TRUE);
		$this->build_datetime=strtotime("now");
		$this->type['hidden']=new site_type_class("Miscellaneous","Hidden","."); //Must be first
// WWW Pages
        $this->type['html']=new site_type_class("Web","HTML",".htm,.html,.shtm,.shtml");
        $this->type['js']=new site_type_class("Web","Javascript",".js");
        $this->type['css']=new site_type_class("Web","CSS",".css");
// Programs
        $this->type['php']=new site_type_class("Programs","PHP",".php");
        $this->type['asp']=new site_type_class("Programs","ASP",".asp");
        $this->type['xml']=new site_type_class("Programs","XML",".xml");
        $this->type['exe']=new site_type_class("Programs","Executable",".exe");
// File Formats
        $this->type['image']=new site_type_class("Files","Images",".jpg,.gif,.png");
        $this->type['txt']=new site_type_class("Files","Text",".txt,.csv");
        $this->type['xls']=new site_type_class("Files","Microsoft Excel",".xls,.xlsx,.xlsm");
        $this->type['doc']=new site_type_class("Files","Microsoft Word",".doc,.docx,.docm");
        $this->type['pdf']=new site_type_class("Files","PDF",".pdf");
        $this->type['swf']=new site_type_class("Files","Shockwave Flash",".swf");
// Miscellaneous
        $this->type['bak']=new site_type_class("Miscellaneous","Backup files",".bak");
        $this->type['other']=new site_type_class("Miscellaneous","Other","");

		$miscellaneous=array();
        foreach ($this->type as $key => $item) {
			if ($item->group == "Miscellaneous") {
            	$miscellaneous[$key]=$item->name;
			  } else {
				$this->type_list[$item->group][$key]=$item->name;
            }
        }
        $this->type_list["Miscellaneous"]=$miscellaneous;
    }
    function elapsedtime($elapsedtime=0) {
    global $forms;
	    $stoptime=microtime(TRUE);
		$this->elapsedtime=(floatval($elapsedtime) + $stoptime - $this->starttime);
	    $forms->message[]="Elapsed time: " . number_format($this->elapsedtime,2) . " seconds";
        return $this->elapsedtime;
    }
}
class site_type_class {
	var $group;
	var $name;
    var $pattern;
    function __construct($group,$name,$files) {
		$this->group=$group;
		$this->name="$name";
        if ($files) $this->name .= " ($files)";
		switch (TRUE) {
		  case (!$files):
			break;
		  case ($files == "."):
			$this->pattern="(substr(\$file,0,1)=='.')";
			break;
		  default:
			$file_array=explode(",",$files);
            foreach ($file_array as $item) {
				if ($this->pattern) $this->pattern .= "|";
				$this->pattern .= ".{$item}$";
            }
        }
    }
}
/* Revised 07/22/2012
CREATE TABLE `site` (
  `folder` varchar(512) NOT NULL,
  `file` varchar(256) NOT NULL,
  `type` varchar(12) DEFAULT NULL,
  `size` int(12) unsigned DEFAULT '0',
  `md5` varchar(32) DEFAULT NULL,
  `contents` longblob,
  `create_datetime` int(12) unsigned DEFAULT '0',
  `modify_datetime` int(12) unsigned DEFAULT '0',
  `build_datetime` int(12) unsigned DEFAULT '0',
  PRIMARY KEY (`folder`,`file`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Web Site File Stats'
*/
?>