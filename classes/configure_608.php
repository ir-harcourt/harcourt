<?php
/*
HGPSPM-1.01P-1.00P-10.00-10.00-3.10P-3.00-10.00-6.00
*/
class configure_608_class extends configure_class {
    var $data;
    var $parse_results;
    var $pricing;
	var $constant;
    var $query;
	function scs_table_version() {
		$results=array();
        $results['07/28/2022']="New implemetation";
        $results['01/15/2020']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
    function __construct() {
    	$this->data=new configure_608_data_class();
 		$this->parse_results=new configure_parse_results();
    	$this->constant=new configure_608_constant_class();
		$this->meta=new configure_module_meta_class( key($this->scs_table_version() ) );
        $options=array();
 		$this->pricing=new configure_pricing_class();;
 		$this->pricing->field("a_price","A Diameter",$this->constant->a_break,array() );
 		$this->pricing->field("b_price","B Diameter",$this->constant->b_break,array() );
 		$this->pricing->field("c_price","C Length",$this->constant->c_break,array() );
 		$this->pricing->field("d_price","D Length",$this->constant->d_break,array() );
 		$this->pricing->field("g_price","G Diameter",$this->constant->g_break,array() );
 		$this->pricing->field("h_price","H Diameter",$this->constant->h_break,array() );
 		$this->pricing->field("k_price","K Length",$this->constant->k_break,array() );
 		$this->pricing->field("l_price","L Length",$this->constant->l_break,array() );
 		$this->pricing->field("ghd_price","GHD",$this->constant->ghd_break,array() );
    }
    function parse_sku($sku,$length) {
        $this->meta->sku=preg_split("/-/",$sku);
        $this->data->series=$this->parse_field("Series");
        $this->data->_a_data=$this->parse_field("A Diameter/Tolerance");
        $this->data->a_diameter=substr($this->data->_a_data,0,-1);
        $this->data->a_tolerance=substr($this->data->_a_data,-1);
        $this->data->_b_data=$this->parse_field("B Diameter/Tolerance");
        $this->data->b_diameter=substr($this->data->_b_data,0,-1);
        $this->data->b_tolerance=substr($this->data->_b_data,-1);
        $this->data->c_length=$this->parse_field("C Length");
        $this->data->d_length=$this->parse_field("D Length");

        $this->data->_g_data=$this->parse_field("G Diameter/Tolerance");
        $this->data->g_diameter=substr($this->data->_g_data,0,-1);
        $this->data->g_tolerance=substr($this->data->_g_data,-1);
        $this->data->h_diameter=$this->parse_field("H Diameter");
        $this->data->k_length=$this->parse_field("K Length");
        $this->data->l_length=$this->parse_field("L Length");

        $this->data->_a_diameter=floatval($this->data->a_diameter);
        $this->data->_b_diameter=floatval($this->data->b_diameter);
        $this->data->_c_length=floatval($this->data->c_length);
        $this->data->_d_length=floatval($this->data->d_length);

        $this->data->_g_diameter=floatval($this->data->g_diameter);
        $this->data->_h_diameter=floatval($this->data->h_diameter);
        $this->data->_k_length=floatval($this->data->k_length);
        $this->data->_l_length=floatval($this->data->l_length);

// A Price Break
		switch (TRUE) {
          case ($this->data->_a_diameter <= 5):
          	$this->data->a_break=0;
            break;
          case ($this->data->_a_diameter <= 10):
          	$this->data->a_break=1;
            break;
          case ($this->data->_a_diameter <= 15):
          	$this->data->a_break=2;
            break;
          case ($this->data->_a_diameter <= 20):
          	$this->data->a_break=3;
            break;
          default:
          	$this->data->a_break=4;
		}

// B Price Break
		switch (TRUE) {
          case ($this->data->_b_diameter <= 5):
          	$this->data->b_break=0;
            break;
          case ($this->data->_b_diameter <= 10):
          	$this->data->b_break=1;
            break;
          case ($this->data->_b_diameter <= 15):
          	$this->data->b_break=2;
            break;
          case ($this->data->_b_diameter <= 20):
          	$this->data->b_break=3;
            break;
          default:
          	$this->data->b_break=4;
		}

// C Price Break
		switch (TRUE) {
          case ($this->data->_c_length <= 20):
          	$this->data->c_break=0;
            break;
          case ($this->data->_c_length <= 30):
          	$this->data->c_break=1;
            break;
          case ($this->data->_c_length <= 40):
          	$this->data->c_break=2;
            break;
          case ($this->data->_c_length <= 50):
          	$this->data->c_break=3;
            break;
          default:
          	$this->data->c_break=4;
		}

// D Price  Break
		switch (TRUE) {
          case ($this->data->_d_length <= 5):
          	$this->data->d_break=0;
            break;
          case ($this->data->_d_length <= 10):
          	$this->data->d_break=1;
            break;
          case ($this->data->_d_length <= 15):
          	$this->data->d_break=2;
            break;
          case ($this->data->_d_length <= 20):
          	$this->data->d_break=3;
            break;
          default:
          	$this->data->d_break=4;
		}

// G Price Break
		switch (TRUE) {
          case ($this->data->_g_diameter <= 5):
          	$this->data->g_break=0;
            break;
          case ($this->data->_g_diameter <= 10):
          	$this->data->g_break=1;
            break;
          case ($this->data->_g_diameter <= 15):
          	$this->data->g_break=2;
            break;
          case ($this->data->_g_diameter <= 20):
          	$this->data->g_break=3;
            break;
          default:
          	$this->data->g_break=4;
		}

// H Price Break
		switch (TRUE) {
          case ($this->data->_h_diameter <= 5):
          	$this->data->h_break=0;
            break;
          case ($this->data->_h_diameter <= 10):
          	$this->data->h_break=1;
            break;
          case ($this->data->_h_diameter <= 15):
          	$this->data->h_break=2;
            break;
          case ($this->data->_h_diameter <= 20):
          	$this->data->h_break=3;
            break;
          default:
          	$this->data->h_break=4;
		}

// K Price Break
		switch (TRUE) {
          case ($this->data->_k_length <= 20):
          	$this->data->k_break=0;
            break;
          case ($this->data->_k_length <= 30):
          	$this->data->k_break=1;
            break;
          case ($this->data->_k_length <= 40):
          	$this->data->k_break=2;
            break;
          case ($this->data->_k_length <= 50):
          	$this->data->k_break=3;
            break;
          default:
          	$this->data->k_break=4;
		}

// L Price Break
		switch (TRUE) {
          case ($this->data->_l_length <= 20):
          	$this->data->l_break=0;
            break;
          case ($this->data->_l_length <= 30):
          	$this->data->l_break=1;
            break;
          case ($this->data->_l_length <= 40):
          	$this->data->l_break=2;
            break;
          case ($this->data->_l_length <= 50):
          	$this->data->l_break=3;
            break;
          default:
          	$this->data->l_break=4;
		}

// GHD Price Break
		switch (TRUE) {
          case ($this->data->_a_diameter <= 18):
          	$this->data->ghd_break=0;
            break;
          default:
          	$this->data->ghd_break=1;
		}
	}
    function verify() {
		$this->configure_verify("Series");
		$this->configure_verify("A Diameter","a_diameter");
		$this->configure_verify("A Tolerance","a_tolerance");
		$this->configure_verify("B Diameter","b_diameter");
		$this->configure_verify("B Tolerance","b_tolerance");
		$this->configure_verify("C Length","c_length");
		$this->configure_verify("D Length","d_length");
		$this->configure_verify("G Diameter","g_diameter");
		$this->configure_verify("G Tolerance","g_tolerance");
		$this->configure_verify("H Diameter","h_diameter");
		$this->configure_verify("K Length","k_length");
		$this->configure_verify("L Length","l_length");

		switch (TRUE) {
		  case ( ($this->data->_a_diameter < 1) || ($this->data->_a_diameter > 35) ):
          	$this->meta->error="A Diameter allowed values: 1.00mm -35.00mm";
            break;
		  case ( ($this->data->_b_diameter < 1) || ($this->data->_b_diameter > 35) ):
          	$this->meta->error="B Diameter allowed values: 1.00mm - 35.00mm";
            break;
          case ( ($this->data->_c_length < 10) || ($this->data->_c_length > 60) ):
          	$this->meta->error="C Length allowed values: 10.00mm - 60.00mm";
            break;
          case ( ($this->data->_d_length < 10) || ($this->data->_d_length > 60) ):
          	$this->meta->error="D Length allowed values: 10.00mm - 60.00mm";
            break;
		  case ( ($this->data->_g_diameter < 1) || ($this->data->_g_diameter > 35) ):
          	$this->meta->error="G Diameter allowed values: 1.00mm - 35.00mm";
            break;
		  case ( ($this->data->_h_diameter < 1) || ($this->data->_h_diameter > 35) ):
          	$this->meta->error="H Diameter allowed values: 1.00mm - 35.00mm";
            break;
          case ( ($this->data->_k_length < 1) || ($this->data->_k_length > 60) ):
          	$this->meta->error="K Length allowed values: 1.00mm - 60.00mm";
            break;
          case ( ($this->data->_l_length < 1) || ($this->data->_l_length > 60) ):
          	$this->meta->error="L Length allowed values: 1.00mm - 60.00mm";
            break;
		}

		return $this->meta->error;
    }
    function pricing() {
/* A Pricing */
    	$price=array();
        for ($i=0; $i < sizeof($this->pricing->item['a_price']->price_name); $i++) {
        	$price[$i]=0;
        }
        $price[ $this->data->a_break]=1;
    	$this->pricing->price("a_price",$this->constant->a_break[$this->data->a_break],$price);

/* B Pricing */
    	$price=array();
        for ($i=0; $i < sizeof($this->pricing->item['b_price']->price_name); $i++) {
        	$price[$i]=0;
        }
        $price[ $this->data->b_break]=1;
    	$this->pricing->price("b_price",$this->constant->b_break[$this->data->b_break],$price);

/* C Pricing */
    	$price=array();
        for ($i=0; $i < sizeof($this->pricing->item['c_price']->price_name); $i++) {
        	$price[$i]=0;
        }
        $price[ $this->data->c_break]=1;
    	$this->pricing->price("c_price",$this->constant->c_break[$this->data->c_break],$price);

/* D Pricing */
    	$price=array();
        for ($i=0; $i < sizeof($this->pricing->item['d_price']->price_name); $i++) {
        	$price[$i]=0;
        }
        $price[ $this->data->d_break]=1;
    	$this->pricing->price("d_price",$this->constant->d_break[$this->data->d_break],$price);

/* G Pricing */
    	$price=array();
        for ($i=0; $i < sizeof($this->pricing->item['g_price']->price_name); $i++) {
        	$price[$i]=0;
        }
        $price[$this->data->g_break]=1;
    	$this->pricing->price("g_price",$this->constant->g_break[$this->data->g_break],$price);

/* H Pricing */
    	$price=array();
        for ($i=0; $i < sizeof($this->pricing->item['h_price']->price_name); $i++) {
        	$price[$i]=0;
        }
        $price[$this->data->h_break]=1;
    	$this->pricing->price("h_price",$this->constant->h_break[$this->data->h_break],$price);

/* K Pricing */
    	$price=array();
        for ($i=0; $i < sizeof($this->pricing->item['k_price']->price_name); $i++) {
        	$price[$i]=0;
        }
        $price[ $this->data->k_break]=1;
    	$this->pricing->price("k_price",$this->constant->k_break[$this->data->k_break],$price);

/* L Pricing */
    	$price=array();
        for ($i=0; $i < sizeof($this->pricing->item['l_price']->price_name); $i++) {
        	$price[$i]=0;
        }
        $price[ $this->data->l_break]=1;
    	$this->pricing->price("l_price",$this->constant->l_break[$this->data->l_break],$price);

/* GHD Pricing */
    	$price=array();
        for ($i=0; $i < sizeof($this->pricing->item['ghd_price']->price_name); $i++) {
        	$price[$i]=0;
        }
        $price[ $this->data->ghd_break]=1;
    	$this->pricing->price("ghd_price",$this->constant->ghd_break[$this->data->ghd_break],$price);
	}
}
class configure_608_data_class {
	var $series;
    var $a_diameter;
    var $a_tolerance;
    var $b_diameter;
    var $b_tolerance;
    var $c_length;
    var $d_length;
    var $g_diameter;
    var $g_tolerance;
	var $h_diameter;
    var $k_length;
    var $l_length;

// internal variables
	var $_a_data;
    var $_a_diameter;
	var $_b_data;
    var $_b_diameter;
    var $_c_length;
    var $_d_length;
	var $_g_data;
    var $_g_diameter;
    var $_h_diameter;
    var $_k_length;
    var $_l_length;

// pricing variables
	var $a_break=0;
	var $b_break=0;
	var $c_break=0;
	var $d_break=0;
    var $g_break=0;
    var $h_break=0;
    var $k_break=0;
    var $l_break=0;
    var $ghd_break=0;

}
class configure_608_constant_class {
// Base SKU
	var $series=array();
    var $a_diameter=array();
    var $a_tolerance=array();
    var $b_diameter=array();
    var $b_tolerance=array();
    var $c_length=array();
    var $d_length=array();

    var $g_diameter=array();
    var $g_tolerance=array();
    var $h_diameter=array();
    var $k_length=array();
    var $l_length=array();

// Price breaks
	var $a_break=array();
	var $b_break=array();
    var $c_break=array();
    var $d_break=array();
    var $g_break=array();
    var $h_break=array();
    var $k_break=array();
    var $l_break=array();
    var $ghd_break=array();

    function __construct() {
		$this->series['HGPSPM']="Step/Pilot Stab Pin Assembly";
        $this->a_tolerance['P']="-.000/+.012";
        $this->a_tolerance['M']="-.012/+.000";

        $this->b_tolerance['P']="-.000/+.012";
        $this->b_tolerance['M']="-.012/+.000";

	    $this->a_break[]="1.00mm to 5.00mm";
	    $this->a_break[]="5.01mm to 10.00mm";
	    $this->a_break[]="10.01mm to 15.00mm";
	    $this->a_break[]="15.01mm to 20.00mm";
	    $this->a_break[]="20.01mm to 24.99mm";

	    $this->b_break[]="1.00mm to 5.00mm";
	    $this->b_break[]="5.01mm to 10.00mm";
	    $this->b_break[]="10.01mm to 15.00mm";
	    $this->b_break[]="15.01mm to 20.00mm";
	    $this->b_break[]="20.01mm to 24.99mm";

	    $this->c_break[]="10mm to 20mm";
	    $this->c_break[]="21mm to 30mm";
	    $this->c_break[]="31mm to 40mm";
	    $this->c_break[]="41mm to 50mm";
	    $this->c_break[]="51mm to 60mm";

	    $this->d_break[]="10mm to 20mm";
	    $this->d_break[]="21mm to 30mm";
	    $this->d_break[]="31mm to 40mm";
	    $this->d_break[]="41mm to 50mm";
	    $this->d_break[]="51mm to 60mm";

        $this->g_tolerance['P']="-.000/+.012";
        $this->g_tolerance['M']="-.012/+.000";

	    $this->g_break[]="1.00mm to 5.00mm";
	    $this->g_break[]="5.01mm to 10.00mm";
	    $this->g_break[]="10.01mm to 15.00mm";
	    $this->g_break[]="15.01mm to 20.00mm";
	    $this->g_break[]="20.01mm to 24.99mm";

	    $this->h_break[]="1.00mm to 5.00mm";
	    $this->h_break[]="5.01mm to 10.00mm";
	    $this->h_break[]="10.01mm to 15.00mm";
	    $this->h_break[]="15.01mm to 20.00mm";
	    $this->h_break[]="20.01mm to 24.99mm";

	    $this->k_break[]="10mm to 20mm";
	    $this->k_break[]="21mm to 30mm";
	    $this->k_break[]="31mm to 40mm";
	    $this->k_break[]="41mm to 50mm";
	    $this->k_break[]="51mm to 60mm";

	    $this->l_break[]="10mm to 20mm";
	    $this->l_break[]="21mm to 30mm";
	    $this->l_break[]="31mm to 40mm";
	    $this->l_break[]="41mm to 50mm";
	    $this->l_break[]="51mm to 60mm";

	    $this->ghd_break[]="GHD-3-BLACK";
	    $this->ghd_break[]="GHD-4-BLACK";

	}
    function load($data) {
		$this->a_diameter($data);
		$this->b_diameter($data);
		$this->c_length($data);
		$this->d_length($data);
		$this->g_diameter($data);
		$this->h_diameter($data);
		$this->k_length($data);
		$this->l_length($data);
    }
    function a_diameter($data) {
    	$size=number_format($data->_a_diameter,2,".","");
		$this->a_diameter[$size]=new configure_options_class("{$size} mm");
    }
    function b_diameter($data) {
    	$size=number_format($data->_b_diameter,2,".","");
		$this->b_diameter[$size]=new configure_options_class("{$size} mm");
    }
    function c_length($data) {
    	$size=number_format($data->_c_length,2,".","");
		$this->c_length[$size]=new configure_options_class("{$size} mm");
    }
    function d_length($data) {
    	$size=number_format($data->_d_length,2,".","");
		$this->d_length[$size]=new configure_options_class("{$size} mm");
    }
    function g_diameter($data) {
    	$size=number_format($data->_g_diameter,2,".","");
		$size_bug=( ($size === $data->g_diameter) ?  $size : number_format($data->_g_diameter,3,".",""));  // Fixes PS bug
		$this->g_diameter[$size_bug]=new configure_options_class("{$size} mm");
    }
    function h_diameter($data) {
    	$size=number_format($data->_h_diameter,2,".","");
		$this->h_diameter[$size]=new configure_options_class("{$size} mm");
    }
    function k_length($data) {
    	$size=number_format($data->_k_length,2,".","");
		$this->k_length[$size]=new configure_options_class("{$size} mm");
    }
    function l_length($data) {
    	$size=number_format($data->_l_length,2,".","");
		$this->l_length[$size]=new configure_options_class("{$size} mm");
    }
}
?>