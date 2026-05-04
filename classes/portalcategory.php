<?php
$database->portalcategory = new portalcategory_class();
class portalcategory_class extends database_class {
	function scs_table_version() {
		$results=array();
        $results['08/04/2016']="Add subcategory_id";
        $results['10/31/2012']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
	function __construct() {
    	$this->data=new portalcategory_data_class();
        $this->constant=new portalcategory_constant_class();
        $this->fetch=array();
    	$this->meta=new database_meta_class();
    }
    function fetch($fetch=FALSE) {
        if ($fetch) $this->fetch=$this->fetch_array();
    	$this->data=new portalcategory_data_class();
	    $this->data->id=$this->fetch['id'];
	    $this->data->portal_id=$this->fetch['portal_id'];
	    $this->data->name=$this->fetch['name'];
	    $this->data->output_order=$this->fetch['output_order'];
	    $this->data->headline=$this->fetch['headline'];
	    $this->data->headline_portalcontent_id=$this->fetch['headline_portalcontent_id'];
	    $this->data->image_portalcontent_id=$this->fetch['image_portalcontent_id'];
	    $this->data->subcategory_id=$this->fetch['subcategory_id'];
	    $this->data->active=$this->fetch['active'];
    }
    function read($id,$field="id") {
        $query =
            "select * from portalcategory " .
            "where $field=" . fn_escape($id);
        $this->query($query);
        if ($this->meta->rows) {
        	$this->fetch(TRUE);
		  } else {
			$this->data=new portalcategory_data_class();
		}
        $this->free_result();
    }
    function update($update=FALSE) {
		$fields=array();
        $fields[]="id=" . fn_escape($this->data->id,FALSE);
        $fields[]="portal_id=" . fn_escape($this->data->portal_id,FALSE);
        $fields[]="name=" . fn_escape($this->data->name);
        $fields[]="output_order=" . fn_escape($this->data->output_order,FALSE);
        $fields[]="headline=" . fn_escape($this->data->headline);
        $fields[]="headline_portalcontent_id=" . fn_escape($this->data->headline_portalcontent_id,FALSE);
        $fields[]="image_portalcontent_id=" . fn_escape($this->data->image_portalcontent_id,FALSE);
        $fields[]="subcategory_id=" . fn_escape($this->data->subcategory_id,FALSE);
        $fields[]="active=" . fn_escape($this->data->active,FALSE);
    	if ($update) {
          	$query=
            	"update portalcategory set \n" .
                implode(",\n",$fields) . "\n" .
                "where id=" . fn_escape($this->data->id);
		  } else {
        	$query=
            	"insert into portalcategory set \n" .
                implode(",\n",$fields);
        }
		$this->query($query);
        if ((!$this->data->id) && (!$this->meta->error)) $this->data->id = $this->insert_id();
    }
    function delete($id) {
	    $query =
	        "delete from portalcategory where " .
	        "id=" . fn_escape($id);
		$this->query($query);
    }
    function select($compare,$portal_id,$select_options=array(),$forms_options=array() ) {
    global $forms;
		if (!array_key_exists("field",$select_options)) $select_options['field']="portalcategory_id";
		if (!array_key_exists("blank",$select_options)) $select_options['blank']=TRUE;
		if (!array_key_exists("subcategory_id",$select_options)) $select_options['subcategory_id']=TRUE;
		$results=array();
        $query_where=array();
        $query_where[]=new query_where("active","=",1);
        $query_where[]=new query_where("portal_id","=",$portal_id);
        if (!$select_options['subcategory_id']) $query_where[]=new query_where("subcategory_id","=",0);
        $query =
			"select * from portalcategory \n" .
			$this->where($query_where) .
            "order by output_order,name";
		$this->query($query);
		while ($this->fetch = $this->fetch_array() ) {
			$this->fetch();
            $results[$this->data->id]=$this->data->name;
        }
        $this->free_result();
        return $forms->select($select_options['field'],$results,$compare,$select_options['blank'],$forms_options);
    }
}
class portalcategory_data_class {
	var $id=0;
	var $portal_id=0;
	var $name;
    var $output_order=0;
	var $headline;
	var $headline_portalcontent_id=0;
	var $image_portalcontent_id=0;
    var $subcategory_id=0;
	var $active=1;
}
class portalcategory_constant_class {

}
/*
ALTER TABLE `portalcategory`
ADD COLUMN `subcategory_id` INT(10) NULL DEFAULT '0' AFTER `image_portalcontent_id`,
COMMENT = 'Portal category (08/04/2016)' ;

CREATE TABLE `portalcategory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `portal_id` int(11) DEFAULT '0',
  `name` varchar(60) DEFAULT '',
  `output_order` int(2) DEFAULT '0',
  `headline` mediumtext,
  `headline_portalcontent_id` int(11) DEFAULT '0',
  `image_portalcontent_id` int(11) DEFAULT '0',
  `subcategory_id` int(10) DEFAULT '0',
  `active` int(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `name_key` (`portal_id`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Portal category (08/04/2016)'
*/
?>