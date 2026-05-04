<?php
$database->cmspage = new cmspage_class();
class cmspage_class extends database_class {
	function scs_table_version() {
		$results=array();
        $results['03/31/2015']="Add multiple domains";
        $results['01/10/2013']="Initial release";
		$results['file']= __FILE__;
        return $results;
    }
	function __construct() {
    	$this->data=new cmspage_data_class();
        $this->fetch=array();
    	$this->meta=new database_meta_class();
        $this->constant=new cmspage_constant_class();
    }
    function fetch($fetch=FALSE) {
        if ($fetch) $this->fetch=$this->fetch_array();
    	$this->data=new cmspage_data_class();
	    $this->data->id=$this->fetch['id'];
	    $this->data->domain=$this->fetch['domain'];
	    $this->data->page=$this->fetch['page'];
	    $this->data->name=$this->fetch['name'];
	    $this->data->meta_title=$this->fetch['meta_title'];
	    $this->data->meta_description=$this->fetch['meta_description'];
	    $this->data->active=$this->fetch['active'];
    }
    function read($id,$field="id") {
        $query =
            "select * from cmspage " .
            "where $field=" . fn_escape($id);
        $this->query($query);
        if ($this->meta->rows) {
        	$this->fetch(TRUE);
		  } else {
			$this->data=new cmspage_data_class();
		}
        $this->free_result();
    }
    function update($update=FALSE) {
		$fields=array();
		$fields[]="id=" . fn_escape($this->data->id,FALSE);
		$fields[]="domain=" . fn_escape($this->data->domain);
		$fields[]="page=" . fn_escape($this->data->page);
		$fields[]="name=" . fn_escape($this->data->name);
		$fields[]="meta_title=" . fn_escape($this->data->meta_title);
		$fields[]="meta_description=" . fn_escape($this->data->meta_description);
		$fields[]="active=" . fn_escape($this->data->active,FALSE);
    	if ($update) {
          	$query=
            	"update cmspage set \n" .
                implode(",\n",$fields) . "\n" .
				"where id=" . fn_escape($this->data->id);
		  } else {
        	$query=
        		"insert into cmspage set \n" .
                implode(",\n",$fields);
        }
		$this->query($query);
        if ((!$this->data->id) && (!$this->meta->error)) $this->data->id=$this->insert_id();
    }
    function delete($id) {
	    $query =
	        "delete from cmspage where " .
	        "id=" . fn_escape($id);
		$this->query($query);
    }
    function select($compare,$field="cmsmpage_page",$blank=TRUE,$options=array()) {
    global $forms;
		$results=array();
		$query=
            "select * from cmspage \n" .
            "order by name";
	    $this->query($query);
	    while ($this->fetch = $this->fetch_array()) {
	        $this->fetch();
            $results[$this->data->page]=$this->data->name;
	    }
	    $this->free_result();
        return $forms->select($field,$results,$compare,$blank,$options);
    }
    function load($url) {
    global $database, $LOCAL_URL;
		$this->meta->error_abort=FALSE;
        $pages=array();
        $pages[]=$url;
		if ( (isset($LOCAL_URL)) && ($LOCAL_URL[1]) && (preg_match("/^\/beta\//",$pages[0])) ) $pages[]=preg_replace("/^\/beta\//","/",$pages[0]);
        $domains=array();
        $domains[]="";
        if ($database->registry->domain) $domains[]=$database->registry->domain;

		$query_where=array();
		$query_where['domain']=new query_where("domain","in",$domains);
		$query_where['page']=new query_where("page","in",$pages);
		$query =
        	"select \n" .
            "case \n" .
            "  when domain=" . fn_escape($database->registry->domain) . " and page=" . fn_escape($pages[0]) . " then 1 \n" .
            "  when page=" . fn_escape($pages[0]) . " then 2 \n" .
            "  when domain=" . fn_escape($database->registry->domain) . " then 3 \n" .
            "  else 4 \n" .
            "end as page_match," .
            "cmspage.* from cmspage \n" .
			$this->where($query_where) .
            "order by page_match limit 1";
		$this->query($query);
        $this->fetch(TRUE);
        $this->free_result();
		return $this->meta->rows;
    }
}
class cmspage_data_class {
	var $id=0;
    var $domain;
	var $page;
	var $name;
    var $meta_title;
	var $meta_description;
    var $active=1;
    function __construct() {

    }
}
class cmspage_constant_class {

}
/*
CREATE TABLE `cmspage` (
  `id` int(12) NOT NULL AUTO_INCREMENT,
  `domain` varchar(255) NOT NULL DEFAULT '',
  `page` varchar(45) DEFAULT NULL,
  `name` varchar(45) DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` varchar(255) DEFAULT NULL,
  `active` int(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_key` (`domain`,`page`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='CMS Pages (03/31/2015)'
*/
?>