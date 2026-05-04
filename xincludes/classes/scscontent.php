<?php
$database->scscontent=new scscontent_class();
class scscontent_class extends database_class {
	function scs_table_version() {
		$results=array();
        $results['08/17/2021']="Initial release";
        return $results;
    }
	function __construct() {
    	$this->data=new scscontent_data_class();
    	$this->meta=new database_meta_class();
        $this->fetch=array();
        $this->constant=new scscontent_constant_class();
    }
    function fetch($fetch=FALSE) {
        if ($fetch) $this->fetch=$this->fetch_array();
		$this->data=new scscontent_data_class();
	    $this->data->id=$this->fetch['id'];
	    $this->data->record_type=$this->fetch['record_type'];
	    $this->data->record_id=$this->fetch['record_id'];
	    $this->data->record_item=$this->fetch['record_item'];
	    $this->data->name=$this->fetch['name'];
	    $this->data->description=$this->fetch['description'];
	    $this->data->url=$this->fetch['url'];
	    if (strlen($this->fetch['meta'])) $this->data->meta=(array) json_decode($this->fetch['meta']);
	    $this->data->html_content=$this->fetch['html_content'];
	    $this->data->binary_content=$this->fetch['binary_content'];
	    $this->data->revised=$this->fetch['revised'];
	    $this->data->user_id=$this->fetch['user_id'];
    }
    function read($id,$field="id") {
        $query=array("select * from scscontent");
        $query[]="where $field=" . fn_escape($id);
        $this->query($query);
        if ($this->meta->rows) {
        	$this->fetch(TRUE);
			$this->free_result();
		  } else {
			$this->data=new scscontent_data_class();
		}
    }
    function update($update=FALSE) {
		if (!$this->data->revised) $this->data->revised=strtotime("now");
		if (!$this->data->user_id) $this->data->revised=$_SESSION['user']->id;
		$fields=array();
	    $fields[]="id=" . fn_escape($this->data->id,FALSE);
	    $fields[]="record_type=" . fn_escape($this->data->record_type);
	    $fields[]="record_id=" . fn_escape($this->data->record_id,FALSE);
	    $fields[]="record_item=" . fn_escape($this->data->record_item,"null");
	    $fields[]="name=" . fn_escape($this->data->name,"null");
	    $fields[]="description=" . fn_escape($this->data->description,"null");
	    $fields[]="url=" . fn_escape($this->data->url,"null");
	    $fields[]="meta=" . fn_escape(json_encode($this->data->meta),"null");
	    $fields[]="html_content=" . fn_escape($this->data->html_content,"null");
	    $fields[]="binary_content=" . fn_escape($this->data->binary_content,"null");
	    $fields[]="revised=" . fn_escape($this->data->revised,FALSE);
	    $fields[]="user_id=" . fn_escape($this->data->user_id,FALSE);
        $query=array();
    	if ($update) {
        	$query[]="update scscontent set";
          	$query[]=implode(",\n",$fields);
            $query[]=" where id=" . fn_escape($this->data->id);
		  } else {
        	$query[]="insert into scscontent set";
          	$query[]=implode(",\n",$fields);
        }
		$this->query($query);
		if ((!$update) && (!$this->meta->error)) $this->data->id = $this->insert_id();
    }
    function delete($id) {
	    $query="delete from scscontent where id=" . fn_escape($id);
		$this->query($query);
    }
    function purge($record_type, $record_id, $record_item="") {
    global $database;
		$query=array("select id, url");
        $query[]="from scscontent";
        $query[]="where record_type=" . fn_escape($record_type);
        $query[]=" and record_id=" . fn_escape($record_id);
    	if ($record_item) $query[]=" and record_item=" . fn_escape($record_item);
        $database->temp->query($query);
        while ($database->temp->fetch = $database->temp->fetch_array()) {
			if (strlen($database->temp->fetch['url'])) {
        		$url=$_SERVER['DOCUMENT_ROOT'] . $database->temp->fetch['url'];
                if (file_exists($url)) @unlink($url);
			}
    		$this->delete($database->temp->fetch['id']);
        }
    }
}
class scscontent_data_class {
	var $id=0;
    var $record_type;
    var $record_id=0;
    var $record_item;
	var $name;
	var $description;
    var $url;
    var $meta=array();
    var $html_content;
    var $binary_content;
    var $revised=0;
    var $user_id=0;
    function __construct() {

    }
}
class scscontent_constant_class {
}
/*
CREATE TABLE `scscontent` (
  `id` bigint(11) NOT NULL AUTO_INCREMENT,
  `record_type` varchar(45) NOT NULL DEFAULT '',
  `record_id` bigint(11) unsigned NOT NULL DEFAULT '0',
  `record_item` varchar(45) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `description` mediumtext,
  `url` mediumtext,
  `meta` mediumtext,
  `html_content` longtext,
  `binary_content` longblob,
  `revised` bigint(10) unsigned NOT NULL DEFAULT '0',
  `user_id` bigint(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `file_key` (`record_type`,`record_id`,`record_item`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='SCS Content (08/17/2021)'
*/
?>