<?php
$database->portalitem = new portalitem_class();
class portalitem_class extends database_class {
	function scs_table_version() {
		$results=array();
        $results['10/12/2025']="Hotfix";
        $results['07/22/2016']="Add subcategory_id";
        $results['10/31/2012']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
	function __construct() {
    	$this->data=new portalitem_data_class();
        $this->constant=new portalitem_constant_class();
        $this->fetch=array();
    	$this->meta=new database_meta_class();
    }
    function fetch($fetch=FALSE) {
        if ($fetch) $this->fetch=$this->fetch_array();
    	$this->data=new portalitem_data_class();
	    $this->data->id=$this->fetch['id'];
	    $this->data->portal_id=$this->fetch['portal_id'];
	    $this->data->portalcategory_id=$this->fetch['portalcategory_id'];
	    $this->data->name=$this->fetch['name'];
	    $this->data->output_order=$this->fetch['output_order'];
	    $this->data->pdf_portalcontent_id=$this->fetch['pdf_portalcontent_id'];
	    $this->data->cad_portalcontent_id=$this->fetch['cad_portalcontent_id'];
	    $this->data->hyperlink_portalcontent_id=$this->fetch['hyperlink_portalcontent_id'];
	    $this->data->other_portalcontent_id=$this->fetch['other_portalcontent_id'];
	    $this->data->other_name=$this->fetch['other_name'];
	    $this->data->subcategory_id=$this->fetch['subcategory_id'];
	    $this->data->active=$this->fetch['active'];
    }
    function read($id,$field="id") {
        $query=array("select * from portalitem");
        $query[]="where {$field}=" . fn_escape($id);
        $this->query($query);
        if ($this->meta->rows) {
            $this->fetch(TRUE);
          } else {
            $this->data=new portalitem_data_class();
        }
    }
    function update($update=FALSE) {
		$fields=array();
	    $fields[]="id=" . fn_escape($this->data->id,FALSE);
	    $fields[]="portal_id=" . fn_escape($this->data->portal_id,FALSE);
	    $fields[]="portalcategory_id=" . fn_escape($this->data->portalcategory_id,FALSE);
	    $fields[]="name=" . fn_escape($this->data->name);
	    $fields[]="output_order=" . fn_escape($this->data->output_order,FALSE);
	    $fields[]="pdf_portalcontent_id=" . fn_escape($this->data->pdf_portalcontent_id,FALSE);
	    $fields[]="cad_portalcontent_id=" . fn_escape($this->data->cad_portalcontent_id,FALSE);
	    $fields[]="hyperlink_portalcontent_id=" . fn_escape($this->data->hyperlink_portalcontent_id,FALSE);
	    $fields[]="other_portalcontent_id=" . fn_escape($this->data->other_portalcontent_id,FALSE);
	    $fields[]="other_name=" . fn_escape($this->data->other_name);
	    $fields[]="subcategory_id=" . fn_escape($this->data->subcategory_id,FALSE);
	    $fields[]="active=" . fn_escape($this->data->active,FALSE);
        $query=array();
        if ($update) {
          	$query[]="update portalitem set";
            $query[]=implode(",\n",$fields);
            $query[]="where id=" . fn_escape($this->data->id);
		  } else {
        	$query[]="insert into portalitem set";
            $query[]=implode(",\n",$fields);
		}
		$this->query($query);
        if ((!$this->data->id) && (!$this->meta->error)) $this->data->id = $this->insert_id();
    }
    function delete($id) {
	    $query=array("delete from portalitem where");
	    $query[]="id=" . fn_escape($id);
		$this->query($query);
    }
    function scs_hotfix($execute=FALSE) {
        $this->hotfix=new mysql_hotfix_class("portalitem", "Portal item");
        $this->hotfix->version("07/22/2016", "Initial release");
        $this->hotfix->version("10/12/2025", "Add hyperlink_portalcontent_id");
        $this->hotfix->process($execute);
    }
    function scs_hotfix_20251012($meta) {
        $fields=array();
        $fields[]="ADD COLUMN `hyperlink_portalcontent_id` INT(11) NULL DEFAULT '0' AFTER `cad_portalcontent_id`";
        $meta->count=$this->hotfix->alter($fields);
    }
}
class portalitem_data_class {
	var $id=0;
	var $portal_id=0;
	var $portalcategory_id=0;
	var $name;
    var $output_order;
	var $pdf_portalcontent_id=0;
	var $cad_portalcontent_id=0;
	var $hyperlink_portalcontent_id=0;
	var $other_portalcontent_id=0;
	var $other_name;
	var $subcategory_id=0;
	var $active=1;
}
class portalitem_constant_class {

}
/*
CREATE TABLE `portalitem` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `portal_id` int(11) DEFAULT '0',
  `portalcategory_id` int(11) DEFAULT '0',
  `name` varchar(60) DEFAULT '',
  `output_order` int(11) DEFAULT '0',
  `pdf_portalcontent_id` int(11) DEFAULT '0',
  `cad_portalcontent_id` int(11) DEFAULT '0',
  `hyperlink_portalcontent_id` int(11) DEFAULT '0',
  `other_portalcontent_id` int(11) DEFAULT '0',
  `other_name` varchar(45) DEFAULT '',
  `subcategory_id` int(11) DEFAULT '0',
  `active` int(11) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_key` (`name`,`portalcategory_id`,`portal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Portal item (10/12/2025)'
*/
?>