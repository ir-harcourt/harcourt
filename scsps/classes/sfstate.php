<?php
$database->sfstate = new sfstate_class();
class sfstate_class extends database_class {
	function scs_table_version() {
		$results=array();
        $results['12/05/2019']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
	function __construct() {
    	$this->data=new sfstate_data_class();
        $this->constant=new sfstate_constant_class();
    	$this->meta=new database_meta_class();
        $this->fetch=array();
    }
    function fetch($fetch=FALSE) {
        if ($fetch) $this->fetch=$this->fetch_array();
    	$this->data=new sfstate_data_class();
	    $this->data->country=$this->fetch['country'];
	    $this->data->state=$this->fetch['state'];
	    $this->data->name=$this->fetch['name'];
	    $this->data->update_timestamp=$this->fetch['update_timestamp'];
    }
    function read($country,$state) {
        $query=array("select * from sfstate");
        $query[]="where country=" . fn_escape($country) . " and state=" . fn_escape($state);
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
	    $fields[]="country=" . fn_escape($this->data->country);
	    $fields[]="state=" . fn_escape($this->data->state);
	    $fields[]="name=" . fn_escape($this->data->name);
	    $fields[]="update_timestamp=" . fn_escape($this->data->update_timestamp);
        $query=array();
    	if ($update) {
          	$query[]="update sfstate set";
            $query[]=implode(",\n",$fields);
            $query[]="where id=" . fn_escape($this->data->id);
		  } else {
          	$query[]="insert into sfstate set";
            $query[]=implode(",\n",$fields);
		}
		$this->query($query);
        if ( (!$this->data->id) && (!$this->meta->error) ) $this->data->id = $this->insert_id();
    }
    function map($options) {
    global $database;
        $this->map=new table_map_class($options);
        $this->map->update_timestamp=strtotime("now");
	    $this->map->item("country",array("name"=>"Country","type"=>"uppercase","required"=>TRUE,"width"=>12));
	    $this->map->item("state",array("name"=>"State","type"=>"uppercase","required"=>TRUE,"width"=>12));
	    $this->map->item("name",array("name"=>"Name","type"=>"alphanumeric","required"=>TRUE,"width"=>60));
    }
    function export_query() {
		$query=array("select * from sfstate");
        $query[]="order by country,name";
        return $query;
    }
    function import_update($data) {
    global $database;
		$this->read($data['country'],$data['state']);
        $this->data->country=$data['country'];
        $this->data->state=$data['state'];
        $this->data->name=$data['name'];
        $this->data->update_timestamp=$this->map->update_timestamp;
        $database->country->read($this->data->country,"");
        if (!$database->country->meta->rows) $this->map->error['country']="Invalid country code: " . $this->data->country;
        if (!strlen($this->data->state)) $this->map->error['state']="Cannot be blank";
        if (!strlen($this->data->name)) $this->map->error['name']="Cannot be blank";
        if ( ($this->map->update) && (!sizeof($this->map->error)) ) $this->update($this->meta->rows);
    }
    function import_delete() {
    global $database;
		$query_where=array();
        $query_where[]=new query_where("update_timestamp","<>",$this->map->update_timestamp);
	    $query=array("delete from sfstate");
        $query[]=$this->where($query_where);
		$this->query($query);
    }
}
class sfstate_data_class {
    var $country;
    var $state;
    var $name;
    var $update_timestamp=0;
    function __construct() {
    global $database;
    	$this->update_timestamp=strtotime("now");
    }
}
class sfstate_constant_class {
}
/*
CREATE TABLE `sfstate` (
  `country` char(2) NOT NULL DEFAULT '',
  `state` varchar(12) NOT NULL DEFAULT '',
  `name` varchar(60) NOT NULL DEFAULT '',
  `update_timestamp` bigint(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`country`,`state`),
  UNIQUE KEY `name_key` (`country`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Salesforce State Codes (12/05/2019)'
*/
?>