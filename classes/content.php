<?php
require_once "file_rev1.php";
$database->content=new content_class();
class content_class extends database_class {
	function scs_table_version() {
		$results=array();
        $results['10/13/2025']="Hotfix";
        $results['07/22/2024']="Meta JSON (hotfix)";
        $results['08/12/2014']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
	function __construct() {
    	$this->data=new content_data_class();
        $this->fetch=array();
    	$this->meta=new database_meta_class();
        $this->constant=new content_constant_class();
    }
    function fetch($fetch=FALSE) {
        if ($fetch) $this->fetch=$this->fetch_array();
    	$this->data=new content_data_class();
	    $this->data->id=$this->fetch['id'];
	    $this->data->record_type=$this->fetch['record_type'];
	    $this->data->record_item=$this->fetch['record_item'];
	    $this->data->record_id=$this->fetch['record_id'];
	    $this->data->language_code=$this->fetch['language_code'];
	    $this->data->name=$this->fetch['name'];
	    $this->data->description=$this->fetch['description'];
	    $this->data->filetype=$this->fetch['filetype'];
        $this->data->meta=$this->fetch_json($this->fetch['meta']);
	    $this->data->revised=$this->fetch['revised'];
        if (in_array($this->data->filetype, array("html", "hyperlink"))) {
	        $this->data->content=$this->fetch['html_content'];
		  } else {
	        $this->data->content=$this->fetch['content'];
        }
    }
    function read($id) {
        $query =
            "select * from content " .
            "where id=" . fn_escape($id);
        $this->query($query);
        if ($this->meta->rows) {
        	$this->fetch(TRUE);
			$this->free_result();
		  } else {
			$this->data=new content_data_class();
		}
    }
    function update($update=FALSE) {
		$fields=array();
        $fields[]="id=" . fn_escape($this->data->id,FALSE);
        $fields[]="record_type=" . fn_escape($this->data->record_type);
        $fields[]="record_id=" . fn_escape($this->data->record_id,FALSE);
        $fields[]="record_item=" . fn_escape($this->data->record_item);
        $fields[]="language_code=" . fn_escape($this->data->language_code);
        $fields[]="name=" . fn_escape($this->data->name);
        $fields[]="description=" . fn_escape($this->data->description);
        $fields[]="filetype=" . fn_escape($this->data->filetype);
        $fields[]="revised=" . fn_escape($this->data->revised,FALSE);
        $fields[]="meta=" . fn_escape(json_encode($this->data->meta));
        switch (TRUE) {
          case (!strlen($this->data->content)):
          case (in_array($this->data->filetype, array("html", "hyperlink"))):
          	$fields[]="html_content=" . fn_escape($this->data->content);
        	$fields[]="content=null";
            break;
		  default:
          	$fields[]="html_content=''";
        	$fields[]="content=0x" . bin2hex($this->data->content);
		}
        $query=array();
    	if ($update) {
			$query[]="update content set";
            $query[]=implode(",\n",$fields);
            $query[]="where id=" . fn_escape($this->data->id);
		  } else {
			$query[]="insert into content set";
            $query[]=implode(",\n",$fields);
		}
		$this->query($query);
		if ((!$this->data->id) && (!$this->meta->error)) $this->data->id = $this->insert_id();
    }
    function delete($id) {
	    $query =
	        "delete from content where " .
	        "id=" . fn_escape($id);
		$this->query($query);
    }
    function page($record_type,$record_id,$options=array()) {
	    $query_where=array();
	    $query_where[]=new query_where("record_type","=",$record_type);
	    $query_where[]=new query_where("record_id","=",$record_id);
        if (array_key_exists("record_item",$options)) $query_where[]=new query_where("record_item","=",$options['record_item']);
        if (array_key_exists("filetype",$options)) $query_where[]=new query_where("filetype","=",$options['filetype']);
	    $query_where[]=new query_where("language_code","in",array("EN",$_SESSION['user']->language_code));
	    $query=
	        "select \n" .
	        "if (language_code='EN','2','1') as sort_key, \n" .
	        "content.* from content \n" .
	        $this->where($query_where) .
	        "order by sort_key";
		$this->query($query);
	    $this->fetch(TRUE);
	    $this->free_result();
    }
    function encode_page($record_type,$record_id,$options=array()) {
		$this->page($record_type,$record_id,$options);
		return $this->encode($this->data->id);
    }
    function encode($content_id) {
        return fn_base64(array($content_id, dechex($_SESSION['user']->last_login)));
    }
    function decode($text,$read=FALSE) {
		list($content_id,$last_login)=fn_base64($text, FALSE);
        switch (TRUE) {
          case (!$content_id):
		  case ($last_login != dechex($_SESSION['user']->last_login)):
			$content_id=0;
		}
        if (!$read) return $content_id;
        $this->read($content_id);
        switch (TRUE) {
          case (!isset($_SESSION['user'])):
          case (!$_SESSION['user']->last_login):
          case (!$this->meta->rows):
            return FALSE;
          default:
            return TRUE;
        }
    }
    function icon($title="") {
    global $forms;
		if (array_key_exists($this->data->mime,$this->constant->icons)) {
        	$img="icons/" . $this->constant->icons[$this->data->mime];
		  } else {
          	$img="icons/unknown.gif";
		}
		$img_options=array();
        if ($title) $img_options['title']=$title;
        return $forms->img($img,$img_options);
    }
    function fileinfo($data,$name="") {
		$data->meta=new file_meta_class();
        if (!$name) {
	        $data->filetype="";
	        $data->content="";
            return;
        }
		$data->meta->name=preg_replace("/[ ?\/\"]/","_",$name);
        $data->revised=strtotime("now");
		$data->filetype=$this->constant->filetype($data->meta->name);
        $data->meta->mime=$data->meta->mime($data->meta->name);
        if ($data->filetype == "image") {
	        $imageinfo=getimagesizefromstring($data->content);
	        if (is_array($imageinfo)) {
	            $data->meta->width=$imageinfo[0];
	            $data->meta->height=$imageinfo[1];
	            $data->meta->type=$imageinfo[2];
	            $data->meta->dimensions=$imageinfo[3];
	            if (array_key_exists('bits',$imageinfo)) $data->meta->bits=$imageinfo['bits'];
	            if (array_key_exists('channels',$imageinfo)) $data->meta->channels=$imageinfo['channels'];
	        }
        }
    }
    function output($type,$id,$options=array()) {
		$this->page($type,$id);
        if (!$this->meta->rows) return;
        $div=array("div");
        if (array_key_exists("id",$options)) $div[]="id=" . fn_escape($options['id']);
        if (array_key_exists("class",$options)) $div[]="class=" . fn_escape($options['class']);
		return "<" . implode(" ",$div) . ">\n" . $this->data->content . "\n</div>\n";
    }
    function scs_hotfix($execute=FALSE) {
        $this->hotfix=new mysql_hotfix_class("content", "Content");
        $this->hotfix->version("10/13/2025", "Drop xmeta");
        $this->hotfix->version("07/22/2024", "Field meta now an object");
        $this->hotfix->version("02/26/2015", "Initial release");
        $this->hotfix->process($execute);
    }
    function scs_hotfix_20251013($meta) {
        global $database;
        $this->hotfix->trace[]=__FUNCTION__;
        $fields=array();
        $fields[]="DROP COLUMN `xmeta`";
        $meta->count=$this->hotfix->alter($fields);
    }
    function scs_hotfix_20240722($meta) {
        global $database;
        $this->hotfix->trace[]=__FUNCTION__;
        $query=array("ALTER TABLE `content`");
        $query[]="CHANGE COLUMN `meta` `xmeta` MEDIUMTEXT NULL DEFAULT NULL,";
        $query[]="ADD COLUMN `meta` MEDIUMTEXT NULL AFTER `filetype`;";
        $this->query($query);
        $query=array("select id, xmeta from content");
        $this->query($query);
        while ($this->fetch = $this->fetch_array() ) {
            $obj=unserialize($this->fetch['xmeta']);
            if (!is_object($obj)) $obj=new stdClass();
            $query=array("update content");
            $query[]="set meta=" . fn_escape(json_encode($obj));
            $query[]="where id=" . fn_escape($this->fetch['id']);
            $database->temp->query($query);
        }
    }
}
class content_data_class {
	var $id=0;
	var $record_type;
	var $record_id=0;
    var $record_item;
	var $language_code;
	var $name;
    var $description;
    var $filetype;
	var $revised=0;
    var $meta;
	var $content;
    function __construct($language_code="") {
		$this->meta=new file_meta_class();
        $this->language_code=$language_code;
    }
}
class content_constant_class {
	var $icons=array(
    	'text/html'=>'generic.gif',
        'text/plain'=>'text.gif',
        'application/pdf'=>'pdf.gif',
        'application/x-zip-compressed'=>'compressed.gif',
        'application/vnd.ms-excel'=>'excel.gif',
        'application/msword'=>'word.gif');
    var $filetype=array("image"=>"Image","html"=>"HTML","pdf"=>"PDF","other"=>"Other","hyperlink"=>"Hyperlink",""=>"");
    var $mime_filetype=array(
	    "txt"=>"html",
	    "htm"=>"html",
	    "html"=>"html",
	    "php"=>"",
	    "css"=>"",
	    "js"=>"",
	    "json"=>"",
	    "xml"=>"other",
	    "swf"=>"other",
	    "flv"=>"other",
	    "png"=>"image",
	    "jpe"=>"image",
	    "jpeg"=>"image",
	    "jpg"=>"image",
	    "gif"=>"image",
	    "bmp"=>"image",
	    "ico"=>"image",
	    "tiff"=>"image",
	    "tif"=>"image",
	    "svg"=>"image",
	    "svgz"=>"image",
	    "zip"=>"other",
	    "rar"=>"other",
	    "exe"=>"",
	    "msi"=>"",
	    "cab"=>"",
	    "mp3"=>"other",
	    "qt"=>"other",
	    "mov"=>"other",
	    "pdf"=>"pdf",
	    "psd"=>"other",
	    "ai"=>"other",
	    "eps"=>"other",
	    "ps"=>"other",
	    "doc"=>"other",
	    "docx"=>"other",
	    "rtf"=>"other",
	    "xls"=>"other",
	    "xlsx"=>"other",
	    "ppt"=>"other",
	    "pptx"=>"other",
	    "odt"=>"other",
	    "ods"=>"other");
	function filetype($name) {
		$suffix=end(preg_split("/\./",strtolower($name)));
        if (!array_key_exists($suffix,$this->mime_filetype)) {
			return "";
		  } else {
			return $this->mime_filetype[$suffix];
		}
    }
}
/*
CREATE TABLE `content` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `record_type` varchar(45) NOT NULL DEFAULT '',
  `record_id` int(10) unsigned NOT NULL DEFAULT '0',
  `record_item` varchar(45) NOT NULL DEFAULT '',
  `language_code` char(3) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `description` mediumtext,
  `revised` bigint(20) unsigned NOT NULL DEFAULT '0',
  `filetype` varchar(45) NOT NULL DEFAULT '',
  `meta` mediumtext,
  `content` mediumblob,
  `html_content` longtext CHARACTER SET utf8,
  PRIMARY KEY (`id`),
  UNIQUE KEY `file_key` (`record_type`,`record_id`,`record_item`,`language_code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Content (10/13/2025)'
*/
?>