<?php
$database->document=new document_class();
class document_class extends database_class {
	function scs_table_version() {
		$results=array();
        $results['08/04/2014']="Content stored in content folder";
        $results['08/17/2010']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
	function __construct() {
    	$this->data=new document_data_class();
    	$this->meta=new database_meta_class();
        $this->fetch=array();
        $this->constant=new document_constant_class();
    }
    function fetch($fetch=FALSE) {
        if ($fetch) $this->fetch=$this->fetch_array();
	    $this->data=new document_data_class();
        $this->data->id=$this->fetch['id'];
	    $this->data->parent_id=$this->fetch['parent_id'];
	    $this->data->name=$this->fetch['name'];
	    $this->data->description=$this->fetch['description'];
	    $this->data->active=$this->fetch['active'];
    }
    function read($id) {
        $query =
            "select * from document " .
            "where id=" . fn_escape($id);
        $this->query($query);
        if ($this->meta->rows) {
        	$this->fetch(TRUE);
		  } else {
			$this->data=new document_data_class();
		}
        $this->free_result();
    }
    function update($update=FALSE) {
		$fields=array();
	    $fields[]="id=" . fn_escape($this->data->id,FALSE);
	    $fields[]="parent_id=" . fn_escape($this->data->parent_id,FALSE);
	    $fields[]="name=" . fn_escape($this->data->name);
	    $fields[]="description=" . fn_escape($this->data->description);
	    $fields[]="active=" . fn_escape($this->data->active,FALSE);
    	if ($update) {
          	$query=
            	"update document set \n" .
                implode(",\n",$fields) . "\n" .
				"where id=" . fn_escape($this->data->id);
		  } else {
        	$query=
            	"insert into document set \n" .
                implode(",\n",$fields);
        }
		$this->query($query);
		if ( (!$this->data->id) && (!$this->meta->error) && (!$this->data->id) ) $this->data->id = $this->insert_id();
    }
    function delete($id) {
	    $query =
	        "delete from document where " .
	        "id=" . fn_escape($id);
		$this->query($query);
    }
    function select($compare,$select_options=array(),$forms_options=array()) {
	global $database, $forms;
		if (!array_key_exists("field",$select_options)) $select_options['field']="document_id";
		if (!array_key_exists("blank",$select_options)) $select_options['blank']=TRUE;
		$results=array();
 		$query_where=array();
	    $query =
	        "select \n" .
	        "(case \n" .
	        "when parent_id=1 then 9999 \n" .
	        "when id=1 then 9999 \n" .
	        "when parent_id=0 then id \n" .
	        "else parent_id \n" .
	        "end) as sort_key, \n" .
	        "document.* from document \n" .
            $database->where($query_where) .
	        "order by sort_key,parent_id,name";
	    $this->query($query);
        $sort_key;
	    while ($this->fetch = $this->fetch_array()) {
	        $this->fetch();
	        if ($this->fetch['sort_key'] != $sort_key) {
	            $sort_key=$this->fetch['sort_key'];
                $sort_name=$this->data->name;
	        }
            $results[$sort_name][$this->data->id]=$this->data->id . ". " . $this->data->name;
		}
	    $this->free_result();
        return $forms->optgroup($select_options['field'],$results,$compare,$select_options['blank'],$forms_options);
    }
    function ajax($search_terms,$limit,$options=array()) {
	    $query_where=array();
	    foreach ($search_terms as $item) {
	        $query_where[]=new query_where("concat('#',id,' ',name)","like","%" . $item . "%");
	    }
	    $query=
	        "select * from document \n" .
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
		if (func_num_args()) {
	        $query_where=array();
	        $query_where[]=new query_where("id","=",$keys[0]);
	        $query=
	            "select * from document \n" .
	            $this->where($query_where);
	        $this->query($query);
	        $this->fetch(TRUE);
	        $this->free_result();
		}
        $results=array();
        $text=array();
        $text[]="#" . $this->data->id . ")";
        $text[]=$this->data->name;
		$results['value']=$this->data->id;
		$results['label']=implode(" ",$text);
		$results['id']=$this->data->id;
		$results['name']=$this->data->name;
        return $results;
    }

    function login_verify() {
	global $menu, $forms;
    	$success=FALSE;
		switch (TRUE) {
	      case ($_COOKIE['harcourt']):
			$menu->cookie($_COOKIE['harcourt']);
	        break;
	      case ($_POST['action'] == "email"):
	        $email=new email_class($_POST['email'],TRUE);
	        $forms->report['email']=$email->address;
	        if ($email->error) {
            	$forms->error('email',"",$email->error);
	          } else {
                $success=TRUE;
                $menu->cookie($forms->report['email']);
				if (isset($_SESSION['login_url'])) {
	                $url=parse_url($_SESSION['login_url']);
                    if ($url['query']) {
	                    parse_str($url['query'],$url_query);
	                    foreach ($url_query as $key => $value) {
                        	$forms->report[$key]=$value;
                        }
					}
					unset($_SESSION['login_url']);
				}
	        }
		}
        return $success;
	}
    function login_document() {
	global $menu, $forms;
        $_SESSION['email_form']=$_SERVER['PHP_SELF'];
		if ($_SESSION['user']->login_document_id) {
	        $menu->document_viewer($_SESSION['user']->login_document_id,0,TRUE);
		  } else {
			if (!isset($_SESSION['login_url'])) {
            	$login_url=fn_url($_SERVER['PHP_SELF'],$_REQUEST);
                $_SESSION['login_url']=$login_url;
            }
	        $menu->document_viewer(156,0,TRUE,$login_url);
	    }
	}
}
class document_data_class {
	var $id;
	var $parent_id;
	var $name;
	var $description;
	var $active=1;
    function __construct() {
    }
}
class document_constant_class {
}
/*
CREATE TABLE `document` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `parent_id` int(10) unsigned default '0',
  `name` varchar(100) default NULL,
  `description` varchar(100) default NULL,
  `active` int(1) unsigned default '1',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Web site documents (08/04/1014)'

*/
?>