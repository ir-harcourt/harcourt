<?php
$database->portal = new portal_class();
class portal_class extends database_class {
	function scs_table_version() {
		$results=array();
        $results['11/21/2025']="Hotfix";
        $results['03/24/2016']="Add legacy";
        $results['10/31/2012']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
	function __construct() {
    	$this->data=new portal_data_class();
        $this->constant=new portal_constant_class();
        $this->fetch=array();
    	$this->meta=new database_meta_class();
    }
    function fetch($fetch=FALSE) {
        if ($fetch) $this->fetch=$this->fetch_array();
    	$this->data=new portal_data_class();
	    $this->data->id=$this->fetch['id'];
	    $this->data->name=$this->fetch['name'];
	    $this->data->headline=$this->fetch['headline'];
	    $this->data->active=$this->fetch['active'];
    }
    function read($id,$field="id") {
        $query=array("select * from portal");
        $query[]="where {$field}=" . fn_escape($id);
        $this->query($query);
        if ($this->meta->rows) {
        	$this->fetch(TRUE);
		  } else {
			$this->data=new portal_data_class();
		}
        $this->free_result();
    }
    function update($update=FALSE) {
		$fields=array();
	    $fields[]="id=" . fn_escape($this->data->id,FALSE);
	    $fields[]="name=" . fn_escape($this->data->name);
	    $fields[]="headline=" . fn_escape($this->data->headline);
	    $fields[]="active=" . fn_escape($this->data->active,FALSE);
        $query=array();
    	if ($update) {
          	$query[]="update portal set";
            $query[]=implode("\n",$fields);
            $query[]="where id=" . fn_escape($this->data->id);
          } else {
        	$query[]="insert into portal set";
        	$query[]=implode(",\n",$fields);
        }
		$this->query($query);
        if ((!$this->data->id) && (!$this->meta->error)) $this->data->id = $this->insert_id();
    }
    function delete($id) {
	    $query=array("delete from portal where");
        $query[]="id=" . fn_escape($id);
		$this->query($query);
    }
    function select($compare,$select_options=array(),$forms_options=array() ) {
        global $forms;
		$field=(array_key_exists("field",$select_options)) ? $select_options['field'] : "portal_id";
		$blank=(array_key_exists("blank",$select_options)) ? $select_options['blank'] : TRUE;
		$active=(array_key_exists("active",$select_options)) ? $select_options['active'] : TRUE;
		$results=array();
        $query[]="select * from portal";
        if ($active) $query[]="where active=1";
        $query[]="order by name";
		$this->query($query);
		while ($this->fetch = $this->fetch_array() ) {
			$this->fetch();
            $results[$this->data->id]=$this->data->name;
        }
        $this->free_result();
        return $forms->select($field,$results,$compare,$blank,$forms_options);
    }
    function ajax($search_terms,$limit,$options=array()) {
	    $query_where=array();
	    $query_where[]=new query_where("active","=",1);
	    foreach ($search_terms as $item) {
	        $query_where[]=new query_where("name","like","%" . $item . "%");
	    }
	    $query=
	        "select * from portal \n" .
	        $this->where($query_where) .
	        "order by name";
		if ($limit) $query .= " limit $limit";
		$this->query($query);
	    $results=array();
	    while($this->fetch = $this->fetch_array() ) {
			$this->fetch();
	        $results[]=$this->ajax_fill();
		}
		$this->free_result();
		return $results;
    }
    function ajax_fill($keys=array()) {
        $results=array();
		if (func_num_args()) {
	        $query_where=array();
	        $query_where[]=new query_where("id","=",$keys[0]);
	        $query=
	            "select * from portal \n" .
	            $this->where($query_where);
	        $this->query($query);
	        $this->fetch(TRUE);
	        $this->free_result();
            if (!$this->meta->rows) return $results;
		}
        $text=array();
        $text[]=$this->data->name;
        if ($this->data->legacy) $text[]="(Legacy)";
		$results['value']=$this->data->id;
		$results['label']=implode(" ",$text);
        foreach ($this->data as $key => $value) {
	        switch (TRUE) {
	          case (is_object($value)):
	          case (is_array($value)):
	          case (array_key_exists($key,$results)):
	            break;
	          default:
	            $results[$key]=$value;
	        }
		}
        return $results;
    }
    function scs_hotfix($execute=FALSE) {
        $this->hotfix=new mysql_hotfix_class("portal", "Customer portal header");
        $this->hotfix->version("11/21/2025", "Drop legacy");
        $this->hotfix->version("03/24/2016", "Add legacy");
        $this->hotfix->version("10/31/2012", "Initial release");
        $this->hotfix->process($execute);
    }
    function scs_hotfix_20251121($meta) {
        $fields=array();
        $fields[]="drop column `legacy`";
        $meta->count=$this->hotfix->alter($fields);
    }
}
class portal_data_class {
	var $id=0;
	var $name;
    var $headline;
	var $active=1;
}
class portal_constant_class {

}
/*
CREATE TABLE `portal` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '',
  `headline` mediumtext,
  `active` int(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_key` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Customer portal header (11/21/2025)'
*/
?>