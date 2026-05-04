<?php
$database->carrier = new carrier_class();
class carrier_class extends database_class {
	function scs_table_version() {
		$results=array();
        $results['08/29/2014']="Incorporate language";
        $results['06/21/2011']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
	function __construct() {
    	$this->data=new carrier_data_class();
        $this->fetch=array();
    	$this->meta=new database_meta_class();
        $this->constant=new carrier_constant_class();
    }
    function fetch($fetch=FALSE) {
        if ($fetch) $this->fetch=$this->fetch_array();
	    $this->data=new carrier_data_class();
	    $this->data->id=$this->fetch['id'];
	    $this->data->name=$this->fetch['name'];
	    $this->data->active=$this->fetch['active'];
    }
    function read($id,$field="id") {
		$query=
	        "select * from carrier " .
	        "where $field=" . fn_escape($id);
        $this->query($query);
    	$this->data=new carrier_data_class($id);
        if ($this->meta->rows) $this->fetch(TRUE);
        $this->free_result();
    }
    function update($update=FALSE) {
		$fields=array();
		$fields[]="id=" . fn_escape($this->data->id,FALSE);
		$fields[]="name=" . fn_escape($this->data->name);
		$fields[]="active=" . fn_escape($this->data->active,FALSE);
    	if ($update) {
			$query=
            	"update carrier set \n" .
                implode(",\n",$fields) . "\n" .
				" where id=" . fn_escape($this->data->id);
		  } else {
        	$query=
            	"insert into carrier set \n" .
                implode(",\n",$fields);
        }
		$this->query($query);
		if ((!$this->data->id) && (!$this->meta->error)) $this->data->id = $this->insert_id();
    }
    function delete($id) {
	    $query =
	        "delete from carrier where " .
	        "id=" . fn_escape($id);
		$this->query($query);
    }
    function select($compare,$search_options=array(),$forms_options=array()) {
    global $forms;
		if (array_key_exists("field",$search_options)) {
        	$field=$search_options['field'];
		  } else {
			$field="carrier_name";
		}
        $blank=FALSE;
	    $query="select * from carrier order by name";
	    $this->query($query);
        $results=array();
        $results['']="Shipping Carrier";
	    while ($this->fetch = $this->fetch_array()) {
			$this->fetch();
            $results[$this->data->name]=$this->data->name;
	    }
	    $this->free_result();
		return $forms->select($field,$results,$compare,$blank,$forms_options);
    }
}
class carrier_data_class {
	var $id;
	var $name;
    var $sort_order=0;
	var $active=1;
}
class carrier_constant_class {
}
/*
CREATE TABLE `carrier` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) DEFAULT NULL,
  `active` int(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_key` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Carrier Codes (11/23/2015)'
*/
?>