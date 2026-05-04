<?php
class configure_class {
	var $trace=array();
    function scs_table_version() {
		$results=array();
        $results['03/28/2025']="Bug in construct";
        $results['06/05/2023']="Add subcategory.currency";
        $results['09/23/2022']="Add currency.exchange_net";
        $results['07/27/2022']="Add currency";
        $results['05/06/2021']="Change pricing output";
        $results['03/04/2020']="Add inventory leadtime";
        $results['02/18/2020']="Revise leadtime";
        $results['01/09/2020']="New configure_pricing_class";
        $results['09/03/2019']="Add leadtime";
        $results['08/16/2019']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
    function __construct($sku_url="",$quantity=0,$options=array()) {
	    global $database;
    	$this->trace[]=__METHOD__;
        if (!$quantity) $quantity=1;
    	$this->meta=new configure_meta_class($sku_url,$quantity,$options);
        if (!strlen($sku_url)) return;
        $this->results=new configure_results($this);
        $this->smart_load();
        if (!$this->meta->source) $this->inventory_load($options);
        if ($this->meta->source) {
            $database->category->read($this->meta->category_id);
            $this->meta->leadtime=( ($this->meta->source == "inventory") ? $database->inventory->leadtime() : $database->subcategory->leadtime());
            $this->results->update($this);
          } else {
            $this->meta->error="Invalid SKU";
        }
    }
    function module_load() {
        global $database;
        if (func_num_args()) {
            $database->subcategory->read(func_get_arg(0));
            if (!$database->subcategory->meta->rows) return;
        }
        $file="classes/configure_" . $database->subcategory->data->id . ".php";
        if (!file_exists($file)) return;
        require_once $file;
        $classname="configure_" . $database->subcategory->data->id . "_class";
        $this->module=new $classname();
        return TRUE;
	}
    function smart_load() {
	    global $database;
    	$this->trace[]=__METHOD__;
	    $query_where=array();
	    $query_where[]=new query_where("output","=","smart");
        $status=array("Active");
        if ($database->user->access("Administrator",FALSE)) $status[]="Pending";
	    $query_where[]=new query_where("status","in",$status);
	    $query=array();
        $query[]="select * from subcategory";
        $query[]=$database->where($query_where);
        $query[]="order by id";
        $database->subcategory->query($query);
        while ($database->subcategory->fetch = $database->subcategory->fetch_array() ) {
			$database->subcategory->fetch();
            if (!$this->smart_module()) continue;
            foreach ($this->module->constant->series as $series => $item) {
            	if (substr($this->meta->sku,0,strlen($series)) == $series) {
	                $this->meta->source="smart";
	                $this->meta->series=$series;
	                $this->meta->category_id=$database->subcategory->data->category_id;
	                $this->meta->subcategory_id=$database->subcategory->data->id;
                    $prj=array();
                    $prj['catalog']=$database->registry->cad->catalog;
                    $prj['part']=$this->meta->sku;
                    $this->meta->prj=http_build_query($prj);
                    $this->meta->cad=1;
					break;
                }
            }
            if ($this->meta->subcategory_id) break;
		}
	    $database->subcategory->free_result();
        if (!$this->meta->source) return;
		$this->module->parse_sku($this->meta->sku,strlen($this->meta->series));
		if ( (!$this->module->meta->error) && (sizeof($this->module->meta->sku) != ($this->module->meta->parse_item + 1)) ) $this->module->meta->error="SKU contains excess fields";
		$this->meta->error=$this->module->meta->error;
        if ($this->meta->error) return;
        if (method_exists($this->module->constant,"load")) $this->meta->error=$this->module->constant->load($this->module->data);
	    if ($this->meta->error) return;
        $this->meta->error=$this->module->verify();
        if ($this->meta->error) return;
        $this->smart_pricing();
    }
    function smart_module() {
	    global $database;
    	$this->trace[]=__METHOD__;
    	if (func_num_args()) {
	        $database->subcategory->read(func_get_arg(0));
	        if (!$database->subcategory->meta->rows) return;
        }
        $file="classes/configure_" . $database->subcategory->data->id . ".php";
        if (!file_exists($file)) return;
	    require_once $file;
        $classname="configure_" . $database->subcategory->data->id . "_class";
		$this->module=new $classname();
		return TRUE;
    }
    function smart_pricing() {
        global $database, $forms, $menu;
    	$this->trace[]=__METHOD__;
		if (array_key_exists($this->meta->currency_code, $database->subcategory->data->currency)) {
        	$this->meta->adder_pct=$database->subcategory->data->currency[$this->meta->currency_code];
			$this->meta->exchange_net=round($this->meta->exchange_rate * (1 + $this->meta->adder_pct),4);
        }
		$this->module->pricing->query($this->module->data);
		$query_where=array();
        foreach ($this->module->pricing->item as $record_type => $item) {
            if ($item->error) continue;
            $query_where[]="(record_type=" . fn_escape($record_type) . " and options=" . fn_escape($item->query) .  ")";
        }
        if (!sizeof($query_where)) {
			$this->module->pricing->error="No pricing options specified";
            return $this->module->pricing->error;
        }
		$query=array("select");
        $query[]="pricing.* from pricing";
        $query[]="where subcategory_id=" . fn_escape($this->meta->subcategory_id);
        $query[]="and (\n" . implode(" or\n",$query_where) . "\n)";
        $query[]="order by record_type";
		$database->pricing->query($query);
	    while ($database->pricing->fetch = $database->pricing->fetch_array()) {
	        $database->pricing->fetch();
			if ($this->module->pricing->item[$database->pricing->data->record_type]->found) continue;
            $obj=$this->module->pricing->item[$database->pricing->data->record_type];
            $obj->found=TRUE;
            $obj->price_type=$database->pricing->data->price_type;
            foreach ($this->meta->currencies as $currency_code) {
	            $obj->price[$currency_code]=array();
                foreach ($database->pricing->data->price as $price) {
                	if ($currency_code == "USD") {
	                    $obj->price[$currency_code]['msrp'][]=$price;
	                    $obj->price[$currency_code]['price'][]=round($price * $this->meta->multiplier,2);
					  } else {
	                    $obj->price[$currency_code]['msrp'][]=round($price * $this->meta->exchange_net,4);
	                    $obj->price[$currency_code]['price'][]=round($price * $this->meta->exchange_net * $this->meta->multiplier,4);
					}
				}
			}
		}
		$database->pricing->free_result();
        $this->module->pricing();
        foreach ($this->module->pricing->item as $field => $obj) {
			switch (TRUE) {
              case (!$obj->priced):
              	break;
              case (!$obj->found):
            	if (!$this->module->pricing->error) $this->module->pricing->error="Price missing: {$field}";
                break;
              case ($obj->price_type):
              	$this->module->pricing->cf=TRUE;
                break;
              default:
              	$msrp=array();
              	$price=array();
	            $match=array();
	            $replace=array();
                $decimal=($this->meta->currency_code == "USD") ? 2 : 4;
                for ($i=0; $i < sizeof($obj->price_name); $i++) {
                	$msrp[]=round($obj->price[$this->meta->currency_code]['msrp'][$i] * $obj->quantity[$i],$decimal);
                	$price[]=round($obj->price[$this->meta->currency_code]['price'][$i] * $obj->quantity[$i],$decimal);
	                $match[]="QUANTITY{$i}";
	                $match[]="PRICE{$i}";
	                $replace[]=$obj->quantity[$i];
                    $replace[]=$this->currency_price($obj->price[$this->meta->currency_code]['price'][$i],$decimal);
                }
        		$obj->comment=str_replace($match,$replace,$obj->comment);
                $obj->net_msrp=array_sum($msrp);
                $obj->net_price=array_sum($price);
                $this->module->pricing->net_msrp += $obj->net_msrp;
                $this->module->pricing->net_price += $obj->net_price;
            }
        }
    }
    function inventory_load($options=array()) {
        global $database;
    	$this->trace[]=__METHOD__;
		$database->inventory->read($this->meta->sku_url,"sku");
        if (!$database->inventory->meta->rows) $database->inventory->read(str_replace("_"," ",$this->meta->sku_url),"sku");
		if (!$database->inventory->meta->rows) return;
	    $this->meta->source="inventory";
        $this->meta->sku=$database->inventory->data->sku;
	    $this->meta->category_id=$database->inventory->data->category_id;
	    $this->meta->subcategory_id=$database->inventory->data->subcategory_id;
	    $database->subcategory->read($database->inventory->data->subcategory_id);
        switch (TRUE) {
          case (!$database->subcategory->data->prj):
            break;
          case ($database->inventory->lookup(FALSE)):
            $this->meta->prj=$database->inventory->data->prj;
            $this->meta->cad=1;
            break;
          default:
            $this->meta->prj=$database->subcategory->data->prj;
        }
    }
    function inventory_msrp() {
    	$this->trace[]=__METHOD__;
    	$this->meta->source="inventory";
        $this->results=new configure_results($this);
        $this->results->update($this);
    }
    function parse_field($field) {
    	switch (TRUE) {
          case ($this->meta->error):
          	return;
		  case (!isset($this->meta->parse_item)):
          	$this->meta->parse_item=0;
			break;
          default:
          	$this->meta->parse_item++;
		}
        if (!strlen($this->meta->sku[$this->meta->parse_item])) {
        	$this->meta->error="{$field} data missing or incomplete";
		  } else {
            return $this->meta->sku[$this->meta->parse_item];
		}
	}
    function configure_verify($name,$field="") {
        if (!$field) $field=strtolower($name);
		switch (TRUE) {
          case (strlen($this->meta->error)):
			break;
          case (!property_exists($this->data,$field)):
        	$this->meta->error="Data field not defined --> {$field}";
			break;
          case (!isset($this->constant->{$field})):
          case (!is_array($this->constant->{$field})):
        	$this->meta->error="Constant not defined --> {$field}";
			break;
          case (array_key_exists($field,$this->parse_results->item)):
        	$this->meta->error="Duplicate results --> {$field}";
			break;
          case (!array_key_exists($this->data->$field,$this->constant->$field)):
        	$this->meta->error="Verify {$field} --> " . (strlen($this->data->$field) ?  $this->data->$field : "(blank)") . " --> not found";
			break;
          default:
			$this->parse_results->item[$field]=new configure_verify_class($name,$this->data->$field,$this->constant->$field[$this->data->{$field}]);
        }
    }
	function description() {
    global $database,$forms;
	    $results=array();
	    $results[]="<div><br>Specifications:</div>";
	    if ($this->meta->source == "inventory") {
	        foreach ($database->subcategory->data->parameters as $id => $parameter) {
	            $results[]=$forms->div($parameter->name . ": " . $database->inventory->data->parameter[$parameter->id]);
	        }
		  } else {
	        foreach ($this->module->parse_results->item as $field => $item) {
            	if ($item->name)$results[]=$forms->div($item->name . ": " . $item->description);
	        }
		}
		return implode("",$results);
	}
    function output_results() {
    global $forms;
		$results=array();
		if (!sizeof($this->module->parse_results->item)) return;
	    $results[]="<table class='small configure noborder'>";
	    foreach ($this->module->parse_results->item as $key => $item) {
	        $results[]="<tr>";
            if (strlen($item->code)) {
            	$text=$forms->button($item->code,array("class"=>"configure_button"));
			  } else {
              	$text="&nbsp;";
			}
            $results[]="<td>$text</td>";
	        $results[]="<td>" . $item->name . "</td>";
            $text=array($item->description);
	        if ($item->comment) $text[]="(" . $item->comment . ")";
	        $results[]="<td>" . implode("<br>",$text) . "</td>";
	        $results[]="</tr>";
	    }
	    $results[]="</table>";
        return implode("",$results);
	}
    function output_simple() {
		switch (TRUE) {
          case ($this->meta->error):
		  case (!$this->meta->access):
          case (!$this->results->price):
          case ($this->results->error):
           return "Contact Harcourt";
		  default:
			return $this->currency_price($this->results->price);
		}
    }
    function output_verbose() {
    global $database, $forms;
    	$results=array();
		switch (TRUE) {
          case ($this->meta->error):
		  case (!$this->meta->access):
          case (!$this->results->price):
          case ($this->results->error):
        	$results[]="<p>" . $database->registry->local->configure_contact . "</p>";
			break;
          case (sizeof($this->results->qty_break) > 1):
	        $results[]=$this->currency_name();
	        $results[]="<table class='standard noborder'>";
	        $results[]="<tr>";
	        $results[]="<td class='right'><b>From</b></td>";
	        $results[]="<td class='right'><b>Thru</b></td>";
	        $results[]="<td class='right'><b>Price</b></td>";
            if ($_SESSION['user']->price_type == "discount") $results[]="<td class='right nobr'><b>Your Price</b></td>";
	        $results[]="</tr>";
            $i=0;
            $items=array();
            foreach ($this->results->qty_break as $qty_break => $obj) {
            	if ($i) $items[$i - 1]->thru=$qty_break - 1;
            	$item=new stdClass();
                $item->from=($i) ? $qty_break : 1;
                $item->thru=0;
				$item->msrp=$obj[$this->meta->currency_code]['msrp'];
				$item->price=$obj[$this->meta->currency_code]['price'];
                $items[]=$item;
                $i++;
            }
            foreach ($items as $obj) {
		        $results[]="<tr>";
			    $results[]="<td class='right'>" . number_format($obj->from) . ( (!$obj->thru) ? "+" : "") .  "</td>";
			    $results[]="<td class='right'>" . ( (!$obj->thru) ? "" : number_format($obj->thru) ) . "</td>";
			    $results[]="<td class='right'>" . $this->currency_price($obj->msrp) . "</td>";
                if ($_SESSION['user']->price_type == "discount") $results[]="<td class='right'>" . $forms->font("red","<b><i>" . $this->currency_price($obj->price) . "</i></b>") . "</td>";
		        $results[]="</tr>";
            }
	        $results[]="</table>";
          	break;
		  case ($_SESSION['user']->price_type == "discount"):
	        $text=array();
	        $text[]=$this->currency_name(FALSE);
	        $text[]="Harcourt List Price: " . $this->currency_price($this->results->msrp);
            $text[]=$forms->font("red","<b><i>Your Discounted Price: " . $this->currency_price($this->results->price) . "</i></b>");
	        $results[]="<div>" . implode("<br>",$text) . "</div>";
            break;
		  default:
	        $results[]="<div>Price: " . $this->output_simple() . "</div>";
	        $results[]=$this->currency_name();
        }
        if ($this->meta->leadtime) $results[]="<p>Typical lead time: " . ($this->meta->leadtime) . "</p>";
        return implode("",$results);
	}
    function currency_price($price, $decimal=2) {
    	$text=array();
        if ( ($this->meta->currency_code == "USD") && ($_SESSION['user']->currency_code == "USD") ) $text[]="$";
        $text[]=number_format($price, $decimal);
        if ( ($_SESSION['user']->currency_code != "USD") || ($this->meta->currency_code != "USD") ) $text[]=" " . $this->meta->currency_code;
		return implode("",$text);
	}
    function currency_name($div=TRUE) {
    global $menu;
    	switch (TRUE) {
          case ($this->meta->currency_code != "USD"):
          case (!array_key_exists($_SESSION['user']->currency_code,$menu->currency)):
          	$results=array();
            if ($div) $results[]="<div>";
            $results[]="Priced in <b>" . $menu->currency[$this->meta->currency_code]->plural . "</b>";
            if ($div) $results[]="</div>";
            return implode("",$results);
		}
    }
    function select_record_type($compare="",$select_options=array(),$forms_options=array()) {
    global $database, $forms, $menu;
		switch (TRUE) {
          case (!$database->subcategory->meta->rows):
          case (!property_exists($this,"module")):
          	return;
		}
		if (!array_key_exists("field",$select_options)) $select_options['field']="record_type";
    	if (!array_key_exists("blank",$select_options)) $select_options['blank']=TRUE;
        $results=array();
        foreach ($this->module->pricing->item as $code => $item) {
        	$results[$code]=$item->name;
        }
        if (sizeof($results)) return $forms->select($select_options['field'],$results,$compare,$select_options['blank'],$forms_options);
	}
}
class configure_meta_class {
    var $source;
    var $series;
    var $category_id=0;
    var $subcategory_id=0;
	var $sku_url;
    var $sku;
    var $prj;
    var $cad=0;
    var $quantity=0;
    var $override=FALSE;
    var $access=TRUE;
    var $price_type;
    var $price_pct=0;
    var $multiplier=1;
    var $currency_code;
    var $currencies=array("USD");
    var $exchange_rate=0;
    var $adder_pct=0;
    var $exchange_net=0;
    var $leadtime;
    var $error;
    function __construct($sku,$quantity,$options) {
    global $database, $forms, $menu;
    	$this->sku_url=strtoupper($sku);
        $this->sku=$this->sku_url;
        $this->quantity=(($quantity > 0) ? $quantity : 1);
        $this->override=( (array_key_exists("override",$options)) ? $options['override'] : FALSE);
        $this->currency_code=( (array_key_exists("currency_code",$options)) ? $options['currency_code'] : $_SESSION['user']->currency_code);
        if (!array_key_exists($this->currency_code,$menu->currency)) $this->currency_code="USD";
        if ($this->currency_code != "USD") $this->currencies[]=$this->currency_code;
        $this->exchange_rate=$menu->currency[$this->currency_code]->exchange_rate;
        $this->adder_pct=$menu->currency[$this->currency_code]->adder_pct;
        $this->exchange_net=round($this->exchange_rate * (1 + $this->adder_pct),4);
        switch (TRUE) {
		  case ($_SESSION['user']->status == "Pending"):
		  case ($_SESSION['user']->status == "Denied"):
          	$this->access=FALSE;
          	break;
          case ($this->override):
          	$this->price_type=$options['price_type'];
            $this->price_pct=$options['price_pct'];
        	break;
          case ($_SESSION['user']->id):
          	$this->price_type=$_SESSION['user']->price_type;
            $this->price_pct=$_SESSION['user']->price_pct;
        	break;
		}
        switch ($this->price_type) {
		  case "":
          	$this->multiplier=0;
            break;
          case "discount":
          	$this->multiplier=(1 - $this->price_pct);
            break;
          case "plus":
            $this->multiplier=(1 + $this->price_pct);
            break;
		}
    }
}
class configure_parse_results {
    var $errors=array();
    var $item=array();
    function __construct() {
    }
}
class configure_results {
	var $quantity=0;
    var $price_type;
    var $price_pct=0;
    var $multiplier=1;
	var $currency_code;
    var $exchange_rate=0;
    var $adder_pct=0;
    var $exchange_net=0;
    var $msrp=0;
    var $price=0;
    var $extended=0;
    var $qty_break=array();
    var $error;
	function __construct($configure) {
    	$this->quantity=$configure->meta->quantity;
    	$this->price_type=$configure->meta->price_type;
    	$this->price_pct=$configure->meta->price_pct;
    	$this->multiplier=$configure->meta->multiplier;
    	$this->quantity=$configure->meta->quantity;
    	$this->currency_code=$configure->meta->currency_code;
    	$this->exchange_rate=$configure->meta->exchange_rate;
    	$this->adder_pct=$configure->meta->adder_pct;
    	$this->exchange_net=$configure->meta->exchange_net;
    }
    function update($configure) {
    global $database;
		if (array_key_exists($this->currency_code, $database->subcategory->data->currency)) {
        	$this->adder_pct=$database->subcategory->data->currency[$this->currency_code];
			$this->exchange_net=round($this->exchange_rate * (1 + $this->adder_pct),4);
        }
    	switch (TRUE) {
          case ($configure->meta->error):
          case (!$configure->meta->access):
          case (!$this->multiplier):
          case (!$this->exchange_net):
          	$this->error=$database->registry->local->configure_contact;
			return;
          case ($configure->meta->source == "inventory"):
          	foreach ($database->inventory->data->pricing as $obj) {
            	$this->qty_break($obj->qty_break, $obj->price);
            }
	        foreach ($this->qty_break as $qty_break => $obj) {
	            if ($qty_break > $this->quantity) break;
	            $this->msrp=$obj[$this->currency_code]['msrp'];
	            $this->price=$obj[$this->currency_code]['price'];
	        }
            break;
          case ($configure->meta->source == "smart"):
			$this->msrp=$configure->module->pricing->net_msrp;
			$this->price=$configure->module->pricing->net_price;
            break;
		}
        $this->extended=$this->price * $this->quantity;
        if (!$this->price) $this->error=$database->registry->local->configure_contact;
    }
    private function qty_break($qty_break, $msrp) {
		$obj=array();
        $obj['USD']=array();
        $obj['USD']['msrp']=$msrp;
        $obj['USD']['price']=round($msrp * $this->multiplier,2);
        if ($this->currency_code != "USD") {
        	$obj[$this->currency_code]=array();
            $obj[$this->currency_code]['msrp']=round($msrp * $this->exchange_net, 2);
            $obj[$this->currency_code]['price']=round($obj[$this->currency_code]['msrp'] * $this->multiplier,2);
		}
        $this->qty_break[$qty_break]=$obj;
    }
}
class configure_module_meta_class {
	var $sku=array();
    var $leadtime;
    var $error;
    var $revision;
    function __construct($revision="") {
		$this->revision=$revision;
    }
}
class configure_pricing_class {
    var $map=array();
    var $error;
    var $cf=FALSE;
    var $net_msrp=0;
    var $net_price=0;
    var $item=array();
    function __construct($map=array()) {
		foreach ($map as $key => $value) {
        	$item=new stdClass();
            if (is_array($value)) {
            	$item->type="list";
                $item->decimal=0;
                $item->list=$value;
              } else {
	            list($item->type,$item->decimal)=preg_split("/:/",$value);
	            $item->decimal=intval($item->decimal);
	            if (!in_array($item->type,array("","decimal","fixed"))) die("Invalid type: " . $item->type);
                $item->list=array();
			}
			$this->map[$key]=$item;
        }
    }
    function field($field,$name,$price_name,$fields,$options=array()) {
    	if (array_key_exists($field,$this->item)) die("Pricing item already exists: {$field}");
        $this->item[$field]=new configure_pricing_item($name,$price_name,$fields,$options,$this->map);
	}
    function query($data) {
		foreach ($this->item as $item) {
        	$item->query($data);
        }
    }
    function import($data) {
    	if (!array_key_exists($data['record_type'],$this->item)) return;
        $content=new stdClass();
		$i=0;
        foreach ($this->item[$data['record_type']]->field as $field => $map) {
        	$content->$field=$data["code{$i}"];
            $i++;
        }
		return $this->item[$data['record_type']]->query($content);
    }
    function price($field, $comment, $quantity, $options=array()) {
        switch (TRUE) {
          case (!array_key_exists($field,$this->item)):
          	die("Pricing field not defined: {$field}");
          case ($this->item[$field]->priced):
          	die("Pricing field priced: {$field}");
		}
        $this->item[$field]->priced=TRUE;
		$this->item[$field]->comment=$comment;
		$this->item[$field]->quantity=(is_array($quantity)) ? $quantity : array($quantity);
        if ($options['name']) $this->item[$field]->name=$options['name'];
    }
}
class configure_pricing_item {
	var $name;
    var $price_name=array();
    var $field=array();
    var $query;
    var $comment;
    var $price=array();
    var $net_msrp=0;
    var $net_price=0;
    var $required=TRUE;
    var $found=FALSE;
    var $priced=FALSE;
    var $unit;
    var $conditional=FALSE;
    var $quantity=array();
	var $error;
    function __construct($name,$price_name,$fields,$options,$map) {
		$this->name=$name;
        $this->unit=$options['unit'];
        $this->conditional=$options['conditional'];
		$this->price_name=$price_name;
        foreach ($fields as $field) {
			$this->field[$field]=(array_key_exists($field,$map) ? $map[$field] : new stdClass() );
        }
    }
    function query($data=array()) {
        $this->error="";
        $results=array();
		$i=0;
        foreach ($this->field as $name => $map) {
 			$text="{$name}=";
            switch (TRUE) {
              case ($map->type == "decimal"):
            	$results[]=$text . number_format(floatval($data->$name),$map->decimal,".","");
                break;
              case ($map->type == "fixed"):
            	$results[]=$text . substr("000000000000" . intval($data->$name),(-1 * $map->decimal));
              	break;
              default:
            	$results[]=$text . trim($data->$name);
			}
            $i++;
        }
		$this->query=implode("&",$results);
        return $this->query;
    }
}
class configure_options_class {
	var $description;
    var $options=array();
    function __construct($description,$options=array()) {
		$this->description=$description;
        $this->options=$options;
    }
}
class configure_verify_class {
    var $name;
	var $code;
    var $description;
	var $comment;
    function __construct($name,$code,$data="",$comment="") {
    	$this->name=$name;
    	$this->code=$code;
		if (is_object($data)) {
	        $this->description=$data->description;
	        $this->comment=$data->comment;
		  } else {
	        $this->description=$data;
	        $this->comment=$comment;
        }
    }
}
class configure_msrp_item {
    var $qty_break=0;
	var $msrp=0;
	var $price=0;
    function __construct($qty_break, $msrp, $price) {
		$this->qty_break=$qty_break;
		$this->msrp=$msrp;
		$this->price=$price;
    }
}
?>