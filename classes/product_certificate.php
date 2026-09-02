<?php
$database->product_certificate = new product_certificate_class();
class product_certificate_class extends database_class {
	function scs_table_version() {
		$results=array();
        $results['08/05/2026']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
	function __construct() {
    	$this->data=new product_certificate_data_class();
    	$this->meta=new database_meta_class();
        $this->fetch=array();
        $this->constant=new product_certificate_constant_class();
    }
    function fetch($fetch=FALSE) {
        if ($fetch) $this->fetch=$this->fetch_array();
	    $this->data=new product_certificate_data_class();
	    $this->data->id=$this->fetch['id'];
	    $this->data->sku=$this->fetch['sku'];
	    $this->data->manufacturing_date=$this->fetch['manufacturing_date'];
	    $this->data->testing_date=$this->fetch['testing_date'];
	    $this->data->product_family=$this->fetch['product_family'];
	    $this->data->product_description=$this->fetch['product_description'];
	    $this->data->product_weight=$this->fetch['product_weight'];
	    $this->data->rated_load=$this->fetch['rated_load'];
	    $this->data->conversion=$this->fetch['conversion'];
	    $this->data->test_machine=$this->fetch['test_machine'];
	    $this->data->test_machine_calibration_date=$this->fetch['test_machine_calibration_date'];
	    $this->data->update_timestamp=$this->fetch['update_timestamp'];
    }
    function read($id,$field="id") {
	    $query=array("select * from product_certificate");
	    $query[]="where {$field}=" . fn_escape($id);
        $this->query($query);
        if ($this->meta->rows) {
        	$this->fetch(TRUE);
			$this->free_result();
		  } else {
			$this->data=new product_certificate_data_class();
		}
    }
    function delete($id) {
	    $query=array("delete from product_certificate where");
        $query[]="id=" . fn_escape($id);
		$this->query($query);
    }
    function export_query() {
	    $query=array("select * from product_certificate");
        $query[]="order by sku, testing_date";
        return $query;
    }
    function map($options=array()) {
        $this->map=new table_map_class($options);
	    $this->map->item("sku",array("name"=>"Part Number","type"=>"uppercase","required"=>TRUE));
	    $this->map->item("manufacturing_date",array("name"=>"Manufacturing Date","type"=>"date"));
	    $this->map->item("testing_date",array("name"=>"Testing Date","type"=>"date","required"=>TRUE));
	    $this->map->item("product_family",array("name"=>"Product Family","type"=>"uppercase"));
	    $this->map->item("product_description",array("name"=>"Product Description"));
	    $this->map->item("product_weight",array("name"=>"Product Weight"));
	    $this->map->item("rated_load",array("name"=>"Rated Load"));
	    $this->map->item("conversion",array("name"=>"Conversion"));
	    $this->map->item("test_machine",array("name"=>"Test Machine"));
	    $this->map->item("test_machine_calibration_date",array("name"=>"Test Machine Calibration Date","type"=>"date"));
        $this->map->update_timestamp=strtotime("now");
    }
    function import_update($data) {
		$this->data=new product_certificate_data_class();
        $this->data->sku=$data['sku'];
        $this->data->manufacturing_date=$data['manufacturing_date'];
        $this->data->testing_date=$data['testing_date'];
        $this->data->product_family=$data['product_family'];
        $this->data->product_description=$data['product_description'];
        $this->data->product_weight=$data['product_weight'];
        $this->data->rated_load=$data['rated_load'];
        $this->data->conversion=$data['conversion'];
        $this->data->test_machine=$data['test_machine'];
        $this->data->test_machine_calibration_date=$data['test_machine_calibration_date'];
        if (!$this->data->sku) $this->map->error['sku']="Cannot be blank";
        if ($this->date_value($this->data->testing_date)=="NULL") $this->map->error['testing_date']="Cannot be blank";
    	if ( ($this->map->update) && (!sizeof($this->map->error)) ) {
			$fields=array();
            $fields[]="sku=" . fn_escape($this->data->sku);
            $fields[]="manufacturing_date=" . $this->date_value($this->data->manufacturing_date);
            $fields[]="testing_date=" . $this->date_value($this->data->testing_date);
            $fields[]="product_family=" . fn_escape($this->data->product_family);
            $fields[]="product_description=" . fn_escape($this->data->product_description);
            $fields[]="product_weight=" . fn_escape($this->data->product_weight);
            $fields[]="rated_load=" . fn_escape($this->data->rated_load);
            $fields[]="conversion=" . fn_escape($this->data->conversion);
            $fields[]="test_machine=" . fn_escape($this->data->test_machine);
            $fields[]="test_machine_calibration_date=" . $this->date_value($this->data->test_machine_calibration_date);
            $fields[]="update_timestamp=" . fn_escape($this->map->update_timestamp,FALSE);
	        $query=array("insert into product_certificate set");
			$query[]=implode(", \n", $fields);
	        $this->query($query);
		}
    }
    function date_value($value) {
    	return (in_array($value,array("0000/00/00","0000-00-00",""))) ? "NULL" : fn_escape($value,"date");
    }
}
class product_certificate_data_class {
	var $id=0;
	var $sku;
	var $manufacturing_date;
	var $testing_date;
	var $product_family;
	var $product_description;
	var $product_weight;
	var $rated_load;
	var $conversion;
	var $test_machine;
	var $test_machine_calibration_date;
	var $update_timestamp=0;
}
class product_certificate_constant_class {

}
?>
