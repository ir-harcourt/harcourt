<?php
$database->country=new country_class();
class country_class extends database_class {
	var $data;
    var $meta;
    var $constant;
    var $fetch=array();
    function scs_table_version() {
    	$results=array();
        $results['08/05/2024']="Add hotfix";
        $results['12/15/2022']="Correct find_state logic error";
        $results['04/25/2021']="Add find_state";
        $results['07/15/2020']="Add find_country";
        $results['11/19/2019']="Add free_field, alias";
        $results['07/07/2014']="Enhance select function";
        $results['04/18/2013']="Address spun off into functions";
        $results['03/27/2013']="Add address_review()";
        $results['01/14/2013']="Use div_show()";
        $results['12/25/2012']="Add address_verify";
        $results['04/12/2012']="Initial release";
		$results['file']= __FILE__;
        return $results;
    }
	function __construct() {
		$this->data=new country_data_class();
    	$this->meta=new database_meta_class();
    	$this->constant=new country_constant_class();
    }
    function fetch($fetch=FALSE) {
        if ($fetch) $this->fetch=$this->fetch_array();
    	$this->data=new country_data_class();
        $this->data->country=$this->fetch['country'];
        $this->data->state=$this->fetch['state'];
        $this->data->name=$this->fetch['name'];
        if (substr($this->fetch['alias'],0,1) == "^") $this->data->alias=explode("^",substr($this->fetch['alias'],1,-1));
        $this->data->free_field=fn_urlencode($this->fetch['free_field'],FALSE);
        $this->data->active=$this->fetch['active'];
    }
    function read($country,$state="") {
        $query=array("select * from country");
        $query[]="where country=" . fn_escape($country);
        $query[]="and state=" . fn_escape($state);
        $this->query($query);
        if ($this->meta->rows) {
        	$this->fetch(TRUE);
			$this->free_result();
		  } else {
			$this->data=new country_data_class();
		}
    }
    function update($update=FALSE) {
		$fields=array();
        $fields[]="country=" . fn_escape($this->data->country);
        $fields[]="state=" . fn_escape($this->data->state);
        $fields[]="name=" . fn_escape($this->data->name);
        $fields[]="alias=" . fn_escape("^" . implode("^",$this->data->alias) . "^");
        $fields[]="free_field=" . fn_escape(fn_urlencode($this->data->free_field,TRUE));
        $fields[]="active=" . fn_escape($this->data->active,FALSE);
        $fields[]="update_timestamp=" . fn_escape($this->data->update_timestamp,FALSE);
        $query=array();
    	if ($update) {
          	$query[]="update country set";
            $query[]=implode(",\n",$fields);
            $query[]="where country=" . fn_escape($this->data->country);
            $query[]=" and state=" . fn_escape($this->data->state);
		  } else {
          	$query[]="insert into country set";
            $query[]=implode(",\n",$fields);
		}
		$this->query($query);
	}
    function select($country,$compare,$select_options=array(),$forms_options=array()) {
    global $forms;
		if (!is_array($select_options)) $select_options=array("field"=>$select_options);
		switch (TRUE) {
		  case (isset($select_options['field'])):
			$field=$select_options['field'];
            break;
		  case ($country):
        	$field="state_code";
			break;
          default:
        	$field="country_code";
        }
		switch (TRUE) {
		  case (isset($select_options['blank'])):
			$blank=$select_options['blank'];
            break;
		  case ($country):
        	$blank=TRUE;
			break;
          default:
          	$blank=FALSE;
        }
		$results=array();
        $query=array("select");
        if ($country) {
        	$query[]= "country.state as 'code',";
            $query[]="country.* from country";
	        $query[]="where country.country=" . fn_escape($country);
	        $query[]="and country.state <> ''";
	        $query[]="order by country.name";
          } else {
	        $query[]="(case country ";
	        $query[]="when 'US' then 1";
	        $query[]="when 'CA' then 2";
	        $query[]="else 3 end) as sort_key,";
	        $query[]="country.country as 'code',";
	        $query[]="country.* from country";
	        $query[]="where country.state=''";
	        $query[]="and country.active='1'";
	        $query[]="order by sort_key,country.name";
		}
	    $this->query($query);
	    while ($this->fetch = $this->fetch_array()) {
	        $this->fetch();
            $results[$this->fetch['code']]=$this->data->name;
	    }
	    $this->free_result();
        return $forms->select($field,$results,$compare,$blank,$forms_options);
    }
    function find_country($code) {
		$this->data=new country_data_class();
	    if (!strlen($code)) return;
	    $query=array("select");
	    $query[]="case";
	    $query[]="  when country=" . fn_escape($code) . " then 1";
	    $query[]="  when name=" . fn_escape($code) . " then 2";
	    $query[]="  else 3";
	    $query[]="end as sort_key,";
	    $query[]="country.* from country";
	    $query[]="where state=''";
	    $query[]="and (";
	    $query[]="country=" . fn_escape($code);
	    $query[]="or name=" . fn_escape($code);
	    $query[]="or alias like " . fn_escape("%" . $code . "%");
	    $query[]=")";
	    $query[]="order by sort_key";
	    $query[]="limit 1";
		$this->query($query);
	    $this->fetch(TRUE);
	    return $this->data->country;
    }
    function find_state($country, $code) {
		$this->data=new country_data_class();
        switch (TRUE) {
          case (!strlen($code)):
          	return;
          case (!in_array($country,array("US","CA"))):
          	return $code;
		}
	    $query=array("select");
	    $query[]="case";
	    $query[]="  when state=" . fn_escape($code) . " then 1";
	    $query[]="  when name=" . fn_escape($code) . " then 2";
	    $query[]="  else 3";
	    $query[]="end as sort_key,";
	    $query[]="country.* from country";
        $query[]="where country=" . fn_escape($country);
	    $query[]="and state <> ''";
        $query[]="having sort_key <> 3";
	    $query[]="order by sort_key";
	    $query[]="limit 1";
		$this->query($query);
	    $this->fetch(TRUE);
	    return $this->data->state;
    }
    function map($options) {
    global $database;
        if (!array_key_exists("country",$options)) $options['country']="";
        $this->map=new table_map_class($options);
        $this->map->update_timestamp=strtotime("now");
	    $this->map->item("country",array("name"=>"Country","comment"=>"ISO Alpha-2","type"=>"uppercase","required"=>TRUE,"width"=>10));
        if (strlen($options['country'])) $this->map->item("state",array("name"=>"State Name/ISO Alpha-2","type"=>"uppercase","required"=>TRUE,"width"=>30));
	    $this->map->item("name",array("name"=>"Name","type"=>"text","required"=>TRUE,"width"=>30));
	    $this->map->item("_alias",array("name"=>"Alias","type"=>"lowercase","comment"=>"^ delimited","required"=>TRUE,"width"=>60));
        foreach ($this->constant->free_field as $code => $obj) {
			$this->map->item($code,array("name"=>$obj->name,"type"=>$obj->type,"required"=>TRUE,"width"=>30));
        }
		$this->map->item("active",array("name"=>"Active","type"=>"boolean","required"=>TRUE,"width"=>10));
	}
    function export_query() {
    	$query_where=array();
        if (strlen($this->map->options['country'])) {
        	$query_where[]=new query_where("country","=",$this->map->options['country']);
            $query_where[]=new query_where("state","<>","");
		  } else {
            $query_where[]=new query_where("state","=","");
        }
    	$query=array("select * from country");
        $query[]=$this->where($query_where);
		$query[]="order by name";
        return $query;
    }
    function export_data() {
    	$this->data->_alias=implode("^",$this->data->alias);
		foreach ($this->constant->free_field as $code => $name) {
        	$this->data->$code=$this->data->free_field[$code];
        }
    }
    function import_update($data) {
		$this->read($data['country'],$data['state']);
        $this->data->country=$data['country'];
        $this->data->state=$data['state'];
        $this->data->name=$data['name'];
        $this->data->alias=array();
        if (strlen($data["_alias"])) {
	        $text=explode("^",$data["_alias"]);
	        foreach ($text as $alias) {
	            $alias=trim(strtolower($alias));
	            if (!in_array($alias,$this->data->alias)) $this->data->alias[]=$alias;
	        }
		}
        $this->data->free_field=array();
        foreach ($this->constant->free_field as $code => $name) {
        	$this->data->free_field[$code]=$data[$code];
        }
        $this->data->active=$data['active'];
        $this->data->update_timestamp=$this->map->update_timestamp;
        if (!strlen($this->data->country)) $this->meta->error['country']="Cannot be blank";
		if ( (strlen($this->map->options['country'])) && (!strlen($this->data->state)) ) $this->meta->error['state']="Cannot be blank";
        if (!strlen($this->data->name)) $this->meta->error['name']="Cannot be blank";
		if ( ($this->map->update) && (!sizeof($this->map->error)) ) {
        	$this->update($this->meta->rows);
            if ($this->meta->error) $this->map->error['mysql']=$this->meta->error;
		}
    }
    function import_delete() {
    global $database;
		$query_where=array();
        if (strlen($this->map->options['country'])) {
        	$query_where[]=new query_where("country","=",$this->map->options['country']);
            $query_where[]=new query_where("state","<>","");
		  } else {
            $query_where[]=new query_where("state","=","");
        }
        $query_where[]=new query_where("update_timestamp","<>",$this->map->update_timestamp);
	    $query=array("delete from country");
        $query[]=$this->where($query_where);
		$this->query($query);
    }
    function scs_hotfix($execute=FALSE) {
        $this->hotfix=new mysql_hotfix_class("country", "Country & state codes");
        $this->hotfix->version("11/19/2019", "Add free_field, alias");
        $this->hotfix->version("04/12/2012", "Initial release");
        $this->hotfix->process($execute);
    }
    function scs_hotfix_20191119($meta) {
        global $database;
        $this->hotfix->trace[]=__FUNCTION__;
        $fields=array();
        $fields[]="ADD COLUMN `free_field` MEDIUMTEXT NULL AFTER `name`";
        $fields[]="ADD COLUMN `alias` MEDIUMTEXT NULL AFTER `name`";
        $fields[]="CHANGE COLUMN `update_timestamp` `update_timestamp` BIGINT(10) UNSIGNED NULL DEFAULT '0'";
        $query=array("ALTER TABLE `country`");
        $query[]=implode(",\n", $fields);
        $this->query($query);
        $meta->count=$this->affected_rows();
    }
}
class country_data_class {
	var $country;
    var $state;
	var $name;
    var $alias=array();
    var $free_field=array();
    var $active=1;
    var $update_timestamp=0;
    function __construct() {
    	$this->update_timestamp=strtotime("now");
    }
}
class country_constant_class {
	var $state=array("","CA","US");
    var $free_field=array();
    function free_field($field,$name,$options=array()) {
    	$this->free_field[$field]=new stdClass();
        $this->free_field[$field]->name=$name;
        if (!array_key_exists("type",$options)) $options['type']="text";
        foreach ($options as $key=>$value) {
        	$this->free_field[$field]->{$key}=$value;
        }
    }
}

/*
ALTER TABLE `country`
ADD COLUMN `free_field` MEDIUMTEXT NULL AFTER `name`,
ADD COLUMN `alias` MEDIUMTEXT NULL AFTER `name`,
CHANGE COLUMN `update_timestamp` `update_timestamp` BIGINT(10) UNSIGNED NULL DEFAULT '0',
COMMENT = 'Country & state codes (11/19/2019)' ;

CREATE TABLE `country` (
  `country` char(2) NOT NULL DEFAULT '',
  `state` varchar(30) NOT NULL DEFAULT '',
  `name` varchar(60) DEFAULT NULL,
  `alias` mediumtext,
  `free_field` mediumtext,
  `active` int(1) unsigned DEFAULT '1',
  `update_timestamp` bigint(10) unsigned DEFAULT '0',
  PRIMARY KEY (`country`,`state`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Country & state codes (11/22/2019)'
*/
?>