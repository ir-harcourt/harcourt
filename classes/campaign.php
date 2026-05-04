<?php
$database->campaign = new campaign_class();
class campaign_class extends database_class {
	function scs_table_version() {
		$results=array();
        $results['10/21/2019']="Add sf_campaign_id";
        $results['12/19/2017']="Add type";
        $results['09/07/2017']="Add js, css";
        $results['05/04/2017']="Add invite, unlimited documents";
        $results['04/20/2017']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
	function __construct() {
    	$this->data=new campaign_data_class();
        $this->fetch=array();
    	$this->meta=new database_meta_class();
        $this->constant=new campaign_constant_class();
    }
    function fetch($fetch=FALSE) {
        if ($fetch) $this->fetch=$this->fetch_array();
	    $this->data=new campaign_data_class();
	    $this->data->id=intval($this->fetch['id']);
	    $this->data->code=$this->fetch['code'];
	    $this->data->name=$this->fetch['name'];
	    $this->data->description=$this->fetch['description'];
	    $this->data->from_date=fn_date($this->fetch['from_date'],'mysql');
	    $this->data->thru_date=fn_date($this->fetch['thru_date'],'mysql');
	    $this->data->type=$this->fetch['type'];
	    $this->data->sort_order=intval($this->fetch['sort_order']);
	    $this->data->title_omit=intval($this->fetch['title_omit']);
	    $this->data->active=intval($this->fetch['active']);
	    if (strlen($this->fetch['divs'])) $this->data->divs=unserialize($this->fetch['divs']);
	    if (strlen($this->fetch['subcategory'])) $this->data->subcategory=explode("^",$this->fetch['subcategory']);
	    if (strlen($this->fetch['css'])) $this->data->css=explode("^",$this->fetch['css']);
	    if (strlen($this->fetch['js'])) $this->data->js=explode("^",$this->fetch['js']);
	    $this->data->sf_campaign_id=$this->fetch['sf_campaign_id'];
    }
    function read($id,$field="id") {
	    $query =
	        "select * from campaign " .
	        "where $field=" . fn_escape($id);
        $this->query($query);
        if ($this->meta->rows) {
        	$this->fetch(TRUE);
			$this->free_result();
		  } else {
			$this->data=new campaign_data_class();
		}
    }
    function update($update) {
    	$field=array();
	    $fields[]="id=" . fn_escape($this->data->id,FALSE);
	    $fields[]="code=" . fn_escape($this->data->code);
	    $fields[]="name=" . fn_escape($this->data->name);
	    $fields[]="description=" . fn_escape($this->data->description);
	    $fields[]="from_date=" . fn_escape($this->data->from_date,'date');
	    $fields[]="thru_date=" . fn_escape($this->data->thru_date,'date');
	    $fields[]="type=" . fn_escape($this->data->type);
	    $fields[]="sort_order=" . fn_escape($this->data->sort_order,FALSE);
	    $fields[]="title_omit=" . fn_escape($this->data->title_omit,FALSE);
	    $fields[]="active=" . fn_escape($this->data->active,FALSE);
	    $fields[]="divs=" . fn_escape(serialize($this->data->divs));
	    $fields[]="subcategory=" . ( (sizeof($this->data->subcategory)) ? fn_escape(implode("^",$this->data->subcategory)) : "NULL");
	    $fields[]="css=" . ( (sizeof($this->data->css)) ? fn_escape(implode("^",$this->data->css)) : "NULL");
	    $fields[]="js=" . ( (sizeof($this->data->js)) ? fn_escape(implode("^",$this->data->js)) : "NULL");
	    $fields[]="sf_campaign_id=" . fn_escape($this->data->sf_campaign_id);
        $query=array();
        if ($update) {
          	$query[]="update campaign set";
            $query[]=implode(",\n",$fields);
            $query[]="where id=" . fn_escape($this->data->id);
		  } else {
          	$query[]="insert into campaign set";
            $query[]=implode(",\n",$fields);
        }
		$this->query($query);
		if ((!$this->data->id) && (!$this->meta->error)) $this->data->id = $this->insert_id();
    }
    function delete($id) {
	    $query =
	        "delete from campaign where " .
	        "id=" . fn_escape($id);
		$this->query($query);
    }
    function active() {
		if (func_num_args()) $this->read(func_get_arg(0),"code");
        switch (TRUE) {
          case (!$this->meta->rows):
          case (!$this->data->active):
          case ($this->data->from_date > date("Y/m/d")):
          case ( ($this->data->thru_date) && ($this->data->thru_date < date("Y/m/d")) ):
          	return FALSE;
          default:
          	return TRUE;
		}
    }
    function select($compare,$select_options=array(),$forms_options=array()) {
    global $forms;
		if (!array_key_exists("field",$select_options)) $select_options['field']="campaign_code";
        if (!array_key_exists("key",$select_options)) $select_options['key']="code";
        if (!array_key_exists("active",$select_options)) $select_options['active']=TRUE;
        if (!array_key_exists("sf",$select_options)) $select_options['sf']=FALSE;
        if (!array_key_exists("blank",$select_options)) $select_options['blank']=TRUE;
        $query_where=array();
        if ($select_options['active']) $query_where[]=new query_where("active","=",1);
        if ($select_options['sf']) $query_where[]=new query_where("sf_campaign_id","<>","");
        $query=array();
        $query[]="select * from campaign";
		$query[]=$this->where($query_where);
        $query[]="order by sort_order, name";
	    $this->query($query);
		$results=array();
	    while ($this->fetch = $this->fetch_array()) {
	        $this->fetch();
            $text=array($this->data->name);
            if ($this->data->from_date) $text[]="From: " . fn_date($this->data->from_date,"ymd");
            if ($this->data->thru_date) $text[]="Thru: " . fn_date($this->data->thru_date,"ymd");
            $results[$this->data->code]=implode(" ",$text);
		}
	    $this->free_result();
        return $forms->select($select_options['field'],$results,$compare,$select_options['blank'],$forms_options);
    }
    function div($field) {
    	return (array_key_exists($field,$this->data->divs) ?  $this->data->divs[$field] : "campaign_{$field}");
    }
}
class campaign_constant_class {
	var $access=array("Public","Semi-Private","Private");
    var $type=array("Invite","Public","Remote");
    var $divs=array();
	function __construct() {
		$this->divs['content']="Campaign Content";
		$this->divs['form']="Forms";
		$this->divs['form_content']="Forms Content";
		$this->divs['form_title']="Forms Title";
		$this->divs['form_disclaimer']="Forms Disclaimer";
		$this->divs['email_welcome']="Email: Welcome";
		$this->divs['email_disclaimer']="Email: Disclaimer";
    }
}
class campaign_data_class {
	var $id=0;
	var $code;
	var $name;
	var $description;
	var $from_date=0;
	var $thru_date=0;
    var $type="Invite";
    var $sort_order=0;
	var $title_omit=1;
	var $active=1;
    var $divs=array();
    var $subcategory=array();
    var $css=array();
    var $js=array();
    var $sf_campaign_id;

}
/*
ALTER TABLE `campaign`
ADD COLUMN `sf_campaign_id` VARCHAR(45) NULL DEFAULT NULL AFTER `divs`,
ADD UNIQUE INDEX `sf_campaign_key` (`sf_campaign_id` ASC),
COMMENT = 'Email marketing campaign (10/21/2019)' ;

CREATE TABLE `campaign` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(12) CHARACTER SET latin1 DEFAULT NULL,
  `name` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  `description` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  `from_date` date DEFAULT '0000-00-00',
  `thru_date` date DEFAULT '0000-00-00',
  `active` int(1) unsigned DEFAULT '1',
  `subcategory` mediumtext CHARACTER SET latin1,
  `invite` int(1) DEFAULT '1',
  `type` varchar(20) DEFAULT 'Invite',
  `sort_order` int(2) DEFAULT '0',
  `css` mediumtext,
  `js` mediumtext,
  `title_omit` int(1) DEFAULT '0',
  `divs` mediumtext,
  `sf_campaign_id` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code_key` (`code`),
  UNIQUE KEY `name_key` (`name`),
  UNIQUE KEY `sf_campaign_key` (`sf_campaign_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Email marketing campaign (10/21/2019)'
*/
?>