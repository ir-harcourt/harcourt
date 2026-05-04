<?php
$database->reporting=new reporting_class();
class reporting_class extends database_class {
	var $report;
    var $first_pass=FALSE;
    var $trace=array();
	function scs_table_version() {
		$results=array();
        $results['06/05/2023']="Add subcategory->currency";
        $results['11/28/2022']="Add Lead Time";
        $results['10/28/2022']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
	function __construct() {
    	$this->data();
        $this->fetch=array();
    	$this->meta=new database_meta_class();
        $this->constant();
    }
    function fetch() {
		$this->data();
        switch ($this->report) {
          case "pricebook":
          	$this->data->sku=$this->fetch['sku'];
          	$this->data->category_id=$this->fetch['category_id'];
          	$this->data->category_name=$this->fetch['category_name'];
          	$this->data->subcategory_id=$this->fetch['subcategory_id'];
          	$this->data->subcategory_name=$this->fetch['subcategory_name'];
          	$this->data->leadtime_option=$this->fetch['leadtime_option'];
          	$this->data->leadtime_text=$this->fetch['leadtime_text'];
			if ($this->fetch['pricing']) $this->data->pricing=unserialize($this->fetch['pricing']);
			break;
		}
    }
    function data() {
    	$obj=new stdClass;
        switch ($this->report) {
          case "pricebook":
	        $obj->sku="";
	        $obj->category_id=0;
	        $obj->category_name="";
	        $obj->subcategory_id=0;
	        $obj->subcategory_name="";
          	$this->data->leadtime_option="";
          	$this->data->leadtime_text="";
	        $obj->pricing=array();
    		break;
        }
        $this->data=$obj;
    }
    function constant() {
    	$obj=new stdClass;
        switch ($this->report) {
          case "pricebook":
            break;
		}
        $this->constant=$obj;
    }
    function map($options=array()) {
    global $database;
    	$this->trace[]=__FUNCTION__;
        $this->map=new table_map_class($options);
        $this->report=$options['report'];
	    $this->map->item("sku",array("name"=>"Harcourt SKU","type"=>"uppercase","required"=>TRUE,"width"=>30));
	    $this->map->item("subcategory_name",array("name"=>"Subcategory","width"=>60));
		$this->map->item("price0",array("name"=>"Base Price","type"=>"decimal:2","blank"=>FALSE,"width"=>12));
        if ($this->map->options['qty_break']) {
	        for ($i=1; $i < 10; $i++) {
                $this->map->item("qty_break{$i}",array("name"=>"Qty Break #{$i}","type"=>"decimal:0","width"=>14));
                $this->map->item("price{$i}",array("name"=>"Price #{$i}","type"=>"decimal:2","width"=>12));
            }
		}
	    $this->map->item("leadtime",array("name"=>"Lead Time","width"=>20));
        if ($this->first_pass) return;
        $this->first_pass=TRUE;
	    $query=array();
	    $query[]="CREATE TEMPORARY TABLE `reporting` (";
	    $query[]="`sku` varchar(80) NOT NULL,";
	    $query[]="`category_id` int(11) DEFAULT '0',";
	    $query[]="`category_name` varchar(60) DEFAULT NULL,";
	    $query[]="`subcategory_id` int(11) DEFAULT '0',";
	    $query[]="`subcategory_name` varchar(80) DEFAULT NULL,";
	    $query[]="`leadtime_option` varchar(20) NOT NULL DEFAULT '',";
	    $query[]="`leadtime_text` mediumtext,";
	    $query[]="`pricing` mediumtext,";
	    $query[]="PRIMARY KEY (`sku`)";
	    $query[]=") ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Reporting (11/28/2022)'";
		$this->query($query);

	    $query_where=array();
	    $query_where[]=new query_where("inventory.subcategory_id","in",$this->map->options['subcategory']);
	    $query_where[]=new query_where("category.active","=",1);
	    $query_where[]=new query_where("subcategory.status","=","Active");
	    $query_where[]=new query_where("subcategory.output","<>","smart");
		$query_where[]=new query_where("inventory.pricing","not in",array("","a:0:{}"));

	    $query=array();
        $query[]="insert into reporting";
        $query[]="select";
	    $query[]="inventory.sku,";
        $query[]="category.id,";
        $query[]="category.name,";
        $query[]="subcategory.id,";
        $query[]="subcategory.name,";
	    $query[]="inventory.leadtime_option,";
	    $query[]="inventory.leadtime_text,";
	    $query[]="inventory.pricing";
        $query[]="from inventory";
        $query[]="left join subcategory on subcategory.id=inventory.subcategory_id";
        $query[]="left join category on category.id=inventory.category_id";
        $query[]=trim($this->where($query_where));
        $database->temp->query($query);
	}
    function map_multiple($options,$code) {
    	$this->trace[]=__FUNCTION__ . " code: {$code}";
    	$options['category_id']=$code;
        $this->map($options);
    }
    function worksheet_name($code) {
	global $database;
    	$this->trace[]=__FUNCTION__ . " code: {$code}";
    	$database->category->read($code);
        return $database->category->data->name;
    }
    function export_query() {
    global $database;
		$database->subcategory->data->id=0;
	    $query_where=array();
	    $query=array("select * from reporting");
        $query[]="where category_id=" . fn_escape($this->map->options['category_id']);
        $query[]=trim($this->where($query_where));
        $query[]="order by subcategory_name,sku";
		return $query;
    }
    function export_data() {
    global $database;
		if ($this->data->subcategory_id != $database->subcategory->data->id) $database->subcategory->read($this->data->subcategory_id);
        $adder_pct=(array_key_exists($this->map->options['currency_code'],$database->subcategory->data->currency)) ? $database->subcategory->data->currency[$this->map->options['currency_code']] : $this->map->options['adder_pct'];
		foreach ($this->data->pricing as $i => $obj) {
            $price="price{$i}";
            $qty_break="qty_break{$i}";
			$this->data->$qty_break=$obj->qty_break;
            $this->data->$price=round($obj->price * $this->map->options['multiplier'] * $this->map->options['exchange_rate'] * (1 + $adder_pct),2);
		}
    	switch (TRUE) {
          case ($this->data->leadtime_option == "In Stock"):
          	$this->data->leadtime=$this->data->leadtime_option;
            break;
          case ($this->data->leadtime_option == "Subcategory"):
          	$this->data->leadtime=$database->subcategory->leadtime();
            break;
          case (strlen($this->data->leadtime_text)):
          	$this->data->leadtime=$this->data->leadtime_text . " Week(s)";
		}
	}
}
?>