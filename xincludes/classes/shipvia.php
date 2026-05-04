<?php
$database->shipvia=new shipvia_class();
class shipvia_class extends database_class {
	function scs_table_version() {
		$results=array();
        $results['04/01/2025']="Add hotfix capability";
        $results['04/19/2021']="Hotfix: Add account";
        $results['01/07/2021']="Initial release";
		$results['file']= __FILE__;
        return $results;
    }
	function __construct() {
    	$this->data=new shipvia_data_class();
    	$this->meta=new database_meta_class();
        $this->constant=new shipvia_constant_class();
        $this->fetch=array();
    }
    function fetch($fetch=FALSE) {
        if ($fetch) $this->fetch=$this->fetch_array();
	    $this->data=new shipvia_data_class();
	    $this->data->id=$this->fetch['id'];
	    $this->data->name=$this->fetch['name'];
	    $this->data->account=$this->fetch['account'];
	    $this->data->ranking=$this->fetch['ranking'];
	    $this->data->active=$this->fetch['active'];
    }
    function read($id,$field="id") {
        $query=array("select * from shipvia");
        $query[]="where {$field}=" . fn_escape($id);
        $this->query($query);
        if ($this->meta->rows) {
        	$this->fetch(TRUE);
			$this->free_result();
		  } else {
			$this->data=new shipvia_data_class();
		}
    }
    function update($update=FALSE) {
		$fields=array();
        $fields[]="id=" . fn_escape($this->data->id,FALSE);
        $fields[]="name=" . fn_escape($this->data->name);
        $fields[]="account=" . fn_escape($this->data->account,FALSE);
        $fields[]="ranking=" . fn_escape($this->data->ranking,FALSE);
        $fields[]="active=" . fn_escape($this->data->active,FALSE);
        $query=array();
    	if ($update) {
          	$query[]="update shipvia set";
            $query[]=implode(",\n",$fields);
            $query[]="where id=" . fn_escape($this->data->id);
		  } else {
          	$query[]="insert into shipvia set";
            $query[]=implode(",\n",$fields);
		}
		$this->query($query);
        if ((!$update) && (!$this->meta->error)) $this->data->id=$this->insert_id();
    }
    function delete($id) {
	    $query=array("delete from shipvia");
        $query[]="where id=" . fn_escape($id);
		$this->query($query);
    }
    function select($compare="",$select_options=array(),$forms_options=array()) {
    global $database, $forms;
		if (!array_key_exists("field",$select_options)) $select_options['field']="shipvia_name";
		$query=array("select * from shipvia");
        $query[]="order by ranking, name";
		$this->query($query);
		$results=array();
        while ($this->fetch = $this->fetch_array() ) {
			$this->fetch();
            $results[]=$this->data->name;
        }
        $this->free_result();
        return $forms->select($select_options['field'],$results,$compare,FALSE,$forms_options);
    }
    function scs_hotfix($execute=FALSE) {
        $this->hotfix=new mysql_hotfix_class("shipvia", "Ship Via Codes");
        $this->hotfix->version("01/07/2021", "Add account");
        $this->hotfix->version("04/19/2021", "Initial release");
        $this->hotfix->process($execute);
    }
    function scs_hotfix_20210419($meta) {
        $fields=array();
        $fields[]="ADD COLUMN `account` INT(1) NOT NULL DEFAULT '0' AFTER `name`";
        $meta->count=$this->hotfix->alter($fields);
    }
}
class shipvia_data_class {
	var $id=0;
	var $name;
    var $account=0;
    var $ranking=999;
	var $active=1;
}
class shipvia_constant_class {
}
/*
ALTER TABLE `shipvia`
ADD COLUMN `account` INT(1) NOT NULL DEFAULT '0' AFTER `name`,
COMMENT = 'Ship Via Codes (04/19/2021)' ;

CREATE TABLE `shipvia` (
  `id` bigint(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL DEFAULT '',
  `account` int(1) NOT NULL DEFAULT '0',
  `ranking` int(6) NOT NULL DEFAULT '0',
  `active` int(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`,`name`),
  UNIQUE KEY `name_UNIQUE` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COMMENT='Ship Via Codes (04/19/2021)'
*/
?>