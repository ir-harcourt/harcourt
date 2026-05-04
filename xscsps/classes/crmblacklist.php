<?php
$database->crmblacklist = new crmblacklist_class();
class crmblacklist_class extends database_class {
	function scs_table_version() {
		$results=array();
        $results['07/24/2023']="Add export_template";
        $results['11/11/2020']="Add track";
        $results['10/23/2019']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
	function __construct() {
    	$this->data=new crmblacklist_data_class();
        $this->constant=new crmblacklist_constant_class();
    	$this->meta=new database_meta_class();
        $this->fetch=array();
    }
    function fetch($fetch=FALSE) {
        if ($fetch) $this->fetch=$this->fetch_array();
    	$this->data=new crmblacklist_data_class();
	    $this->data->id=$this->fetch['id'];
	    $this->data->account=$this->fetch['account'];
	    $this->data->domain=$this->fetch['domain'];
	    $this->data->track=$this->fetch['track'];
    }
    function read($id) {
        $query=array("select * from crmblacklist");
        $query[]="where " . (is_array($id) ? "account=" . fn_escape($id[0]) . " and domain=" . fn_escape($id[1]) : "id=" . fn_escape($id));
        $this->query($query);
        if ($this->meta->rows) {
        	$this->fetch(TRUE);
		  } else {
			$this->data=new crmblacklist_data_class();
		}
        $this->free_result();
    }
    function update($update=FALSE) {
		$fields=array();
	    $fields[]="id=" . fn_escape($this->data->id,FALSE);
	    $fields[]="account=" . fn_escape($this->data->account);
	    $fields[]="domain=" . fn_escape($this->data->domain);
	    $fields[]="track=" . fn_escape($this->data->track,FALSE);
	    $fields[]="update_timestamp=" . fn_escape($this->data->update_timestamp);
        $query=array();
    	if ($update) {
          	$query[]="update crmblacklist set";
            $query[]=implode(",\n",$fields);
            $query[]="where id=" . fn_escape($this->data->id);
		  } else {
          	$query[]="insert into crmblacklist set";
            $query[]=implode(",\n",$fields);
		}
		$this->query($query);
        if ( (!$this->data->id) && (!$this->meta->error) ) $this->data->id = $this->insert_id();
    }
    function delete($id) {
	    $query=array("delete from crmblacklist where id=" . fn_escape($id));
		$this->query($query);
    }
    function verify() {
    	$email=new email_class( (strlen($this->data->account) ? $this->data->account : "a") . "@" . $this->data->domain,TRUE);
		return $email->error;
    }
    function email() {
    	return $this->data->account . "@" . $this->data->domain;
    }
    function track($email) {
		switch (TRUE) {
          case (!strlen($email)):
		  case (preg_match("/" . preg_quote("@partcommunity.com") . "/i",$email)):
			return FALSE;
		}
		list($account,$domain)=explode("@",$email);
        $query_where=array();
        $query_where[]=new query_where("account","in",array("",$account));
        $query_where[]=new query_where("domain","=",$domain);
		$query=array("select * from crmblacklist");
        $query[]=$this->where($query_where);
        $query[]="order by account desc";
        $query[]="limit 1";
        $this->query($query);
        if (!$this->meta->rows) return TRUE;
		$this->fetch(TRUE);
        return $this->data->track;
    }
    function map($options) {
    global $database;
        $this->map=new table_map_class($options);
        $this->map->update_timestamp=strtotime("now");
	    $this->map->item("account",array("name"=>"Account","type"=>"lowercase","required"=>TRUE,"width"=>60));
	    $this->map->item("domain",array("name"=>"Domain","type"=>"lowercase","required"=>TRUE,"width"=>60));
	    $this->map->item("track",array("name"=>"Track","type"=>"boolean","required"=>TRUE));
    }
    function export_query() {
		$query=array("select * from crmblacklist");
        $query[]="order by domain,account";
        return $query;
    }
    function export_template() {
    	$this->data=new crmblacklist_data_class();
        return array($this->data);
	}
    function import_update($data) {
		$this->read(array($data['account'],$data['domain']));
        $this->data->account=$data['account'];
        $this->data->domain=$data['domain'];
        $this->data->track=$data['track'];
        $this->data->update_timestamp=$this->map->update_timestamp;
        $error=$this->verify();
        if ($error) $this->map->error['domain']=$error;
        if ( ($this->map->update) && (!sizeof($this->map->error)) ) $this->update($this->meta->rows);
    }
    function import_delete() {
    global $database;
		$query_where=array();
        $query_where[]=new query_where("update_timestamp","<>",$this->map->update_timestamp);
	    $query=array("delete from crmblacklist");
        $query[]=$this->where($query_where);
		$this->query($query);
    }
}
class crmblacklist_data_class {
	var $id=0;
    var $account;
    var $domain;
    var $track=1;
    var $condition=array();
    var $update_timestamp=0;
    function __construct() {
    global $database;
    	$this->update_timestamp=strtotime("now");
    }
}
class crmblacklist_constant_class {
}
/*
ALTER TABLE `crmblacklist`
ADD COLUMN `track` INT(1) NOT NULL DEFAULT '1' AFTER `domain`,
COMMENT = 'CRM Blacklist (11/11/2020)' ;

CREATE TABLE `crmblacklist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account` varchar(80) NOT NULL DEFAULT '',
  `domain` varchar(80) NOT NULL DEFAULT '',
  `track` int(1) NOT NULL DEFAULT '1',
  `update_timestamp` bigint(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `address_key` (`account`,`domain`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='CRM Blacklist (11/11/2020)'
*/
?>