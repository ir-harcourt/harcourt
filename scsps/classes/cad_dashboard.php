<?php
$dashboard->module("cad",new cad_dashboard_class() );
class cad_dashboard_class {
	var $name;
	var $item=array();
    function __construct() {
		$this->name="CAD/3D Log";
		$this->item['cad']=new dashboard_item_class("CAD Download Summary","Executive",array("days"=>"Past # days"));
		$this->item['view']=new dashboard_item_class("3D Preview Summary","Executive",array("days"=>"Past # days"));
    }
    function cad() {
    global $database, $forms;
		return "";
    }
    function view() {
    global $database, $forms;
		return "";
    }
}
?>