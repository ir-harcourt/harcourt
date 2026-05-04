<?php
$database->partcommunity = new partcommunity_class();
class partcommunity_class extends database_class {
	function scs_table_version() {
		$results=array();
        $results['08/22/2019']="Add smart sku";
        $results['03/11/2019']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
	function __construct() {
    	$this->data=new partcommunity_data_class();
        $this->fetch=array();
    	$this->meta=new database_meta_class();
        $this->constant=new partcommunity_constant_class();
    }
    function fetch($fetch=FALSE) {
        if ($fetch) $this->fetch=$this->fetch_array();
	    $this->data=new partcommunity_data_class();
	    $this->data->id=$this->fetch['id'];
	    $this->data->lina=$this->fetch['lina'];
	    $this->data->nn=$this->fetch['nn'];
	    $this->data->nt=$this->fetch['nt'];
	    $this->data->deeplink=$this->fetch['deeplink'];
	    $this->data->path=$this->fetch['path'];
	    $this->data->sku=$this->fetch['sku'];
	    $this->data->found=$this->fetch['found'];
    }
    function read($id,$field="id") {
		$query=array("select * from partcommunity");
	    $query[]="where {$field}=" . fn_escape($id);
        $this->query($query);
    	$this->data=new partcommunity_data_class($id);
        if ($this->meta->rows) $this->fetch(TRUE);
        $this->free_result();
    }
    function update($update=FALSE) {
		$fields=array();
		$fields[]="id=" . fn_escape($this->data->id,FALSE);
		$fields[]="lina=" . fn_escape($this->data->lina);
		$fields[]="nn=" . fn_escape($this->data->nn);
		$fields[]="nt=" . fn_escape($this->data->nt);
		$fields[]="deeplink=" . fn_escape($this->data->deeplink);
		$fields[]="path=" . fn_escape($this->data->path);
		$fields[]="sku=" . fn_escape($this->data->sku);
        $fields[]="found=" . fn_escape($this->data->found,FALSE);
        $query=array();
    	if ($update) {
			$query[]="update partcommunity set";
            $query[]=implode(",\n",$fields);
            $query[]="where id=" . fn_escape($this->data->id);
		  } else {
        	$query[]="insert into partcommunity set";
            $query[]=implode(",\n",$fields);
        }
		$this->query($query);
		if ((!$this->data->id) && (!$this->meta->error)) $this->data->id = $this->insert_id();
    }
    function map($options=array()) {
    global $database;
        $this->map=new table_map_class($options);
	    $this->map->item("lina",array("required"=>TRUE));
	    $this->map->item("nn",array("required"=>TRUE));
	    $this->map->item("nt",array("required"=>TRUE));
	    $this->map->item("deeplink",array("required"=>TRUE));
	    $this->map->item("path",array("required"=>TRUE));
    }
    function import_initialize() {
        $this->query("drop table if exists `partcommunity`");
    	$query=array();
	    $query[]="CREATE TABLE `partcommunity` (";
	    $query[]="`id` bigint(10) NOT NULL AUTO_INCREMENT,";
	    $query[]="`lina` varchar(256) DEFAULT NULL,";
	    $query[]="`nn` varchar(256) DEFAULT NULL,";
	    $query[]="`nt` varchar(256) DEFAULT NULL,";
	    $query[]="`deeplink` mediumtext,";
	    $query[]="`path` varchar(256) DEFAULT '',";
	    $query[]="`sku` varchar(120) DEFAULT NULL,";
	    $query[]="`found` int(1) DEFAULT '0',";
	    $query[]="PRIMARY KEY (`id`),";
	    $query[]="UNIQUE KEY `lina_key` (`lina`)";
	    $query[]=") ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Partcommunity import (03/11/2019)'";
        $this->query($query);
    }
    function import_update($data) {
    global $database;
		$this->read($data['lina'],"lina");
        switch (TRUE) {
          case (!strlen($data['lina'])):
          	$this->map->error['lina']="Cannot be blank";
            break;
          case ($this->meta->rows):
          	$this->map->error['lina']="Duplicate: " . $data['lina'];
            break;
		}
        if (sizeof($this->map->error)) return;
        $this->data->lina=$data['lina'];
        $this->data->nn=$data['nn'];
        $this->data->nt=$data['nt'];
        $this->data->deeplink=$data['deeplink'];
        $this->data->path=$data['path'];
        $this->data->sku=$data['lina'];
        $database->inventory->read($data['lina'],"sku");
        if (!$database->inventory->meta->rows) {
	        $this->data->sku=str_replace(array("_"),array(" "),$data['lina']);
	        $database->inventory->read($this->data->sku,"sku");
        }
        $this->data->found=$database->inventory->meta->rows;
    	if ( ($this->map->update) && (!sizeof($this->map->error)) ) {
        	$this->update(FALSE);
            switch (TRUE) {
              case ($this->meta->error):
            	$this->map->error['mysql']=$this->meta->error;
                break;
              case ($this->data->found):
              	$database->inventory->data->prj=$this->data->deeplink;
                $database->inventory->update(TRUE);
              	break;
            }
		}
    }
    function import_complete($clear=FALSE) {
    global $database;
    	if ($clear) $database->temp->query("update subcategory set prj=''");
        $query=array("select * from inventory");
        $query[]="where prj <> ''";
        $query[]="group by subcategory_id";
        $query[]="order by subcategory_id";
		$database->inventory->query($query);
        while ($database->inventory->fetch = $database->inventory->fetch_array() ) {
			$database->inventory->fetch();
            $database->subcategory->read($database->inventory->data->subcategory_id);
            if (!$database->subcategory->meta->rows) continue;
            parse_str($database->inventory->data->prj,$prj);
            $database->subcategory->data->prj="info=" . $prj['info'];
			$database->subcategory->update(TRUE);
        }
    }
}
class partcommunity_data_class {
	var $id=0;
	var $lina;
	var $nn;
	var $nt;
	var $deeplink;
	var $path;
	var $sku;
	var $found=0;
}
class partcommunity_constant_class {
}
/*
load data infile "c:/temp/harcourt.txt"
into table partcommunity
fields terminated by '\t'
OPTIONALLY ENCLOSED BY '"'
lines terminated by '\n'
IGNORE 1 LINES

CREATE TABLE `partcommunity` (
  `id` bigint(10) NOT NULL AUTO_INCREMENT,
  `lina` varchar(256) DEFAULT NULL,
  `nn` varchar(256) DEFAULT NULL,
  `nt` varchar(256) DEFAULT NULL,
  `deeplink` mediumtext,
  `path` varchar(256) DEFAULT '',
  `sku` varchar(120) DEFAULT NULL,
  `found` int(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `lina_key` (`lina`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Partcommunity import (03/11/2019)'
*/
?>