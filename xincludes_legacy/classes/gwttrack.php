<?php
$database->gwttrack=new gwttrack_class();
class gwttrack_class extends database_class {
	function scs_table_version() {
		$results=array();
        $results['02/03/2015']="Initial release";
		$results['file']= __FILE__;
        return $results;
    }
	function __construct() {
    	$this->data=new gwttrack_data_class();
    	$this->meta=new database_meta_class();
        $this->constant=new gwttrack_constant_class();
        $this->fetch=array();
    }
    function fetch($fetch=FALSE) {
        if ($fetch) $this->fetch=$this->fetch_array();
	    $this->data=new gwttrack_data_class();
	    $this->data->id=$this->fetch['id'];
	    $this->data->domain=$this->fetch['domain'];
	    $this->data->type=$this->fetch['type'];
	    $this->data->code=$this->fetch['code'];
	    $this->data->initial_date=fn_date($this->fetch['initial_date'],"mysql");
	    $this->data->comment=$this->fetch['comment'];
	    $this->data->active=$this->fetch['active'];
    }
    function read() {
		$query_where=array();
		switch (TRUE) {
          case (func_num_args() == 1):
			$query_where[]=new query_where("id","=",func_get_arg(0));
            break;
          case (func_num_args() == 3):
			$query_where[]=new query_where("domain","=",func_get_arg(0));
			$query_where[]=new query_where("type","=",func_get_arg(1));
			$query_where[]=new query_where("code","=",func_get_arg(2));
            break;
		  default:
			die("Invalid # of arguments");
		}
        $query =
        	"select * from gwttrack " .
			$this->where($query_where);
        $this->query($query);
        if ($this->meta->rows) {
        	$this->fetch(TRUE);
			$this->free_result();
		  } else {
			$this->data=new gwttrack_data_class();
		}
    }
    function update($update=FALSE) {
		$fields=array();
        $fields[]="id=" . fn_escape($this->data->id,FALSE);
        $fields[]="domain=" . fn_escape($this->data->domain);
        $fields[]="type=" . fn_escape($this->data->type);
        $fields[]="code=" . fn_escape($this->data->code);
        $fields[]="initial_date=" . fn_escape($this->data->initial_date,TRUE);
        $fields[]="comment=" . fn_escape($this->data->comment);
        $fields[]="active=" . fn_escape($this->data->active,FALSE);
    	if ($update) {
			$query=
            	"update gwttrack set \n" .
                implode(",\n",$fields) . "\n" .
                "where id=" . fn_escape($this->data->id);
		  } else {
          	$query=
			"insert into gwttrack set \n" .
            implode(",\n",$fields);
		}
		$this->query($query);
        if ((!$this->id) && (!$this->meta->error)) $this->data->id = $this->insert_id();
    }
    function delete($id) {
	    $query =
	        "delete from gwttrack where " .
	        "id=" . fn_escape($id);
		$this->query($query);
    }
}
class gwttrack_data_class {
	var $id=0;
	var $domain;
	var $type;
    var $code;
	var $comment;
    var $initial_date;
    var $active=1;
	function __construct() {
		$this->initial_date=date("Y/m/d");
	}
}
class gwttrack_constant_class {
}

/*
CREATE TABLE `gwttrack` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `domain` varchar(128) NOT NULL DEFAULT '',
  `type` varchar(20) NOT NULL DEFAULT '',
  `code` varchar(512) NOT NULL DEFAULT '',
  `initial_date` date NOT NULL DEFAULT '0000-00-00',
  `comment` mediumtext,
  `active` int(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code_key` (`domain`,`type`,`code`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Google Webmaster Tools Tracking (02/05/2015)'
*/
?>