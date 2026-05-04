<?php
$database->log=new log_class();
class log_class extends database_class {
	function scs_table_version() {
		$results=array();
        $results['04/01/2025']="Add hotfix capability";
        $results['02/25/2024']="Improve comment function";
        $results['03/30/2023']="Improve export query";
        $results['06/30/2019']="Add comment function";
        $results['03/21/2015']="Allow update: action to be an array";
        $results['05/31/2013']="Initial release";
		$results['file']= __FILE__;
        return $results;
    }
	function __construct() {
    	$this->data=new log_data_class();
    	$this->meta=new database_meta_class();
        $this->constant=new log_constant_class();
        $this->fetch=array();
        $this->meta->error_abort=FALSE;
    }
    function fetch($fetch=FALSE) {
        if ($fetch) $this->fetch=$this->fetch_array();
        $this->data=new log_data_class();
	    $this->data->id=$this->fetch['id'];
	    $this->data->type=$this->fetch['type'];
	    $this->data->subtype=$this->fetch['subtype'];
	    if ($this->fetch['comment']) $this->data->comment=explode("\n",$this->fetch['comment']);
	    $this->data->sku=$this->fetch['sku'];
	    $this->data->user_id=$this->fetch['user_id'];
	    $this->data->ip_address=$this->fetch['ip_address'];
	    $this->data->agent=$this->fetch['agent'];
	    $this->data->date_time=$this->fetch['date_time'];
    }
    function read($id) {
	    $query="select * from log where id=" . fn_escape($id);
        $this->query($query);
        if ($this->meta->rows) {
        	$this->fetch(TRUE);
			$this->free_result();
		  } else {
			$this->data=new log_data_class();
		}
    }
    function update($action,$text=array(),$user_id=0,$sku="") {
		if (is_array($action)) {
			list($type,$subtype)=$action;
		  } else {
			list($type,$subtype)=explode(":",$action);
		}
        switch (TRUE) {
          case (is_array($text)):
			$comment=$text;
          	break;
          case ($text):
        	$comment[]=$text;
            break;
          default:
          	$comment=array();
		}
        if (trim(!$type)) $type='Miscellaneous';
        if (trim(!$subtype)) $subtype='Miscellaneous';
        if (!$user_id) $user_id=$_SESSION['user']->id;
		$fields=array();
        $fields[]="type=" . fn_escape(trim($type));
        $fields[]="subtype=" . fn_escape(trim($subtype));
        $fields[]="sku="  . fn_escape($sku);
        $fields[]="comment="  . fn_escape(substr(implode("\n",$comment),0,5000));
        $fields[]="user_id=" . fn_escape($user_id,FALSE);
        $fields[]="ip_address=" . fn_escape(ip2long($_SERVER['REMOTE_ADDR']),FALSE);
        $fields[]="agent=" . fn_escape($_SERVER['HTTP_USER_AGENT']);
        $fields[]="date_time=" . fn_escape(strtotime("now"));
		$query=array("insert into log set");
        $query[]=implode(",\n",$fields);
		$this->query($query);
        return $this->insert_id();
    }
    function ipc($type, $comment, $user_id=0) {
		$this->update($type,$comment,$user_id);
    }
    function comment($id, $type, $comment ,$user_id=0, $sku="") {
        if (is_array($type)) $type[]=implode(":");
        if ($id) $this->read($id);
        if ( ($id) && ($this->meta->rows) && ($type == $this->data->type . ":" . $this->data->subtype) ) {
            $query=array("update log set");
            $query[]="comment="  . fn_escape( (is_array($comment) ? implode("\n",$comment) : $comment) );
            $query[]="where id=" . fn_escape($id);
            $this->query($query);
            return $id;
          } else {
            $this->update($type, $comment, $user_id, $sku);
            return $this->insert_id();
        }
    }
    function map($options) {
        global $database;
        $this->map=new table_map_class($options);
	    $this->map->item("type",array("name"=>"Type","width"=>20));
	    $this->map->item("subtype",array("name"=>"Subtype","width"=>30));
	    $this->map->item("date_time",array("name"=>"Date/Time","type"=>"timestamp"));
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
	    $this->map->item("ip_address",array("name"=>"IP Address","type"=>"ipv4"));
	    $this->map->item("agent",array("name"=>"Browser/Agent","width"=>60));
	}
    function export_query() {
		$query_where=array();
		if ($this->map->options['user_id']) $query_where[]=new query_where("log.user_id","=",$this->map->options['user_id']);
        if (strlen($this->map->options['type'])) $query_where[]=new query_where("log.type","=",$this->map->options['type']);
        if (strlen($this->map->options['subtype'])) $query_where[]=new query_where("log.subtype","=",$this->map->options['subtype']);
        if ($this->map->options['from_date']) $query_where[]=new query_where("log.date_time",">=",strtotime($this->map->options['from_date']));
        if ($this->map->options['thru_date']) $query_where[]=new query_where("log.date_time","<",strtotime($this->map->options['thru_date'] . " +1 day"));
		if ($this->map->options['timestamp']) $query_where[]=new query_where("log.date_time","<=",$this->map->options['timestamp']);
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
        $query[]="log.* from log";
        $query[]="left join user on user.id=log.user_id";
        $query[]="left join country on country.country=user.country_code and country.state=''";
        $query[]=$this->where($query_where);
        $query[]="order by id " . $this->map->options['descending'];
		return $query;
    }
    function scs_hotfix($execute=FALSE) {
        $this->hotfix=new mysql_hotfix_class("log", "Access log");
        $this->hotfix->version("05/08/2018", "Initial release");
        $this->hotfix->process($execute);
    }
}
class log_data_class {
	var $id=0;
	var $type;
    var $subtype;
	var $comment=array();
    var $sku;
	var $user_id=0;
	var $ip_address=0;
	var $agent;
	var $date_time=0;
}
class log_constant_class {

}
/*
ALTER TABLE `log`
DROP INDEX `type_index` ;

ALTER TABLE `log`
ADD INDEX `type_index` (`type` ASC),
ADD INDEX `date_index` (`date_time` ASC),
COMMENT = 'Access log (05/08/2018)';
*/
/*
CREATE TABLE `log` (
  `id` int(12) NOT NULL AUTO_INCREMENT,
  `type` varchar(40) DEFAULT '',
  `subtype` varchar(40) DEFAULT '',
  `comment` mediumtext,
  `sku` mediumtext,
  `user_id` int(12) DEFAULT '0',
  `ip_address` bigint(14) DEFAULT '0',
  `agent` mediumtext,
  `date_time` int(14) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `type_index` (`type`),
  KEY `date_index` (`date_time`)
) ENGINE=MyISAM CHARSET=latin1 COMMENT='Access log (05/08/2018)'
*/
?>