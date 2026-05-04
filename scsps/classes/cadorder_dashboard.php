<?php
$dashboard->module("cadorder",new cadcart_dashboard_class() );
class cadcart_dashboard_class {
	var $name;
	var $item=array();
    function __construct() {
		$this->name="CAD Orders";
		$this->item['expire']=new dashboard_item_class("CAD Cart Expiring without Download","",array("days"=>"Unopened file removed in # days"));
		$this->item['recent']=new dashboard_item_class("Recent CAD Cart Requests","Executive",array("days"=>"Past # days"));
    }
}
?>