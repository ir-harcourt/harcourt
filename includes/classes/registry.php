<?php
class registry_class extends database_class {
	var $domain;
    var $global_load=TRUE;
    var $global_domain=FALSE;
    var $meta;
    var $installed_module=array();
	var $read_id=FALSE;
	var $fetch=array();
    function scs_table_version() {
    	$results=array();
        $results['05/26/2020']="Do not load if called from scs_mysql_remote.php";
        $results['09/22/2014']="Bad order on arrays with over 10 numeric keys";
        $results['04/11/2014']="Add version_check";
        $results['02/28/2014']="Add GLOBAL option";
        $results['04/20/2013']="Registry entry moved to registry_rev0.php";
        $results['04/11/2013']="Unlimited css & js";
        $results['03/26/2013']="File check DOCUMENT_ROOT based";
        $results['02/22/2013']="Add compliance function";
        $results['01/13/2013']="Collapse registry modules";
        $results['12/13/2012']="Add company mobile#";
        $results['10/24/2012']="Initial release";
		$results['file']= __FILE__;
        return $results;
    }
	function __construct($domain="",$global_load=TRUE) {
		$this->domain=$domain;
        $this->global_load=$global_load;
    	$this->meta=new database_meta_class();
        if ($_SERVER['PHP_SELF'] != "/scs_mysql_remote.php") $this->load();
    }
    function fetch($fetch=FALSE) {
        if ($fetch) $this->fetch=$this->fetch_array();
        $module=$this->fetch['module'];
        $field=$this->fetch['field'];
        $item=$this->fetch['item'];
        if ((!$module) || (!$field)) return;
		$this->installed_module[$module]=TRUE;
        if (!isset($this->{$module})) $this->{$module}=new stdClass();
        if (strlen($item)) {
			if (!isset($this->{$module}->{$field})) $this->{$module}->{$field}=array();
			if (!isset($this->{$module}->{$field}[$item])) $this->{$module}->{$field}[$item]=$this->fetch['data'];
		  } else {
			if (!isset($this->{$module}->{$field})) $this->{$module}->{$field}=$this->fetch['data'];
        }
	}
    function load($module="",$field="") {
		foreach ($this->installed_module as $key => $value) {
        	switch (TRUE) {
              case (($module) && ($item==$key)):
              case (!$module):
              	unset($this->installed_module[$key]);
                unset($this->{$key});
            }
        }
		$query_where=array();
        $domains=array($this->domain);
        if ($this->global_load) $domains[]="global";
		$query_where[]=new query_where("domain","in",$domains);
        if ($module) $query_where[]=new query_where("module","=",$module);
        if ($field) $query_where[]=new query_where("field","=",$field);
        $query=array("select");
        $query[]="if (domain='global',1,0) as global,";
        $query[]="if(ceil(item),mid(concat('0000',item),-4),item) as sort_key,";
        $query[]="registry.* from registry";
        $query[]=trim($this->where($query_where));
        $query[]="order by module,field,global,sort_key";
		$this->query($query);
	    while ($this->fetch = $this->fetch_array()) {
			$this->fetch();
	    }
	    $this->free_result();
    }
    function update($module,$field,$data) {
		switch (TRUE) {
          case (!$module):
          case (!$field):
          case ($data == $this->{$module}->{$field});
        	return;
		}
		$this->delete($module,$field);
        if (!is_array($data)) $data=array(""=>$data);
        foreach ($data as $item => $value) {
            if (strlen($value)) $this->insert($module,$field,$item,$value);
        }
    }
    function read_id($module,$field,$item="",$global_domain=FALSE) {
		if ($global_domain) {
        	$domain="global";
		  } else {
        	$domain=$this->domain;
		}
        $query_where=array();
        $query_where[]=new query_where("domain","=",$domain);
        $query_where[]=new query_where("module","=",$module);
        $query_where[]=new query_where("field","=",$field);
        $query_where[]=new query_where("item","=",$item);
        $query=array("select * from registry");
        $query[]=trim($this->where($query_where));
		$this->query($query);
		$this->fetch=$this->fetch_array();
		$this->free_result();
		$this->read_id=FALSE;
		switch (TRUE) {
		  case (!$this->meta->rows):
		  case (!isset($this->{$module})):
		  case (!isset($this->{$module}->{$field})):
		  case ( ($item) && (!is_array($this->{$module}->{$field})) ):
		  case ( ($item) && (!isset($this->{$module}->{$field}[$item])) ):
		  case ( (!$item) && (is_array($this->{$module}->{$field})) ):
            break;
          default:
			$this->read_id=$this->fetch['id'];
            if ($item) {
				$this->{$module}->{$field}[$item]=$this->fetch['data'];
			   } else {
				$this->{$module}->{$field}=$this->fetch['data'];
			}
        }
        return $this->read_id;
    }
    function update_id($data) {
		if (!$this->read_id) return;
        $module=$this->fetch['module'];
        $field=$this->fetch['field'];
        $item=$this->fetch['item'];
        if (is_array($this->{$module}->{$field})) {
          	$this->{$module}->{$field}[$item]=$data;
		  } else {
          	$this->{$module}->{$field}=$data;
		}
		$query=array("update registry");
        $query[]="set data=" . fn_escape($data);
        $query[]="where id=" . fn_escape($this->read_id);
		$this->query($query);
		$this->read_id=FALSE;
    }
    function insert($module,$field,$item,$data) {
		if ($this->global_domain) {
        	$domain="global";
		  } else {
        	$domain=$this->domain;
		}
        $fields=array();
        $fields[]="domain=" . fn_escape($domain);
        $fields[]="module=" . fn_escape($module);
        $fields[]="field=" . fn_escape($field);
        $fields[]="item=" . fn_escape($item);
        $fields[]="data=" . fn_escape(trim($data));
		$query=array("insert into registry set");
        $query[]=implode(",",$fields);
		$this->query($query);
    }
    function delete($module,$field="") {
		switch (TRUE) {
          case (!$module):
          	return;
          case (!$field):
			unset($this->{$module});
            break;
           default:
			unset($this->{$module}->{$field});
		}
		$query_where=array();
		if ($this->global_domain) {
			$query_where[]=new query_where("domain","=","global");
		  } else {
			$query_where[]=new query_where("domain","=",$this->domain);
		}
		$query_where[]=new query_where("module","=",$module);
        if ($field) $query_where[]=new query_where("field","=",$field);
        $query =
        	"delete from registry \n" .
            $this->where($query_where);
		$this->query($query);
    }
    function module_version($modules=array()) {
    global $forms, $menu;
		$items=array();
        foreach ($modules as $module => $module_version) {
			$module_version=strtotime($module_version);
            if (!$module_version) continue;
			switch (TRUE) {
			  case (!isset($this->$module)):
				$items[]="<b>$module</b> module missing";
				break;
			  case (!isset($this->$module->module_version)):
			  case ($module_version > $this->$module->module_version):
				$items[]="<b>$module</b> requires version " . date("m/d/Y",$module_version);
                break;
            }
        }
		if (!sizeof($items)) return;
        $forms->message[]="Registry out of date";
		$results="";
        $results=
        	"<p class='standard'>Revision errors have been detected in the following modules:<br>" .
            implode("<br>",$items) . "</p>" .
        	"<p class='standard'>You must update your registry before continuing.<p/>";
		return $results;
    }
    function scs_hotfix($execute=FALSE) {
        $this->hotfix=new mysql_hotfix_class("registry", "Registry");
        $this->hotfix->version("12/11/2024", "All fields not null");
        $this->hotfix->version("10/26/2012", "Initial release");
        $this->hotfix->process($execute);
    }
    function scs_hotfix_20241211($meta) {
        global $database;
        $this->hotfix->trace[]=__FUNCTION__;
        $query=array();
        $query[]="ALTER TABLE `registry`";
        $query[]="CHANGE COLUMN `domain` `domain` VARCHAR(255) NOT NULL DEFAULT '' ,";
        $query[]="CHANGE COLUMN `module` `module` VARCHAR(40) NOT NULL DEFAULT '' ,";
        $query[]="CHANGE COLUMN `field` `field` VARCHAR(40) NOT NULL DEFAULT '' ,";
        $query[]="CHANGE COLUMN `item` `item` VARCHAR(40) NOT NULL DEFAULT ''";
        $this->query($query);
        $meta->count=$this->affected_rows();
    }
}
/*
CREATE TABLE `registry` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `domain` varchar(255) NOT NULL DEFAULT '',
  `module` varchar(40) NOT NULL DEFAULT '',
  `field` varchar(40) NOT NULL DEFAULT '',
  `item` varchar(40) NOT NULL DEFAULT '',
  `data` mediumtext,
  PRIMARY KEY (`id`),
  UNIQUE KEY `record_key` (`domain`,`module`,`field`,`item`)
) ENGINE=InnoDB AUTO_INCREMENT=503 DEFAULT CHARSET=latin1 COMMENT='Registry (12/11/2024)'
*/
?>