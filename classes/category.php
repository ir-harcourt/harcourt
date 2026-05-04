<?php
$database->category = new category_class();
class category_class extends database_class {
	function scs_table_version() {
		$results=array();
        $results['08/29/2014']="Incorporate language";
        $results['06/21/2011']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
	function __construct() {
    	$this->data=new category_data_class();
        $this->fetch=array();
    	$this->meta=new database_meta_class();
        $this->constant=new category_constant_class();
    }
    function fetch($fetch=FALSE) {
        if ($fetch) $this->fetch=$this->fetch_array();
	    $this->data=new category_data_class();
	    $this->data->id=$this->fetch['id'];
	    $this->data->name=$this->fetch['name'];
	    $this->data->sort_order=$this->fetch['sort_order'];
	    $this->data->active=$this->fetch['active'];
    }
    function read($id,$field="id") {
		$query=
	        "select * from category " .
	        "where $field=" . fn_escape($id);
        $this->query($query);
    	$this->data=new category_data_class($id);
        if ($this->meta->rows) $this->fetch(TRUE);
        $this->free_result();
    }
    function update($update=FALSE) {
		$fields=array();
		$fields[]="id=" . fn_escape($this->data->id,FALSE);
		$fields[]="name=" . fn_escape($this->data->name);
		$fields[]="sort_order=" . fn_escape($this->data->sort_order,FALSE);
		$fields[]="active=" . fn_escape($this->data->active,FALSE);
        $query=array();
    	if ($update) {
			$query[]="update category set";
            $query[]=implode(",\n",$fields);
            $query[]="where id=" . fn_escape($this->data->id);
		  } else {
        	$query[]="insert into category set";
            $query[]=implode(",\n",$fields);
        }
		$this->query($query);
		if ((!$this->data->id) && (!$this->meta->error)) $this->data->id = $this->insert_id();
    }
    function delete($id) {
	    $query=array("delete from category where id=" . fn_escape($id));
		$this->query($query);
    }
    function select($compare,$search_options=array(),$forms_options=array()) {
    global $forms;
		if (array_key_exists("field",$search_options)) {
        	$field=$search_options['field'];
		  } else {
			$field="category_id";
		}
		if (array_key_exists("blank",$search_options)) {
        	$blank=$search_options['blank'];
		  } else {
			$blank=TRUE;
		}
	    $query="select * from category order by sort_order,name";
	    $this->query($query);
        $results=array();
	    while ($this->fetch = $this->fetch_array()) {
			$this->fetch();
            $results[$this->data->id]=$this->data->name;
	    }
	    $this->free_result();
        return $forms->select($field,$results,$compare,$blank,$forms_options);
    }
}
class category_data_class {
	var $id;
	var $name;
    var $sort_order=0;
	var $active=1;
}
class category_constant_class {
}
/*
CREATE TABLE `category` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(60) DEFAULT NULL,
  `sort_order` int(2) unsigned NOT NULL DEFAULT '0',
  `active` int(1) unsigned DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_key` (`name`),
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Product Category (09/02/2014)'
*/
?>