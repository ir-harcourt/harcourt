<?php
$database->cmscontent = new cmscontent_class();
class cmscontent_class extends database_class {
	function scs_table_version() {
		$results=array();
	    $results['06/22/2020']="Date format";
	    $results['03/31/2015']="Allow for multiple domains";
	    $results['01/14/2013']="Initial release";
		$results['file']= __FILE__;
        return $results;
    }
	function __construct() {
    	$this->data=new cmscontent_data_class();
        $this->fetch=array();
    	$this->meta=new database_meta_class();
        $this->constant=new cmscontent_constant_class();
    }
    function fetch($fetch=FALSE) {
        if ($fetch) $this->fetch=$this->fetch_array();
    	$this->data=new cmscontent_data_class();
	    $this->data->id=$this->fetch['id'];
	    $this->data->domain=$this->fetch['domain'];
	    $this->data->page=$this->fetch['page'];
	    $this->data->frame=$this->fetch['frame'];
	    $this->data->from_date=fn_date($this->fetch['from_date'],"mysql");
	    $this->data->thru_date=fn_date($this->fetch['thru_date'],"mysql");
	    $this->data->title=$this->fetch['title'];
	    $this->data->html=$this->fetch['html'];
	    $this->data->css=explode("^",$this->fetch['css']);
	    $this->data->user_id=$this->fetch['user_id'];
	    $this->data->revised=$this->fetch['revised'];
	    $this->data->status=$this->fetch['status'];
    }
    function read($id,$field="id") {
        $query=array("select * from cmscontent");
        $query[]="where $field=" . fn_escape($id);
        $this->query($query);
        if ($this->meta->rows) {
        	$this->fetch(TRUE);
		  } else {
			$this->data=new cmscontent_data_class();
		}
        $this->free_result();
    }
    function update($update=FALSE) {
    global $database;
        $this->data->user_id=$_SESSION['user']->id;
        $this->data->revised=strtotime("now");
        $fields=array();
        $fields[]="id=" . fn_escape($this->data->id,FALSE);
        $fields[]="domain=" . fn_escape($this->data->domain);
        $fields[]="page=" . fn_escape($this->data->page);
        $fields[]="frame=" . fn_escape($this->data->frame);
        $fields[]="from_date=" . fn_escape( (strtotime($this->data->from_date)) ? $this->data->from_date : "0000/00/00");
        $fields[]="thru_date=" . fn_escape( (strtotime($this->data->thru_date)) ? $this->data->thru_date : "0000/00/00");
        $fields[]="title=" . fn_escape($this->data->title);
        $fields[]="html=" . fn_escape($this->data->html);
        $fields[]="css=" . fn_escape(implode("^",$this->data->css));
        $fields[]="user_id=" . fn_escape($this->data->user_id,FALSE);
        $fields[]="revised=" . fn_escape($this->data->revised,FALSE);
        $fields[]="status=" . fn_escape($this->data->status);
        $query=array();
    	if ($update) {
          	$query[]="update cmscontent set";
          	$query[]=implode(",\n",$fields);
			$query[]="where id=" . fn_escape($this->data->id);
		  } else {
        	$query[]="insert into cmscontent set";
            $query[]=implode(",\n",$fields) ;
        }
		$this->query($query);
        if ((!$this->data->id) && (!$this->meta->error)) $this->data->id = $this->insert_id();
    }
    function delete($id) {
	    $query="delete from cmscontent where id=" . fn_escape($id);
		$this->query($query);
    }
    function load($page="") {
    global $database, $LOCAL_URL;
    	$results=array();
		if ($_SESSION['cmsdate']) {
        	$date=$_SESSION['cmsdate'];
            $status=array("Active","Pending");
		  } else {
          	$date=date("Y/m/d");
            $status=array("Active");
		}
        $pages=array();
        $pages[]=( (!$page) ? $_SERVER['PHP_SELF'] : $page);
		if ( (isset($LOCAL_URL)) && ($LOCAL_URL[1]) && (preg_match("/^\/beta\//",$pages[0])) ) $pages[]=preg_replace("/^\/beta\//","/",$pages[0]);
        $domains=array();
        $domains[]="";
        if ($database->registry->domain) $domains[]=$database->registry->domain;
		$query_where=array();
		$query_where['domain']=new query_where("domain","in",$domains);
		$query_where['page']=new query_where("page","in",$pages);
		$query_where['from_date']=new query_where("from_date","<=",$date);
	    $query_where['thru_date'][]=new query_where("thru_date",">=",$date);
	    $query_where['thru_date'][]=new query_where("thru_date","=","0000/00/00","or");
	    $query_where['status']=new query_where("status","in",$status);

		$query=array("select");
        $query[]="case";
        $query[]="  when domain=" . fn_escape($database->registry->domain) . " and page=" . fn_escape($pages[0]) . " then 1";
        $query[]="  when page=" . fn_escape($pages[0]) . " then 2";
        $query[]="  when domain=" . fn_escape($database->registry->domain) . " then 3";
        $query[]="  else 4";
        $query[]="end as page_match,";
        $query[]="cmscontent.* from cmscontent";
        $query[]=$this->where($query_where);
        $query[]="order by page_match, page, frame, from_date desc";
		$this->query($query);
	    while ($this->fetch = $this->fetch_array()) {
	        $this->fetch();
			if (!array_key_exists($this->data->frame,$results)) $results[$this->data->frame]=$this->data;
		}
        $this->free_result();
        return $results;
    }
    function results() {
	global $forms;
        $results=array();
        if  ($_SESSION['cmsdebug']) {
			if ($this->data->status=="Active") {
            	$bg=$forms->background->greenbar;
			  } else {
              	$bg=$forms->background->silver;
            }
	        $results[]="<div id=cmsdate_open" . $this->data->frame . " $bg>";
            $text=array();
            $text[]="<b>CMS content begin frame " . $this->data->frame  . "</b>";
            $text[]="Record ID: " . $this->data->id;
            if ($this->data->title)  $text[]="Title: " . $this->data->title;
            if ($this->data->from_date) $text[]="Start date: " . date("l F j, Y",strtotime($this->data->from_date));
            if ($this->data->thru_date) $text[]="End date: " . date("l F j, Y",strtotime($this->data->thru_date));
            $text[]="Revised: " . date("l F j, Y g:i A",$this->cms[$frame]->revised);
            $results[]=implode("<br>\>\n",$text);
	        $text[]="</div>";
		  } else {
			$results[]="<!-- Begin frame: " . $this->data->frame . " -->";
        }
        return implode("\n",$results);
    }
}
class cmscontent_data_class {
	var $id=0;
	var $page;
	var $frame;
    var $from_date;
	var $thru_date;
	var $title;
    var $html;
	var $css=array();
	var $user_id=0;
	var $revised=0;
    var $status="Active";
    var $displayed=FALSE;
    function __construct() {

    }
}
class cmscontent_constant_class {
	var $status=array("Active","Pending","Inactive");
}

/*
CREATE TABLE `cmscontent` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `domain` varchar(255) NOT NULL DEFAULT '',
  `page` varchar(255) DEFAULT NULL,
  `frame` varchar(255) DEFAULT NULL,
  `from_date` date DEFAULT '0000-00-00',
  `thru_date` date DEFAULT '0000-00-00',
  `title` mediumtext,
  `html` longtext,
  `css` mediumtext,
  `user_id` int(12) DEFAULT '0',
  `revised` bigint(14) DEFAULT '0',
  `status` varchar(20) DEFAULT 'Active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `page_key` (`domain`,`page`,`frame`,`from_date`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='CMS content (03/31/2105)'
*/
?>