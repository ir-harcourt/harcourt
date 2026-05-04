<?php
$database->hero=new hero_class();
class hero_class extends database_class {
	function scs_table_version() {
		$results=array();
        $results['11/24/2025']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
	function __construct() {
    	$this->data=new hero_data_class();
        $this->fetch=array();
    	$this->meta=new database_meta_class();
        $this->constant=new hero_constant_class();
    }
    function fetch($fetch=FALSE) {
        if ($fetch) $this->fetch=$this->fetch_array();
    	$this->data=new hero_data_class();
	    $this->data->id=$this->fetch['id'];
	    $this->data->effective_date=fn_date($this->fetch['effective_date'], "mysql");
	    $this->data->portal_id=$this->fetch['portal_id'];
	    $this->data->industry_id=$this->fetch['industry_id'];
	    $this->data->language_code=$this->fetch['language_code'];
	    $this->data->html=$this->fetch['html'];
	    $this->data->css=$this->fetch['css'];
	    $this->data->js=$this->fetch['js'];
	    $this->data->access=$this->fetch['access'];
	    $this->data->status=$this->fetch['status'];
    }
    function read($id,$field="id") {
        $query=array("select * from hero");
        $query[]="where $field=" . fn_escape($id);
        $this->query($query);
        if ($this->meta->rows) {
        	$this->fetch(TRUE);
			$this->free_result();
		  } else {
			$this->data=new hero_data_class();
		}
    }
    function update($update=FALSE) {
	    $fields=array();
	    $fields[]="id=" . fn_escape($this->data->id,FALSE);
	    $fields[]="effective_date=" . fn_escape($this->data->effective_date,"date");
	    $fields[]="portal_id=" . fn_escape($this->data->portal_id,FALSE);
	    $fields[]="industry_id=" . fn_escape($this->data->industry_id,FALSE);
	    $fields[]="language_code=" . fn_escape($this->data->language_code);
	    $fields[]="html=" . fn_escape($this->data->html,"null");
	    $fields[]="css=" . fn_escape($this->data->css,"null");
        $fields[]="js=" . fn_escape($this->data->js,"null");
        $fields[]="access=" . fn_escape($this->data->access);
        $fields[]="status=" . fn_escape($this->data->status);
        $query=array();
    	if ($update) {
          	$query[]="update hero set";
            $query[]=implode(",\n",$fields);
            $query[]="where id=" . fn_escape($this->data->id);
		  } else {
        	$query[]="insert into hero set";
            $query[]=implode(",\n",$fields);
		}
		$this->query($query);
        if ( (!$this->data->id) && (!$this->meta->error) ) $this->data->id = $this->insert_id();
    }
    function delete($id) {
	    $query=array("delete from hero where");
        $query[]="id=" . fn_escape($id);
		$this->query($query);
    }
    function access() {
		return role_access($_SESSION['user']->type, $this->data->access);
    }
    function load($options=array()) {
        global $database;
        if (!array_key_exists("date", $options)) $options['date']=date("Y/m/d");
        if (!array_key_exists("portal_id", $options)) $options['portal_id']=intval($_SESSION['user']->portal_id);
        if (!array_key_exists("industry_id", $options)) $options['industry_id']=intval($_SESSION['user']->industry_id);
        if (!array_key_exists("language_code", $options)) $options['language_code']=$_SESSION['user']->language_code;
        if (!array_key_exists("role", $options)) $options['role']=$_SESSION['user']->type;
        $role_list=array("");
        foreach ($database->user->constant->type as $role) {
            if (role_access($options['role'], $role)) $role_list[]=$role;
        }
        $status=array("Active");
        if ($options['pending']) $status[]="Pending";
        $query_where=array();
        $query_where[]=new query_where("portal_id", "in", array(0, $options['portal_id']));
        $query_where[]=new query_where("industry_id", "in", array(0, $options['industry_id']));
        $query_where[]=new query_where("language_code", "in", array("EN", $options['language_code']));
        $query_where[]=new query_where("status", "in", $status);
        $query_where[]=new query_where("access", "in", $role_list);
        $query_where[]=new query_where("effective_date", "<=", $options['date']);
        $query=array("select");
        $query[]="case (true)";
        if ($options['portal_id']) $query[]="when portal_id={$options['portal_id']} then 0";
        $query[]="when industry_id={$options['industry_id']} then 1";
        $query[]="else 2";
        $query[]="end as sort_order,";
        $query[]="if (language_code = '{$options['language_code']}', 0,1) as language_match,";
        $query[]="ifnull(portal.name,'All') as 'portal_name',";
        $query[]="ifnull(industry.name, 'All') as 'industry_name',";
        $query[]="language.name as 'language_name',";
        $query[]="hero.* from hero";
        $query[]="left join portal on portal.id=hero.portal_id";
        $query[]="left join industry on industry.id=hero.industry_id";
        $query[]="left join language on language.code=hero.language_code";
        $query[]=$this->where($query_where);
        $query[]="order by sort_order, language_match, effective_date desc";
        $query[]="limit 1";
        $this->query($query);
        $this->fetch(TRUE);
        return $this->meta->rows;
    }
    function output($options=array()) {
        if ( (!$options['preview']) && (!$this->load()) ) return;
        if ($options['session']) {
            $this->data->css=$_SESSION['maintain_hero']['css'];
            $this->data->js=$_SESSION['maintain_hero']['js'];
            $this->data->html=$_SESSION['maintain_hero']['html'];
        }
        $results=array();
        if (strlen($this->data->js)) $results[]=$this->data->js;
        if (strlen($this->data->css)) $results[]=$this->data->css;
        if ($options['preview']) $results[]="<p class=center>Hero content enclosed in <b><span class=scscpq_hero_preview>&nbsp;&nbsp;Box&nbsp;&nbsp;</span></b></p>";
        $results[]="<div id=scscpq_hero class=scscpq_hero_preview>";
        $results[]=$this->data->html;
        $results[]="</div>";
        return implode("", $results);
    }
    function scs_hotfix($execute=FALSE) {
        $this->hotfix=new mysql_hotfix_class("hero", "Hero Headline");
        $this->hotfix->version("11/24/2025", "Initial Release");
        $this->hotfix->process($execute);
    }
}
class hero_data_class {
	var $id=0;
    var $effective_date=0;
    var $portal_id=0;
    var $industry_id=0;
    var $language_code;
    var $html;
    var $css;
    var $js;
    var $access;
	var $status="Active";
}
class hero_constant_class {
    var $status=array("Active", "Pending", "Expired");
}
/*
CREATE TABLE `hero` (
  `id` bigint(10) NOT NULL AUTO_INCREMENT,
  `effective_date` date DEFAULT NULL,
  `portal_id` bigint(10) DEFAULT NULL,
  `industry_id` bigint(10) DEFAULT NULL,
  `language_code` varchar(2) DEFAULT NULL,
  `html` mediumtext,
  `css` mediumtext,
  `js` mediumtext,
  `access` varchar(20) NOT NULL DEFAULT '',
  `status` varchar(20) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_key` (`effective_date`,`portal_id`,`industry_id`,`language_code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Hero Headline (11/24/2025)'
*/
?>