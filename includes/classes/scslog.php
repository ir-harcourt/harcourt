<?php
$database->scslog=new scslog_class();
class scslog_class extends database_class {
	function scs_table_version() {
		$results=array();
        $results['08/05/2022']="Initial release (Based on log 06/30/2019";
		$results['file']= __FILE__;
        return $results;
    }
	function __construct() {
    	$this->data=new scslog_data_class();
    	$this->meta=new database_meta_class();
        $this->constant=new scslog_constant_class();
        $this->fetch=array();
        $this->meta->error_abort=FALSE;
    }
    function fetch($fetch=FALSE) {
        if ($fetch) $this->fetch=$this->fetch_array();
        $this->data=new scslog_data_class();
	    $this->data->id=$this->fetch['id'];
	    $this->data->type=$this->fetch['type'];
	    $this->data->subtype=$this->fetch['subtype'];
	    $this->data->user_id=$this->fetch['user_id'];
	    $this->data->ip=$this->fetch['ip'];
	    $this->data->agent=$this->fetch['agent'];
	    $this->data->timestamp=$this->fetch['timestamp'];
	    $this->data->sku=$this->fetch['sku'];
	    if ($this->fetch['comment']) $this->data->comment=explode("\n",$this->fetch['comment']);
        for ($i=0; $i < $this->constant->free_field; $i++) {
        	$this->data->free_field[$i]=$this->fetch["free_field{$i}"];
        }
    }
    function read($id) {
	    $query="select * from scslog where id=" . fn_escape($id);
        $this->query($query);
        if ($this->meta->rows) {
        	$this->fetch(TRUE);
			$this->free_result();
		  } else {
			$this->data=new scslog_data_class();
		}
    }
    function update($action, $comment=array(), $options=array()) {
		$args=func_get_args();
		switch (TRUE) {
          case (sizeof($args) == 2):
          case ( (sizeof($args) == 3) && (is_array($args[2])) ):
          	break;
          default:  // Legacy interface (action, comment, user_id, sku)
          	$options=array();
            $options['user_id']=$args[2];
			$options['sku']=$args[3];
		}
		if (is_array($action)) {
			list($type,$subtype)=$action;
		  } else {
			list($type,$subtype)=explode(":",$action);
		}
        if (trim(!$type)) $type='Miscellaneous';
        if (trim(!$subtype)) $subtype='Miscellaneous';
        if (!is_array($comment)) $comment=array($comment);
		$fields=array();
        $fields[]="type=" . fn_escape(trim($type),"null");
        $fields[]="subtype=" . fn_escape(trim($subtype),"null");
        $fields[]="user_id=" . fn_escape((isset($options['user_id'])) ? $options['user_id'] : $_SESSION['user']->id,FALSE);
        $fields[]="ip=" . fn_escape($this->ip($_SERVER['REMOTE_ADDR'],FALSE),"null");
        $fields[]="agent=" . fn_escape($_SERVER['HTTP_USER_AGENT'],"null");
        $fields[]="timestamp=" . fn_escape(strtotime("now"));
        $fields[]="sku="  . fn_escape($options['sku'],"null");
        $fields[]="comment="  . fn_escape(substr(implode("\n",$comment),0,5000),"null");
		for ($i=0; $i < $this->constant->free_field; $i++) {
        	$fields[]="free_field{$i}=" . fn_escape($options["free_field{$i}"],"null");
        }
		$query=array("insert into scslog set");
        $query[]=implode(",\n",$fields);
		$this->query($query);
        return $this->insert_id();
    }
    function comment($id, $action, $comment, $options=array()) {
    	if ($id) {
			$this->read($id);
            if ($action == $this->data->type . ":" . $this->data->subtype) {
            	$query=array("update scslog set");
				$query[]="comment="  . fn_escape( (is_array($comment) ? implode("\n",$comment) : $comment) );
                $query[]="where id=" . fn_escape($id);
                $this->query($query);
            }
          } else {
          	$this->update($action, $comment, $options);
            return $this->insert_id();
        }
    }
    function ip($ip, $external=TRUE) {
    	switch (TRUE) {
          case (!strlen($ip)):
          	return;
          case ($external):
    		return inet_ntop(hex2bin($ip));
          default:
          	return bin2hex(inet_pton($ip));
		}
    }
    function map($options) {
    global $database;
        $this->map=new table_map_class($options);
	    $this->map->item("type",array("name"=>"Type","width"=>20));
	    $this->map->item("subtype",array("name"=>"Subtype","width"=>30));
	    $this->map->item("timestamp",array("name"=>"Date/Time","type"=>"timestamp"));
	    $this->map->item("user_id",array("name"=>"User ID","type"=>"id","width"=>12));
	    $this->map->item("user_name",array("name"=>"Name","width"=>40,"fetch"=>TRUE));
	    $this->map->item("user_email",array("name"=>"Email","width"=>60,"fetch"=>TRUE));
	    $this->map->item("user_company_name",array("name"=>"Company Name","width"=>40,"fetch"=>TRUE));
	    $this->map->item("user_address",array("name"=>"Address","width"=>40,"fetch"=>TRUE));
	    $this->map->item("user_city",array("name"=>"City","width"=>40,"fetch"=>TRUE));
	    $this->map->item("user_state",array("name"=>"State/Province","width"=>20,"fetch"=>TRUE));
	    $this->map->item("user_zip",array("name"=>"Zip/Postal Code","width"=>20,"fetch"=>TRUE));
	    $this->map->item("country_name",array("name"=>"Country","width"=>30,"fetch"=>TRUE));
	    $this->map->item("user_status",array("name"=>"User Status","width"=>30,"fetch"=>TRUE));
	    $this->map->item("comment",array("name"=>"Comment","type"=>"text","width"=>60,"fetch"=>TRUE));
	    $this->map->item("sku",array("name"=>"SKU","type"=>"uppercase","width"=>40));
	    $this->map->item("ip",array("name"=>"IP Address","type"=>"ipv4"));
	    $this->map->item("agent",array("name"=>"Browser/Agent","width"=>60));
	}
    function export_query() {
		$query_where=array();
		if ($this->map->options['user_id']) $query_where[]=new query_where("scslog.user_id","=",$this->map->options['user_id']);
        if (strlen($this->map->options['type'])) $query_where[]=new query_where("scslog.type","=",$this->map->options['type']);
        if (strlen($this->map->options['subtype'])) $query_where[]=new query_where("scslog.subtype","=",$this->map->options['subtype']);
        if ($this->map->options['from_date']) $query_where[]=new query_where($this->unixtime("scslog.timestamp"),">=",$this->map->options['from_date']);
        if ($this->map->options['thru_date']) $query_where[]=new query_where($this->unixtime("scslog.timestamp"),"<=",$this->map->options['thru_date']);
		if ($this->map->options['timestamp']) $query_where[]=new query_where("scslog.timestamp","<=",$this->map->options['timestamp']);
        $query=array();
        $query[]="select";
        $query[]="user.email as 'user_email',";
        $query[]="user.company_name as 'user_company_name',";
        $query[]="user.name as 'user_name',";
        $query[]="user.address as 'user_address',";
        $query[]="user.city as 'user_city',";
        $query[]="user.state as 'user_state',";
        $query[]="user.zip as 'user_zip',";
        $query[]="user.status as 'user_status',";
        $query[]="country.name as 'country_name',";
        $query[]="scslog.* from scslog";
        $query[]="left join user on user.id=scslog.user_id";
        $query[]="left join country on country.country=user.country_code and country.state=''";
        $query[]=$this->where($query_where);
        $query[]="order by id " . $this->map->options['descending'];
		return $query;
    }
}
class scslog_data_class {
	var $id=0;
	var $type;
    var $subtype;
	var $user_id=0;
	var $ip;
	var $agent;
    var $sku;
	var $comment=array();
    var $free_field=array();
	var $timestamp=0;
}
class scslog_constant_class {
	var $free_field=5;
}
/*
CREATE TABLE `scslog` (
  `id` bigint(12) NOT NULL AUTO_INCREMENT,
  `type` varchar(40) DEFAULT NULL,
  `subtype` varchar(40) DEFAULT NULL,
  `user_id` bigint(12) DEFAULT '0',
  `ip` varchar(32) DEFAULT NULL,
  `agent` mediumtext,
  `timestamp` bigint(10) unsigned DEFAULT '0',
  `sku` varchar(256) DEFAULT NULL,
  `comment` mediumtext,
  `free_field0` varchar(256) DEFAULT NULL,
  `free_field1` varchar(256) DEFAULT NULL,
  `free_field2` varchar(256) DEFAULT NULL,
  `free_field3` varchar(256) DEFAULT NULL,
  `free_field4` varchar(256) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `type_index` (`type`),
  KEY `date_index` (`timestamp`),
  KEY `sku_index` (`sku`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Event log (08/05/2022)'
*/
?>