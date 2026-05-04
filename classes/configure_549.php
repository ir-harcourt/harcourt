<?php
/*
HLSM-040A-0P-0600-3
HLSM-040A-1P-1200-2
HLSM-040A-2P-1500-1
*/
class configure_549_class extends configure_class {
    var $data;
    var $parse_results;
    var $pricing;
	var $constant;
    var $query;
	function scs_table_version() {
		$results=array();
        $results['07/28/2022']="New implemetation";         
        $results['03/02/2020']="Add carriage #4";
        $results['08/16/2019']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
    function __construct() {
    	$this->data=new configure_549_data_class();
 		$this->parse_results=new configure_parse_results();
    	$this->constant=new configure_549_constant_class();
		$this->meta=new configure_module_meta_class( key($this->scs_table_version() ) );
 		$this->pricing=new configure_pricing_class( array("rail_length"=>"fixed:4") );
		$this->pricing->field("carriage_length","Carriage Length",array("Per Carriage"),array("carriage_length"));
		$this->pricing->field("rail_length","Rail Length",array("Per MM Rail"),array());
    }
    function parse_sku($sku,$length) {
        $this->meta->sku=preg_split("/-/",$sku);
        $this->data->series=$this->parse_field("Series");
        $this->data->constant1=$this->parse_field("Constant");
        $this->data->_carriage_clearance=$this->parse_field("Carriage Length/Clearance");
        $this->data->rail_length=$this->parse_field("Rail Length");
        $this->data->carriages=$this->parse_field("Carriages");
// Parsed fields
        $this->data->carriage_length=substr($this->data->_carriage_clearance,0,1);
        $this->data->clearance=substr($this->data->_carriage_clearance,1);
        $this->data->_rail_length=floatval($this->data->rail_length);
	}
    function verify() {
		$this->configure_verify("Series");
		$this->configure_verify("","constant1");
		$this->configure_verify("Carriage Length","carriage_length");
		$this->configure_verify("Clearance");
		$this->configure_verify("Rail Length","rail_length");
		$this->configure_verify("Carriages");
        switch (TRUE) {
          case ($this->meta->error):
          	break;
          case ( ($this->data->_rail_length < 100) || ($this->data->_rail_length > 2750) ):
          	$this->meta->error="Rail length must be from 100-2750mm";
          	break;
		}
		return $this->meta->error;
    }
    function pricing() {
    	$text=( ($this->data->carriages == 1) ? "" : "QUANTITY0 @ PRICE0");
    	$this->pricing->price("carriage_length",$text,array($this->data->carriages),array("name"=>"Carriage (" . $this->constant->carriage_length[$this->data->carriage_length]->description . ")"));
        $this->pricing->price("rail_length","QUANTITY0mm @ PRICE0",array($this->data->_rail_length),array("name"=>"Rail (" . $this->data->_rail_length . "mm)"));
	}
}
class configure_549_data_class {
	var $series;
    var $constant1;
    var $carriage_length;
    var $clearance;
    var $rail_length;
    var $carriages;

// internal variables
	var $_carriage_clearance;
	var $_rail_length=0;
}
class configure_549_constant_class {
// Base SKU
	var $series=array();
    var $constant1=array();
    var $carriage_length=array();
    var $clearance=array();
    var $rail_length=array();
    var $carriages=array();

    function __construct() {
		$this->series['HLSM']="Linear Slides";

        $this->constant1['040A']=new configure_options_class("");

	    $this->carriage_length['0']=new configure_options_class('100mm');
	    $this->carriage_length['1']=new configure_options_class('150mm');
	    $this->carriage_length['2']=new configure_options_class('200mm');

	    $this->clearance['P']=new configure_options_class('Precision Running Clearance');
	    $this->clearance['C']=new configure_options_class('Compensated Running Clearance');

	    $this->carriages['1']=new configure_options_class('1 Carriage');
	    $this->carriages['2']=new configure_options_class('2 Carriages');
	    $this->carriages['3']=new configure_options_class('3 Carriages');
	    $this->carriages['4']=new configure_options_class('4 Carriages');
	}
    function load($data) {
		$this->rail_length($data);
    }
    function rail_length($data) {
		$this->rail_length[substr("0000" . $data->_rail_length,-4)]=new configure_options_class( number_format($data->_rail_length,0,".","") . "mm");
    }
}
?>