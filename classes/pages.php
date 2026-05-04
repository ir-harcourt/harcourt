<?php
$database->pages = new pages_class();
class pages_class extends database_class {
	function scs_record_typele_version() {
		$results=array();
        $results['03/21/2016']="Add category_id";
        $results['02/11/2015']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
	function __construct() {
    	$this->data=new pages_data_class();
        $this->fetch=array();
    	$this->meta=new database_meta_class();
        $this->constant=new pages_constant_class();
    }
    function fetch($fetch=FALSE) {
        if ($fetch) $this->fetch=$this->fetch_array();
    	$this->data=new pages_data_class();
	    $this->data->id=$this->fetch['id'];
	    $this->data->record_type=$this->fetch['record_type'];
	    $this->data->name=$this->fetch['name'];
	    $this->data->description=$this->fetch['description'];
	    $this->data->url=$this->fetch['url'];
	    $this->data->document_id=$this->fetch['document_id'];
	    $this->data->sort_order=$this->fetch['sort_order'];
	    $this->data->user_type=$this->fetch['user_type'];
	    if ($this->fetch['css']) $this->data->css=unserialize($this->fetch['css']);
	    if ($this->fetch['js']) $this->data->js=unserialize($this->fetch['js']);
	    $this->data->html_title=$this->fetch['html_title'];
	    $this->data->html_description=$this->fetch['html_description'];
	    $this->data->category_id=$this->fetch['category_id'];
	    $this->data->active=$this->fetch['active'];
    }
    function read($id,$field="id") {
        $query =
            "select * from pages " .
            "where $field=" . fn_escape($id);
        $this->query($query);
        if ($this->meta->rows) {
        	$this->fetch(TRUE);
			$this->free_result();
		  } else {
			$this->data=new pages_data_class();
		}
    }
    function update($update=FALSE) {
        $fields=array();
	    $fields[]="id=" . fn_escape($this->data->id,FALSE);
	    $fields[]="record_type=" . fn_escape($this->data->record_type);
	    $fields[]="name=" . fn_escape($this->data->name);
	    $fields[]="description=" . fn_escape($this->data->description);
	    $fields[]="url=" . fn_escape($this->data->url);
	    $fields[]="document_id=" . fn_escape($this->data->document_id,FALSE);
	    $fields[]="sort_order=" . fn_escape($this->data->sort_order,FALSE);
	    $fields[]="user_type=" . fn_escape($this->data->user_type);
	    $fields[]="css=" . fn_escape(serialize($this->data->css));
	    $fields[]="js=" . fn_escape(serialize($this->data->js));
	    $fields[]="html_title=" . fn_escape($this->data->html_title);
	    $fields[]="html_description=" . fn_escape($this->data->html_description);
	    $fields[]="category_id=" . fn_escape($this->data->category_id,FALSE);
	    $fields[]="active=" . fn_escape($this->data->active,FALSE);
    	if ($update) {
          	$query=
            	"update pages set \n" .
                implode(",\n",$fields) . "\n" .
				"where id=" . fn_escape($this->data->id);
		  } else {
        	$query="
            	insert into pages set \n" .
                implode(",\n",$fields);
        }
		$this->query($query);
		if ((!$this->data->id) && (!$this->meta->error)) $this->data->id=$this->insert_id();
    }
    function delete($id) {
	    $query =
	        "delete from pages where " .
	        "id=" . fn_escape($id);
		$this->query($query);
    }
}
class pages_data_class {
	var $id=0;
    var $record_type;
    var $name;
    var $description;
    var $url;
    var $document_id=0;
    var $sort_order=0;
    var $user_type;
    var $css=array();
    var $js=array();
    var $html_title;
    var $html_description;
    var $category_id=0;
    var $active=1;
}
class pages_constant_class {
	var $record_type=array("general"=>"General","solutions"=>"Solutions","products"=>"Products");
}
/*
CREATE TABLE `pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `record_type` varchar(20) NOT NULL DEFAULT '',
  `name` varchar(45) NOT NULL DEFAULT '',
  `description` mediumtext,
  `url` varchar(256) NOT NULL DEFAULT '',
  `document_id` int(12) NOT NULL DEFAULT '0',
  `sort_order` int(2) NOT NULL DEFAULT '0',
  `user_type` varchar(45) NOT NULL DEFAULT '',
  `css` mediumtext,
  `js` mediumtext,
  `html_title` varchar(120) NOT NULL DEFAULT '',
  `html_description` varchar(255) NOT NULL DEFAULT '',
  `active` int(1) NOT NULL DEFAULT '1',
  `category_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_key` (`record_type`,`name`),
  KEY `url_key` (`url`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Dynamic Pages (03/21/2016)'
*/
?>