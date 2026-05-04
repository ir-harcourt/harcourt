<?php
$dashboard=new dashboard_class();
class dashboard_class {
	var $module;
    var $results=array();
    function __construct() {
        $this->module=new stdClass();
    }
    function module($module,$items) {
        $this->module->$module=$items;
    }
    function output() {
	global $database;
		foreach ($this->module as $module => $module_content) {
			if (!isset($database->registry->dashboard->$module)) continue;
	        foreach ($module_content->item as $item => $item_content) {
            	switch (TRUE) {
                  case (!array_key_exists($item,$database->registry->dashboard->$module)):
                  case (!method_exists($module_content,$item)):
                  case (!$database->user->access($database->registry->dashboard->{$module}[$item],FALSE)):
					continue;
				  default:
					$item_registry=array();
	                foreach ($item_content->fields as $field => $field_name) {
	                    if (!array_key_exists("{$item}_{$field}",$database->registry->dashboard->$module)) {
	                        $item_registry[$field]="";
	                      } else {
	                        $item_registry[$field]=$database->registry->dashboard->{$module}["{$item}_{$field}"];
	                    }
	                }
					$results=$module_content->$item($item_registry);
					if ($results) $this->results[$module][$item]=$results;
				}
	        }
         }
        if (!sizeof($this->results)) return;
//Allow for individual sorting
		foreach ($this->results as $module_results) {
			foreach ($module_results as $item_results) {
				print $item_results;
            }
        }
    }
}
class dashboard_item_class {
	var $name;
    var $access;
    var $fields=array();
    var $options=array();
    function __construct($name,$access,$fields=array(),$options=array()) {
		$this->name=$name;
        $this->access=$access;
        $this->fields=$fields;
        $this->options=$options;
    }
}
?>