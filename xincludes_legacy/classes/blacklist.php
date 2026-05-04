<?php
$database->blacklist=new blacklist_class();
class blacklist_class extends database_class {
	function scs_table_version() {
		$results=array();
        $results['06/05/2014']="Initial release";
		$results['file']= __FILE__;
        return $results;
    }
	function __construct() {
    	$this->data=new blacklist_data_class();
    	$this->meta=new database_meta_class();
        $this->constant=new blacklist_constant_class();
        $this->fetch=array();
        $this->meta->error_abort=FALSE;
    }
    function fetch($fetch=FALSE) {
        if ($fetch) $this->fetch=$this->fetch_array();
	    $this->data=new blacklist_data_class();
	    $this->data->id=$this->fetch['id'];
	    $this->data->ip_address=$this->fetch['ip_address'];
	    $this->data->type=$this->fetch['type'];
	    $this->data->comment=$this->fetch['comment'];
	    $this->data->user_id=$this->fetch['user_id'];
	    $this->data->date_time=$this->fetch['date_time'];
    }
    function read($id,$field="id") {
        $query =
            "select * from blacklist " .
            "where $field=" . fn_escape($id);
        $this->query($query);
        if ($this->meta->rows) {
        	$this->fetch(TRUE);
			$this->free_result();
		  } else {
			$this->data=new blacklist_data_class();
		}
    }
    function update($update=FALSE) {
    	if ($update) {
          	$query_action="update blacklist set ";
            $query_where=" where id=" . fn_escape($this->data->id);
		  } else {
        	$query_action="insert into blacklist set ";
        }
        $query =
        	$query_action .
	        "id=" . fn_escape($this->data->id,FALSE) . "," .
			"ip_address=" . fn_escape($this->data->ip_address,FALSE) . "," .
			"type=" . fn_escape($this->data->type) . "," .
			"comment=" . fn_escape($this->data->comment) . "," .
			"user_id=" . fn_escape($this->data->user_id,FALSE) . "," .
			"date_time=" . fn_escape($this->data->date_time,FALSE) . " " .
			$query_where;
		$this->query($query);
        if ((!$update) && (!$this->meta->error)) $this->data->id=$this->insert_id();
    }
    function delete($id) {
	    $query =
	        "delete from blacklist where " .
	        "id=" . fn_escape($id);
		$this->query($query);
    }
    function blacklist_script_injection() {
    global $database;
		$ip_address=ip2long($_SERVER['REMOTE_ADDR']);
		$this->read($ip_address,"ip_address");
        if (!$this->meta->rows) {
	        $this->data->ip_address=$ip_address;
	        $this->data->type="Script Injection";
	        $this->data->comment=$_SERVER['PHP_SELF'];
	        $this->data->user_id=$_SESSION['user']->id;
	        $this->data->date_time=strtotime("now");
	        $this->update(FALSE);
	        if (method_exists($database->log,"ipc")) $database->log->ipc("Blacklist IP:" . $this->data->type,$this->data->comment,$_SESSION['user']->id);
		}
        if (method_exists($database->user,"blacklist")) $database->user->blacklist($this->data->type);
    }
}
class blacklist_data_class {
	var $id=0;
	var $ip_address=0;
	var $type;
	var $comment;
	var $user_id=0;
	var $date_time=0;
}
class blacklist_constant_class {
	var $type=array("White List","Script Injection","Competitor","Foreign","Other");
}
/*
CREATE TABLE `blacklist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` bigint(14) unsigned NOT NULL DEFAULT '0',
  `type` varchar(40) NOT NULL DEFAULT '',
  `comment` varchar(255) NOT NULL DEFAULT '',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0',
  `date_time` bigint(14) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip_key` (`ip_address`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Whitelist / Blacklist (06/05/2014)'
*/
?>