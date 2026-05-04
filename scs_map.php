<?php
class map_table_class {
	var $field=array();
    var $alternate_name=array();
    var $line_count=0;
    var $table_errors=array();
    var $line_errors=array();
    var $update=FALSE;
    var $results=array();
    var $groups=array();
    var $update_timestamp;
	function export_header() {
    	$results=array();
    	foreach ($this->field as $key => $map) {
        	if ($map->export) $results[]=$key;
        }
        return $results;
    }
    function export_data($table) {
		foreach ($table->export as $key => $value) {
			if (!$this->field[$key]->export) unset($table->export[$key]);
        }

		foreach ($this->field as $key => $map) {
        	switch (TRUE) {
              case (!$map->export):
              	break;
              default:
				$results[]=$map->export($table->fetch[$key]);
			}
		}
    	return $table->export;
    }
    function import_line($data,$import_data=FALSE) {
        $results=array();
		if (!isset($this->headers)) {
	        $this->headers=TRUE;
            foreach ($this->field as $key => $map) {
            	if ($map->alternate_name) $this->alternate_name[$map->alternate_name]=$key;
            }
	        foreach ($data as $column => $name) {
				if (array_key_exists(strtolower($name),$this->alternate_name)) {
                	$field=$this->alternate_name[strtolower($name)];
				  } else {
					$field=strtolower($name);
                }
	            switch (TRUE) {
	              case (!array_key_exists($field,$this->field)):
	                break;
	              case (!isset($this->field[$field]->column)):
	                $this->field[$field]->column=$column;
	                break;
	               default:
	                $this->field[$field]->duplicate=TRUE;
	            }
	        }
	        foreach ($this->field as $field => $map) {
	            switch (TRUE) {
	              case ($map->required=="no"):
	              case ($map->required=="external"):
	                break;
	              case (!isset($map->column)):
	                $this->table_errors[$field]="Missing";
	                break;
	              case ($map->duplicate):
	                $this->table_errors[$field]="Duplicate field";
	                break;
	            }
                if (($map->group) && (isset($map->column))) $this->groups[$map->group]=TRUE;
	        }
		  } else {
			$this->line_count++;
          	if ($import_data) {
	            foreach ($this->field as $field => $map) {
	                switch (TRUE) {
	                  case ($map->required=="external"):
	                    break;
	                  case (isset($map->column)):
	                    $results[$field]=$map->import($data[$map->column]);
                        break;
					}
				}
			}
		}
        return $results;
    }
    function import_review() {
        switch (TRUE) {
          case (sizeof($this->table_errors)):
			return "Error(s) found in import file";
          case (!$this->line_count):
			return "No fields imported";
          default:
            $this->update=TRUE;
			return "File import -> review";
		}
    }
}
class map_class {
	var $type;
    var $decimal;
    var $required;
    var $comment;
    var $alternate_name;
    var $export=FALSE;
    var $group;
    function __construct($type,$required="yes",$comment="",$alternate_name="",$group="") {
    	$type_array=preg_split("/\:/",$type);
    	$this->type=$type_array[0];
        $this->decimal=intval($type_array[1]);
        $this->required=$required;
        $this->comment=$comment;
        $this->alternate_name=strtolower($alternate_name);
        $this->group=$group;
        if ($required=="key") $this->export=TRUE;
    }

    function export($value) {
		switch ($this->type) {
          case "integer":
        	return (intval($value));
          case "float":
        	return (floatval($value));
            break;
          case "decimal":
          case "numeric":
        	return number_format($value,$this->decimal,".","");
          case "percent":
        	return number_format(($value * 100),$this->decimal,".","") . "%";
          case "date":
			return fn_date((fn_date("mysql",$value)),"ymd");
          case "datetime":
          case "timestamp":
			return $value;
          default:
          	return $value;
		}
    }
    function import($value) {
		switch ($this->type) {
          case "integer":
          case "float":
          case "decimal":
          case "numeric":
        	return fn_number($value);
          case "percent":
          	if (strpos($value,"%")) {
				return fn_number($value * .01);
              } else {
				return fn_number($value);
            }
          case "date":
          	$date=fn_date($value,"mdy");
            if (!$date) {
            	return "0000/00/00";
              } else {
				return $date;
			}
          case "datetime":
          case "timestamp":
			return fn_date($value,"timestamp");
          case "uppercase":
          	return strtoupper($value);
          case "lowercase":
          	return strtolower($value);
          case "boolean":
			switch (TRUE) {
              case (strtolower($value)=="n"):
              case (strtolower($value)=="no"):
              case ($value=="0"):
            	return 0;
			  default:
              	return 1;
			}
          default:
          	return $value;
		}
    }
}
?>