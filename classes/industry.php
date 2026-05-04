<?php
$database->industry=new industry_class();
class industry_class extends database_class {
	function scs_table_version() {
		$results=array();
        $results['03/10/2016']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
	function __construct() {
    	$this->data=new industry_data_class();
        $this->fetch=array();
    	$this->meta=new database_meta_class();
        $this->constant=new industry_constant_class();
    }
    function fetch($fetch=FALSE) {
        if ($fetch) $this->fetch=$this->fetch_array();
    	$this->data=new industry_data_class();
	    $this->data->id=$this->fetch['id'];
	    $this->data->name=$this->fetch['name'];
	    $this->data->active=$this->fetch['active'];
    }
    function read($id,$field="id") {
        $query =
            "select * from industry " .
            "where $field=" . fn_escape($id);
        $this->query($query);
        if ($this->meta->rows) {
        	$this->fetch(TRUE);
			$this->free_result();
		  } else {
			$this->data=new industry_data_class();
		}
    }
    function update($update=FALSE) {
	    $fields=array();
	    $fields[]="id=" . fn_escape($this->data->id,FALSE);
	    $fields[]="name=" . fn_escape($this->data->name);
	    $fields[]="active=" . fn_escape($this->data->active,FALSE);
        $query=array();
    	if ($update) {
          	$query[]="update industry set";
            $query[]=implode(",\n",$fields);
            $query[]="where id=" . fn_escape($this->data->id);
		  } else {
        	$query[]="insert into industry set";
            $query[]=implode(",\n",$fields);
		}
		$this->query($query);
        if ( (!$this->data->id) && (!$this->meta->error) ) $this->data->id = $this->insert_id();
    }
    function delete($id) {
	    $query =
	        "delete from industry where " .
	        "id=" . fn_escape($id);
		$this->query($query);
    }
    function select($compare,$select_options=array(),$forms_options=array()) {
    global $forms;
    	if (!array_key_exists("field",$select_options)) $select_options['field']="industry_id";
    	if (!array_key_exists("blank",$select_options)) $select_options['blank']=TRUE;
        $results=array();
		$query_where=array();
		$query_where[]=new query_where("active","=",1);
		$query=array();
        $query[]="select * from industry";
        $query[]=$this->where($query_where);
		$query[]="order by name";
		$this->query($query);
        while ($this->fetch=$this->fetch_array()) {
        	$this->fetch();
            $results[$this->data->id]=$this->data->name;
        }
        $this->free_result();
        return $forms->select($select_options['field'],$results,$compare,$select_options['blank'],$forms_options);
    }
}
class industry_data_class {
	var $id=0;
	var $name;
	var $active=1;
}
class industry_constant_class {
}
/*
CREATE TABLE `industry` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(60) NOT NULL DEFAULT '',
  `active` int(1) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Industry (03/10/2016)'
*/
?>