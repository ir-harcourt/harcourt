<?php
$database->pricing=new pricing_class();
class pricing_class extends database_class {
	function __construct() {
    	$this->data=new pricing_data_class();
    	$this->meta=new database_meta_class();
        $this->fetch=array();
        $this->constant=new pricing_constant_class();
    }
	function scs_table_version() {
		$results=array();
        $results['01/09/2020']="Add list option";
        $results['08/16/2019']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
    function fetch($fetch=FALSE) {
        if ($fetch) $this->fetch=$this->fetch_array();
    	$this->data=new pricing_data_class();
	    $this->data->subcategory_id=$this->fetch['subcategory_id'];
	    $this->data->record_type=$this->fetch['record_type'];
	    $this->data->options=$this->fetch['options'];
	    $this->data->price_type=$this->fetch['price_type'];
	    $this->data->price[0]=$this->fetch['price0'];
	    $this->data->price[1]=$this->fetch['price1'];
	    $this->data->price[2]=$this->fetch['price2'];
	    $this->data->price[3]=$this->fetch['price3'];
	    $this->data->price[4]=$this->fetch['price4'];
	    $this->data->update_timestamp=$this->fetch['update_timestamp'];
    }
    function update() {
		$fields=array();
		$fields[]="subcategory_id=" . fn_escape($this->data->subcategory_id);
		$fields[]="record_type=" . fn_escape($this->data->record_type);
		$fields[]="options=" . fn_escape($this->data->options);
		$fields[]="price_type=" . fn_escape($this->data->price_type);
		$fields[]="price0=" . fn_escape($this->data->price[0],FALSE);
		$fields[]="price1=" . fn_escape($this->data->price[1],FALSE);
		$fields[]="price2=" . fn_escape($this->data->price[2],FALSE);
		$fields[]="price3=" . fn_escape($this->data->price[3],FALSE);
		$fields[]="price4=" . fn_escape($this->data->price[4],FALSE);
		$fields[]="update_timestamp=" . fn_escape($this->map->update_timestamp);
        $query=array("replace pricing set");
        $query[]=implode(",\n",$fields);
		$this->query($query);
	}
    function map($options) {
    global $database, $forms, $menu;
        $this->map=new table_map_class($options);
 		$this->map->update_timestamp=strtotime("now");
 		$this->map->item("subcategory_id",array("required"=>TRUE,"type"=>"lowercase"));
 		$this->map->item("record_type",array("required"=>TRUE,"type"=>"lowercase"));
 		$this->map->item("code0",array("required"=>TRUE,"type"=>"uppercase"));
 		$this->map->item("code1",array("required"=>TRUE,"type"=>"uppercase"));
 		$this->map->item("code2",array("required"=>TRUE,"type"=>"uppercase"));
 		$this->map->item("code3",array("required"=>TRUE,"type"=>"uppercase"));
 		$this->map->item("code4",array("required"=>TRUE,"type"=>"uppercase"));
 		$this->map->item("price0",array("required"=>TRUE,"type"=>"decimal:2"));
 		$this->map->item("price1",array("required"=>TRUE,"type"=>"decimal:2"));
 		$this->map->item("price2",array("required"=>TRUE,"type"=>"decimal:2"));
 		$this->map->item("price3",array("required"=>TRUE,"type"=>"decimal:2"));
 		$this->map->item("price4",array("required"=>TRUE,"type"=>"decimal:2"));
        switch (TRUE) {
          case ( ($options['subcategory_id']) && ($options['record_type']) ):
        	if (!array_key_exists($options['record_type'], $menu->configure->module->pricing->item)) break;
        	$code=array_keys($menu->configure->module->pricing->item[ $options['record_type'] ]->field);
			for ($i=0; $i < 5; $i++) {
            	$field="code{$i}";
            	if ($code[$i]) {
                	$this->map->fields[$field]->name=$code[$i];
				  } else {
                     unset($this->map->fields[$field]);
				}
            }
        	$code=$menu->configure->module->pricing->item[ $options['record_type'] ]->price_name;
			for ($i=0; $i < 5; $i++) {
            	$field="price{$i}";
            	if ($code[$i]) {
                	$this->map->fields[$field]->name=( (preg_match("/price/i",$code[$i])) ? $code[$i] : $code[$i] . " price");
				  } else {
                     unset($this->map->fields[$field]);
				}
            }
        }
    }
    function map_multiple($options,$code) {
    global $database, $forms, $menu;
    	$options['record_type']=$code;
        $this->map($options);
    }
    function worksheet_name($record_type) {
    global $menu;
		return (array_key_exists($record_type,$menu->configure->module->pricing->item) ? $menu->configure->module->pricing->item[$record_type]->name : $record_type);
    }
    function export_query() {
		$record_type=( (func_num_args()) ? func_get_arg(0) : $this->map->options['record_type']);
	    $query_where=array();
	    $query_where[]=new query_where("subcategory_id","=",$this->map->options['subcategory_id']);
        if (strlen($record_type)) $query_where[]=new query_where("record_type","=",$record_type);
	    $query=array("select * from pricing");
	    $query[]=$this->where($query_where);
	    $query[]="order by record_type,options";
		return $query;
    }
    function export_data() {
    global $menu;
		$results=array();
        $items=fn_urlencode($this->data->options,FALSE);
        $i=0;
        foreach ($menu->configure->module->pricing->item[$this->data->record_type]->field as $field_name => $field_obj) {
        	$field="code{$i}";
            switch (TRUE) {
              case (!array_key_exists($field_name,$items)):
              case (!strlen($items[$field_name])):
              die('b');
              	break;
			  case ($field_obj->type == "list"):
              	$this->data->$field=$field_obj->list[ $items[$field_name] ];
                break;
              default:
              	$this->data->$field=$items[$field_name];
            }
            $i++;
        }
		for ($i=0; $i < 5; $i++) {
        	$field="price{$i}";
            $this->data->$field=$this->data->price[$i];
        }
		foreach ($this->map->fields as $field => $map) {
			$results[$field]=$this->data->$field;
        }
		return $results;
    }
    function import_initialize() {
    global $menu;
		$this->map->record_type=array("");
	    foreach ($menu->configure->module->pricing->item as $record_type => $item) {
	        $this->map->record_type[]=$record_type;
	    }
    }
    function import_update($data) {
    global $menu;
        switch (TRUE) {
		  case ($data['subcategory_id'] != $this->map->options['subcategory_id']):
			$this->map->error['subcategory_id']=$data['subcategory_id'];
			break;
          case (!in_array($data['record_type'],$this->map->record_type)):
          case ( ($this->map->options['record_type']) && ($data['record_type'] != $this->map->options['record_type']) ):
			$this->map->error['record_type']=$data['record_type'];
			break;
		}
        if (sizeof($this->map->error)) return;
        $this->data=new pricing_data_class();
	    $this->data->subcategory_id=$data['subcategory_id'];
	    $this->data->record_type=$data['record_type'];
		$this->data->options=$menu->configure->module->pricing->import($data);
	    $this->data->price_type=$data['price_type'];
        if ($this->data->price_type != $data['price_type']) $this->map->error['price_type']="Invalid: " . $this->data->price_type;

        $options=array();
        $i=0;
        foreach ($menu->configure->module->pricing->item[ $data['record_type'] ]->field as $field_name => $field_obj) {
        	switch ($field_obj->type) {
              case "list":
				$options[$field_name]=import_match_id($data["code{$i}"], $field_obj->list, TRUE);
            	break;
              default:
				$options[$field_name]=$data["code{$i}"];
				break;
            }
            if (!strlen($options[$field_name])) $this->map->error["code{$i}"]="Invalid: " . $data["code{$i}"];
        }
        $this->data->options=fn_urlencode($options,TRUE);
        for ($i=0; $i < sizeof($menu->configure->module->pricing->item[ $data['record_type'] ]->price_name); $i++) {
			$this->data->price[$i]=$data["price{$i}"];
        }
        switch (TRUE) {
          case ( ($data['price_type']) && (floatval($this->data->price[0])) ):
			$this->map->error['price0']="Price must be 0";
            break;
		}
        switch (TRUE) {
          case (sizeof($this->map->error)):
          case ($this->map->options['function'] != "upload"):
          	break;
          default:
			$this->update();
            if ($this->meta->error) $this->error['mysql']=$this->meta->error;
		}
	}
    function import_delete() {
		$query_where=array();
        $query_where[]=new query_where("update_timestamp","<>",$this->map->update_timestamp);
        $query_where[]=new query_where("subcategory_id","=",$this->map->group);
        if ($this->map->options['record_type']) $query_where[]=new query_where("record_type","=",$this->map->options['record_type']);
	    $query=array("delete from pricing");
        $query[]=$this->where($query_where);
		$this->query($query);
    }
}
class pricing_data_class {
	var $subcategory_id;
	var $record_type;
	var $options;
	var $price_type;
	var $price=array();
    var $update_timestamp=0;
    function __construct() {
    }
}
class pricing_constant_class {
	var $price_size=5;
	var $price_type=array(""=>"","C/F"=>"Consult Factory","N/A"=>"Not Available");
}

/*
CREATE TABLE `pricing` (
  `subcategory_id` varchar(45) NOT NULL DEFAULT '',
  `record_type` varchar(45) NOT NULL DEFAULT '',
  `options` varchar(255) NOT NULL DEFAULT '',
  `price_type` varchar(12) NOT NULL DEFAULT '',
  `price0` decimal(11,2) NOT NULL DEFAULT '0.00',
  `price1` decimal(11,2) NOT NULL DEFAULT '0.00',
  `price2` decimal(11,2) NOT NULL DEFAULT '0.00',
  `price3` decimal(11,2) NOT NULL DEFAULT '0.00',
  `price4` decimal(11,2) NOT NULL DEFAULT '0.00',
  `update_timestamp` bigint(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`subcategory_id`,`record_type`,`options`),
  KEY `timestamp_index` (`update_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Pricing (08/16/2019)'
*/
?>