<?php
$database->sfpicklist = new sfpicklist_class();
class sfpicklist_class extends database_class {
	function scs_table_version() {
		$results=array();
        $results['01/28/2021']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
	function __construct() {
    	$this->data=new sfpicklist_data_class();
        $this->constant=new sfpicklist_constant_class();
    	$this->meta=new database_meta_class();
        $this->fetch=array();
    }
    function fetch($fetch=FALSE) {
        if ($fetch) $this->fetch=$this->fetch_array();
    	$this->data=new sfpicklist_data_class();
	    $this->data->id=$this->fetch['id'];
	    $this->data->record_type=$this->fetch['record_type'];
        if (strlen($this->fetch['code'])) parse_str($this->fetch['code'], $this->data->code);
	    $this->data->name=$this->fetch['name'];
	    $this->data->update_timestamp=$this->fetch['update_timestamp'];
    }
    function read($record_type, $data) {
		$query_where=array();
        $query_where[]=new query_where("record_type","=", $record_type);
        $code=array();
        foreach ($this->constant->map as $field) {
        	$code[$field]=( (is_array($data)) ? $data[$field] : $data->$field);
        }
        $query_where[]=new query_where("code","=", http_build_query($code));
        $query=array("select * from sfpicklist");
        $query[]=$this->where($query_where);
        $this->query($query);
        if ($this->meta->rows) {
        	$this->fetch(TRUE);
		  } else {
          	$this->data=new sfpicklist_data_class();
		}
        $this->free_result();
    }
    function translate($record_type, $data, $value) {
    	$this->constant->map($record_type);
        $this->read($record_type, $data);
		return ( ($this->meta->rows) ? $this->data->name : $value );
    }
    function update($update=FALSE) {
		$fields=array();
	    $fields[]="id=" . fn_escape($this->data->id,FALSE);
	    $fields[]="record_type=" . fn_escape($this->data->record_type);
	    $fields[]="code=" . fn_escape(http_build_query($this->data->code));
	    $fields[]="name=" . fn_escape($this->data->name);
	    $fields[]="update_timestamp=" . fn_escape($this->data->update_timestamp);
        $query=array();
        if ($update) {
	        $query[]="update sfpicklist set";
	        $query[]=implode(",\n",$fields);
            $query[]="where id=" . fn_escape($this->data->id,FALSE);
          } else {
	        $query[]="insert into sfpicklist set";
	        $query[]=implode(",\n",$fields);
		}
		$this->query($query);
        if ((!$update) && (!$this->meta->error)) $this->data->id=$this->insert_id();
    }
    function map($options) {
    global $database;
        $this->map=new table_map_class($options);
        $this->constant->map($options['record_type']);
        $this->map->update_timestamp=strtotime("now");
	    $this->map->item("record_type",array("type"=>"uppercase","required"=>TRUE,"width"=>12));
        foreach ($this->constant->map as $field) {
	    	$this->map->item($field,array("type"=>"uppercase","required"=>TRUE,"width"=>30));
        }
	    $this->map->item("name",array("name"=>"Name","type"=>"alphanumeric","required"=>TRUE,"width"=>60));
    }
    function export_query() {
		$query=array("select * from sfpicklist");
        $query[]="where record_type=" . fn_escape($this->map->options['record_type']);
        $query[]="order by name";
        return $query;
    }
    function export_template() {
    	$results=array();
		$results['record_type']=$this->map->options['record_type'];
        return $results;
    }
    function export_data() {
		$results=array();
        foreach ($this->constant->map as $field) {
        	$this->data->$field=$this->data->code[$field];
        }
		foreach ($this->map->fields as $field => $map) {
			$results[$field]=$this->data->$field;
        }
		return $results;
    }
    function map_multiple($options,$code) {
    global $database, $forms, $menu;
    	$options['record_type']=$code;
        $this->map($options);
    }
    function worksheet_name($record_type) {
    global $menu;
		return (isset($menu->configure->module->pricing->record_type->$record_type) ? $this->map->record_type->name : $record_type);
    }
    function import_update($data) {
		$this->read($this->map->options['record_type'], $data);
		$this->data->record_type=$this->map->options['record_type'];
        foreach ($this->constant->map as $field) {
        	$this->data->code[$field]=$data[$field];
            if (!strlen($data[$field])) $this->map->error[$field]="Cannot be blank";
        }
        $this->data->name=$data['name'];
        if (!strlen($data['name'])) $this->map->error['name']="Cannot be blank";
        $this->data->update_timestamp=$this->map->update_timestamp;
        switch (TRUE) {
          case (!$this->map->update):
          case (sizeof($this->map->error)):
          	return;
          default:
        	$this->update($this->meta->rows);
			if ($this->meta->error) $this->map->error['mysql']=$this->meta->error;
		}
    }
    function import_delete() {
    global $database;
		$query_where=array();
        $query_where[]=new query_where("record_type","=",$this->map->options['record_type']);
        $query_where[]=new query_where("update_timestamp","<>",$this->map->update_timestamp);
	    $query=array("delete from sfpicklist");
        $query[]=$this->where($query_where);
		$this->query($query);
    }
}
class sfpicklist_data_class {
	var $id=0;
    var $record_type;
    var $code=array();
    var $name;
    var $update_timestamp=0;
}
class sfpicklist_constant_class {
	var $record_type=array("country"=>"Country","state"=>"State");
    var $map=array();
	function map($record_type) {
    	$this->map=array();
        switch ($record_type) {
          case "country":
          	$this->map[]="country_code";
            break;
          case "state":
          	$this->map[]="country_code";
          	$this->map[]="state";
            break;
        }
    }
}
/*
CREATE TABLE `sfpicklist` (
  `id` bigint(10) NOT NULL AUTO_INCREMENT,
  `record_type` varchar(20) NOT NULL DEFAULT '',
  `code` varchar(128) NOT NULL DEFAULT '',
  `name` varchar(60) NOT NULL DEFAULT '',
  `update_timestamp` bigint(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code_key` (`record_type`,`code`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Salesforce Picklist (01/28/2021)'
*/
?>