<?php
$database->inventory_xref = new inventory_xref_class();
class inventory_xref_class extends database_class {
	function scs_table_version() {
		$results=array();
        $results['03/11/2019']="Improve import/export";
        $results['07/14/2011']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
	function __construct() {
    	$this->data=new inventory_xref_data_class();
    	$this->meta=new database_meta_class();
        $this->fetch=array();
        $this->constant=new inventory_xref_constant_class();
    }
    function fetch($fetch=FALSE) {
        if ($fetch) $this->fetch=$this->fetch_array();
	    $this->data=new inventory_xref_data_class();
	    $this->data->sku=$this->fetch['sku'];
	    $this->data->competitor=$this->fetch['competitor'];
	    $this->data->competitor_sku=$this->fetch['competitor_sku'];
    }
    function map($options=array()) {
        global $database;
        $this->map=new table_map_class($options);
	    $this->map->item("sku",array("name"=>"Item #","type"=>"uppercase","required"=>TRUE));
	    $this->map->item("competitor_sku",array("name"=>"Xref","type"=>"uppercase","required"=>TRUE));
        if ($options['update_timestamp']) $this->map->update_timestamp=strtotime("now");
    }
    function import_update($data) {
		$this->data=new inventory_xref_data_class();
        $this->data->sku=$data['sku'];
        $this->data->competitor_sku=$data['competitor_sku'];
        if (!$this->data->sku) $this->map->error['sku']="Cannot be blank";
        switch (TRUE) {
          case (!$this->data->competitor_sku):
          	$this->map->error['competitor_sku']="Cannot be blank";
            break;
          case ($this->data->sku == $this->data->competitor_sku):
			$this->map->error['competitor_sku']="Cannot be same as " . $this->map->fields['sku']->name;
            break;
		}
    	if ( ($this->map->update) && (!sizeof($this->map->error)) ) {
			$fields=array();
            $fields[]="sku=" . fn_escape($this->data->sku);
            $fields[]="competitor=" . fn_escape($this->data->competitor);
            $fields[]="competitor_sku=" . fn_escape($this->data->competitor_sku);
            $fields[]="update_timestamp=" . fn_escape($this->map->update_timestamp,FALSE);
	        $query=array("replace inventory_xref set");
			$query[]=implode(", \n", $fields);
	        $this->query($query);
		}
    }
}
class inventory_xref_data_class {
	var $sku;
	var $competitor;
	var $competitor_sku;
}
class inventory_xref_constant_class {

}

/*
rename table `inventory_xref` to `xinventory_xref`;

insert into inventory_xref
select sku, '', competitor_sku,0 from xinventory_xref
where sku <> competitor_sku
group by sku, competitor_sku

CREATE TABLE `inventory_xref` (
  `sku` varchar(60) NOT NULL,
  `competitor` varchar(60) NOT NULL,
  `competitor_sku` varchar(60) NOT NULL,
  `update_timestamp` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`sku`,`competitor`,`competitor_sku`) USING BTREE,
  FULLTEXT KEY `search_key` (`competitor_sku`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Inventory cross reference (07/14/2011)'
*/
?>