<?php
$database->inventory = new inventory_class();
class inventory_class extends database_class {
	function scs_table_version() {
		$results=array();
        $results['08/21/2024']="Add competitor_sku (hotfix)";
        $results['07/22/2024']="Blank out prj (hotfix)";
        $results['06/06/2024']="Add lookup";
        $results['07/28/2022']="Expand SKU to varchar(80)";
        $results['04/12/2020']="Add weeks";
        $results['03/03/2020']="Add leadtime";
        $results['02/18/2020']="Drop prj_new, sonic";
        $results['08/22/2019']="Drop MSRP";
        $results['03/08/2019']="Add prj_new";
        $results['06/22/2017']="Update pricing";
        $results['08/10/2016']="Add sonic";
        $results['03/22/2016']="Search only allowed on visible subcategories";
        $results['04/15/2015']="Return search_term with ajax";
        $results['03/11/2015']="Add discrete parameter";
        $results['02/10/2015']="Add new_date";
        $results['09/11/2014']="Add import/export";
        $results['07/29/2013']=array("Add tiered pricing","Add language support");
        $results['01/21/2013']="Add pricing";
        $results['06/23/2011']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
	function __construct() {
    	$this->data=new inventory_data_class();
        $this->fetch=array();
    	$this->meta=new database_meta_class();
        $this->constant=new inventory_constant_class();
    }
    function fetch($fetch=FALSE) {
        if ($fetch) $this->fetch=$this->fetch_array();
	    $this->data=new inventory_data_class();
        $this->data->id=$this->fetch['id'];
	    $this->data->sku=$this->fetch['sku'];
	    $this->data->category_id=$this->fetch['category_id'];
	    $this->data->subcategory_id=$this->fetch['subcategory_id'];
	    $this->data->prj=$this->fetch['prj'];
		for ($parameter_id=0; $parameter_id < $this->constant->parameters; $parameter_id++) {
			$this->data->parameter[$parameter_id]=$this->fetch['parameter' . $parameter_id];
			$this->data->nparameter[$parameter_id]=floatval($this->fetch['nparameter' . $parameter_id]);
        }
		if ($this->fetch['documents']) $this->data->documents=unserialize($this->fetch['documents']);
		if ($this->fetch['pricing']) $this->data->pricing=unserialize($this->fetch['pricing']);
	    $this->data->sort_order=$this->fetch['sort_order'];
	    $this->data->new_date=fn_date($this->fetch['new_date'],"mysql");
        if (strlen($this->fetch['competitor_sku'])) $this->data->competitor_sku=explode(",", $this->fetch['competitor_sku']);
	    $this->data->leadtime_option=$this->fetch['leadtime_option'];
	    $this->data->leadtime_text=$this->fetch['leadtime_text'];
	    $this->data->active=$this->fetch['active'];
    }
    function read($id,$field="id") {
	    $query=array("select * from inventory");
	    $query[]="where {$field}=" . fn_escape($id);
        $this->query($query);
        if ($this->meta->rows) {
        	$this->fetch(TRUE);
			$this->free_result();
		  } else {
			$this->data=new inventory_data_class();
		}
    }
    function update($update,$replace=FALSE) {
		$fields=array();
        $fields[]="id=" . fn_escape($this->data->id,FALSE);
        $fields[]="sku=" . fn_escape($this->data->sku);
        $fields[]="category_id=" . fn_escape($this->data->category_id,FALSE);
        $fields[]="subcategory_id=" . fn_escape($this->data->subcategory_id,FALSE);
        $fields[]="prj=" . fn_escape($this->data->prj);
		for ($parameter_id=0; $parameter_id < $this->constant->parameters; $parameter_id++) {
        	$fields[]="parameter{$parameter_id}=" . fn_escape($this->data->parameter[$parameter_id]);
        	$fields[]="nparameter{$parameter_id}=" . fn_escape($this->data->nparameter[$parameter_id],FALSE);
        }
        $fields[]="documents=" . fn_escape(serialize($this->data->documents));
        $fields[]="pricing=" . fn_escape(serialize($this->data->pricing));
        $fields[]="sort_order=" . fn_escape($this->data->sort_order,FALSE);
        $fields[]="new_date=" . fn_escape($this->data->new_date,"date");
        $fields[]="competitor_sku=" . fn_escape(implode(",", $this->data->competitor_sku));
	    $fields[]="leadtime_option=" . fn_escape($this->data->leadtime_option);
	    $fields[]="leadtime_text=" . fn_escape($this->data->leadtime_text);
        $fields[]="active=" . fn_escape($this->data->active,FALSE);
        $query=array();
        switch (TRUE) {
		  case ( ($update) && ($replace) ):
          	$query[]="replace inventory set";
          	$query[]=implode(",\n",$fields);
            break;
		  case ($update):
          	$query[]="update inventory set";
          	$query[]=implode(",\n",$fields);
          	$query[]="where id=" . fn_escape($this->data->id);
			break;
		  default:
          	$query[]="insert into inventory set";
          	$query[]=implode(",\n",$fields);
        }
		$this->query($query);
		if ((!$this->data->id) && (!$this->meta->error)) $this->data->id = $this->insert_id();
    }
    function delete($id) {
	    $query=array("delete from inventory where");
        $query[]="id=" . fn_escape($id);
		$this->query($query);
    }
    function new_date() {
    global $database;
		return date("Y/m/d",strtotime("now -" . $database->registry->local->new_days . " days"));
    }
    function leadtime() {
        global $database;
    	switch (TRUE) {
          case ($this->data->leadtime_option == "Subcategory"):
          	return $database->subcategory->leadtime();
          case ($this->data->leadtime_option != "Weeks"):
          	return $this->data->leadtime_option;
          case (strlen($this->data->leadtime_text)):
          	return $this->data->leadtime_text . " Week(s)";
		}
    }
    function prj($prj, $language_code="") {
        global $database, $menu;
		$text=explode("&",$prj,2);
		$query=array();
        $query['info']=substr($text[0],5);
        if (substr($text[1],0,7) == "varset=") $query['varset']=substr($text[1],7);
        if ($language_code) $query['languageIso']=strtolower($language_code);
        $i=0;
        $results=array($database->registry->cad->configurator);
        foreach ($query as $key => $value) {
        	$results[]=( (sizeof($results) == 1) ? "?" : "&") . "{$key}={$value}";
        }
		return implode("",$results);
    }
    function lookup($log=TRUE) {
        global $database;
        switch (TRUE) {
          case (!strlen($this->data->sku)):
            return;
          case (strlen($this->data->prj)):
            return TRUE;
        }
        $lookup=new cad_lookup_class($this->data->sku, array("log"=>FALSE));
        if ($lookup->error) {
            if ($log) $database->log->update("PARTSolutions:Reverse Lookup Error",array("sku"=>$this->data->sku,"comment"=>$lookup->error));
            return;
        }
        $text=array();
        foreach ($lookup->url_query as $key => $value) {
            $text[]="{$key}={$value}";
        }
        $this->data->prj=implode("&", $text);
        $query=array("update inventory");
        $query[]="set prj=" . fn_escape($this->data->prj);
        $query[]="where sku=" . fn_escape($this->data->sku);
        $database->temp->query($query);
        return TRUE;
    }
    function competitor_sku($input, $import=FALSE) {
        $errors=array();
        $this->data->competitor_sku=array();
        if ($import) {
            $items=explode(",", $input);
          } else {
            if (preg_match("/\,/", $input)) $errors[0]="Comma not allowed in competitor SKU";
            $items=explode("\n", $input);
        }
        foreach ($items as $item) {
            $item=trim(strtoupper($item));
            if (!strlen($item)) continue;
            if (strlen($item) < 3) $errors[1]="Must be at least 3 characters";
            if (!in_array($item, $this->data->competitor_sku)) $this->data->competitor_sku[]=$item;
        }
        sort($this->data->competitor_sku);
        return implode("<br>", $errors);
    }
    function map($options=array()) {
        global $database;
        if (!array_key_exists("subcategory_id",$options)) {
        	$options['subcategory_id']=0;
		  } else {
			$database->subcategory->read($options['subcategory_id']);
		}
        $this->map=new table_map_class($options);
        switch (TRUE) {
          case ($this->map->options['partcommunity']):
			$this->map_partcommunity();
            break;
          case ($this->map->options['erp']):
			$this->map_erp();
            break;
		  default:
			$this->map_inventory();
        }
	}
    function map_partcommunity() {
        global $database;
	    $this->map->item("nb",array("comment"=>"SKU?"));
	    $this->map->item("sku",array("name"=>"lina","required"=>TRUE));
	    $this->map->item("nn",array("comment"=>"Subcategory Prefix"));
	    $this->map->item("nt",array("comment"=>"Subcategory Name"));
    }
    function map_erp() {
    global $database;
	    $this->map->item("sku",array("name"=>"Harcourt Part # in Inxsql","type"=>"uppercase","required"=>TRUE));
	    $this->map->item("_price_code",array("name"=>"Price Code","type"=>"uppercase","required"=>TRUE));
	    $this->map->item("_price0",array("name"=>"List Price","type"=>"decimal:4","required"=>TRUE));
	    $this->map->item("_break1",array("name"=>"iqty_quantity1","type"=>"decimal:0","required"=>TRUE));
	    $this->map->item("_price1",array("name"=>"iprc1","type"=>"decimal:4","required"=>TRUE));
	    $this->map->item("_break2",array("name"=>"iqty_quantity2","type"=>"decimal:0","required"=>TRUE));
	    $this->map->item("_price2",array("name"=>"iprc2","type"=>"decimal:4","required"=>TRUE));
	    $this->map->item("_break3",array("name"=>"iqty_quantity3","type"=>"decimal:0","required"=>TRUE));
	    $this->map->item("_price3",array("name"=>"iprc3","type"=>"decimal:4","required"=>TRUE));
	    $this->map->item("_break4",array("name"=>"iqty_quantity4","type"=>"decimal:0","required"=>TRUE));
	    $this->map->item("_price4",array("name"=>"iprc4","type"=>"decimal:4","required"=>TRUE));
	    $this->map->item("_break5",array("name"=>"iqty_quantity5","type"=>"decimal:0","required"=>TRUE));
	    $this->map->item("_price5",array("name"=>"iprc5","type"=>"decimal:4","required"=>TRUE));
	    $this->map->item("_break6",array("name"=>"iqty_quantity6","type"=>"decimal:0","required"=>TRUE));
	    $this->map->item("_price6",array("name"=>"iprc6","type"=>"decimal:4","required"=>TRUE));
	    $this->map->item("_break7",array("name"=>"iqty_quantity7","type"=>"decimal:0","required"=>TRUE));
	    $this->map->item("_price7",array("name"=>"iprc7","type"=>"decimal:4","required"=>TRUE));
	    $this->map->item("_break8",array("name"=>"iqty_quantity8","type"=>"decimal:0","required"=>TRUE));
	    $this->map->item("_price8",array("name"=>"iprc8","type"=>"decimal:4","required"=>TRUE));
	    $this->map->item("_break9",array("name"=>"iqty_quantity9","type"=>"decimal:0","required"=>TRUE));
	    $this->map->item("_price9",array("name"=>"iprc9","type"=>"decimal:4","required"=>TRUE));
	    $this->map->item("_break10",array("name"=>"iqty_quantity10","type"=>"decimal:0","required"=>TRUE));
	    $this->map->item("_price10",array("name"=>"iprc10","type"=>"decimal:4","required"=>TRUE));
    }
    function map_inventory() {
    global $database;
	    $this->map->item("sku",array("name"=>"Harcourt SKU","type"=>"uppercase","required"=>TRUE,"width"=>15));
	    $this->map->item("category_name",array("name"=>"Category Name","group"=>"subcategory","width"=>30));
	    $this->map->item("subcategory_name",array("name"=>"Subcategory Name","group"=>"subcategory","width"=>30));
	    $this->map->item("sort_order",array("name"=>"Sort Order","type"=>"decimal:0","width"=>6));
	    $this->map->item("new_date",array("name"=>"New Date","type"=>"date","width"=>10));
	    $this->map->item("leadtime_option",array("name"=>"Leadtime Option","group"=>"leadtime","width"=>20));
	    $this->map->item("leadtime_text",array("name"=>"Leadtime Weeks","group"=>"leadtime","width"=>20));
        $this->map->item("temp_competitor_sku",array("name"=>"Competitor SKU","type"=>"uppercase","width"=>60,"comment"=>"Comma separated"));
	    $this->map->item("active",array("name"=>"Active","type"=>"boolean","width"=>6));
        for ($i=0; $i < $this->constant->parameters; $i++) {
			$this->map->item("parameter{$i}",array("name"=>"Parameter #" . ($i + 1),"group"=>"parameters"));
        }
        for ($i=0; $i < $this->constant->parameters; $i++) {
			$item=($i + 1);
			$this->map->item("nparameter{$i}",array("name"=>"Numeric Parameter #" . ($i + 1),"group"=>"parameters","type"=>"numeric"));
        }
        for ($i=0; $i < 10; $i++) {
			if (!$i) {
				$this->map->item("price{$i}",array("name"=>"Base Price","group"=>"pricing","type"=>"decimal:2","width"=>12));
			  } else {
	            $this->map->item("qty_break{$i}",array("name"=>"Qty Break #{$i}","group"=>"pricing","type"=>"decimal:0","width"=>10));
	            $this->map->item("price{$i}",array("name"=>"Price #{$i}","group"=>"pricing","type"=>"decimal:2","width"=>12));
			}
        }
        $query_where=array();
        if ($this->map->options['subcategory_id']) $query_where[]=new query_where("subcategory.id","=",$this->map->options['subcategory_id']);
		$query=array("select");
        $query[]="concat(category.id,':',subcategory.id) as id,";
        $query[]="lower(concat(category.name,':',subcategory.name)) as name";
        $query[]="from subcategory";
        $query[]="left join category on category.id=subcategory.category_id";
        $query[]=$database->where($query_where);
		$database->temp->query($query);
        $this->map->subcategory=array();
        while ($database->temp->fetch = $database->temp->fetch_array() ) {
			$this->map->subcategory[ $database->temp->fetch['name'] ]=$database->temp->fetch['id'];
        }
    }
    function export_query() {
	    $query_where=array();
	    if ($this->map->options['subcategory_id']) $query_where[]=new query_where("inventory.subcategory_id","=",$this->map->options['subcategory_id']);
	    if ($this->map->options['no_price']) $query_where[]=new query_where("inventory.pricing","in",array("","a:0:{}"));
	    $query=array("select");
        $query[]="category.name as 'category_name',";
        $query[]="subcategory.name as 'subcategory_name',";
        $query[]="inventory.* from inventory";
        $query[]="left join subcategory on subcategory.id=inventory.subcategory_id";
        $query[]="left join category on category.id=subcategory.category_id";
        $query[]=trim($this->where($query_where));
        $query[]="order by category.name,subcategory.name,inventory.sort_order,inventory.sku";
		return $query;
    }
    function export_data() {
        $this->data->temp_competitor_sku=implode(", ", $this->data->competitor_sku);
        for ($i=0; $i < $this->constant->parameters; $i++) {
            $field="parameter{$i}";
            $this->data->$field=$this->data->parameter[$i];
            $field="nparameter{$i}";
            $this->data->$field=$this->data->nparameter[$i];
        }
        for ($i=0; $i < 10; $i++) {
            $price="price{$i}";
            $qty_break="qty_break{$i}";
			$this->data->$price=$this->data->pricing[$i]->price;
			$this->data->$qty_break=$this->data->pricing[$i]->qty_break;
        }
        $this->data->category_name=$this->fetch['category_name'];
        $this->data->subcategory_name=$this->fetch['subcategory_name'];
    }

    function import_update($data) {
        switch (TRUE) {
          case ($this->map->options['partcommunity']):
			$this->import_partcommunity($data);
            break;
          case ($this->map->options['erp']):
			$this->import_update_erp($data);
            break;
		  default:
			$this->import_update_inventory($data);
        }
	}
    function import_partcommunity($data) {
		$sku=str_replace(array("_"),array(" "),$data['sku']);
        $this->read($sku,"sku");
        if (!$this->meta->rows) $this->map->error['sku']="Invalid SKU: " . $data["sku"];
    	if ( ($this->map->update) && (!sizeof($this->map->error)) ) $this->update(TRUE);
    }
    function import_update_erp($data) {
        global $database;
        $this->read($data["sku"],"sku");
        if (!$this->meta->rows) $this->map->error['sku']="Invalid SKU: " . $data["sku"];
        $this->data->msrp=$data["_price0"];
        $pricing=array();
        $last_price=999999;
        $last_break=0;
        for ($i=1; $i < 11; $i++) {
			$qty_break=floatval($data[ "_break{$i}" ]);
			$qty_price=floatval($data[ "_price{$i}" ]);
			if ( (!$qty_break) || (!$qty_price) ) break;
			if ( ($qty_break > $last_break) && ($qty_price < $last_price) ) {
				$pricing[$last_break]=$qty_price;
                $last_break=$qty_break;
                $last_price=$qty_price;
			}
            if ($qty_break==999999) break;
        }
        $this->data->pricing=array();
        foreach ($pricing as $qty_break => $qty_price) {
			$this->data->pricing[]=new inventory_pricing_class(((!$qty_break) ? 0 : $qty_break + 1),$qty_price);
        }
    	if ( ($this->map->update) && (!sizeof($this->map->error)) ) $this->update(TRUE);
	}
    function import_update_inventory($data) {
        global $database;
        $this->read($data["sku"],"sku");
        switch (TRUE) {
          case (!strlen($data["sku"])):
		  case ( (!$this->meta->rows) && (!$this->map->complete) ):
          	$this->map->error['sku']="Invalid SKU: " . $data["sku"];
		}
        foreach ($data as $key => $value) {
			$this->data->$key=$value;
        }
        if (array_key_exists("category_name",$data)) {
			switch (TRUE) {
			  case ($data['category_name']):
				$name=strtolower($data['category_name'] . ":" . $data['subcategory_name']);
				if (array_key_exists($name,$this->map->subcategory)) {
                	list($this->data->category_id,$this->data->subcategory_id)=preg_split("/:/",$this->map->subcategory[$name]);
				  } else {
	                $this->data->category_id=0;
	                $this->data->subcategory_id=0;
				}
				break;
			  case ($this->map->options['subcategory_id']):
				$this->data->category_id=$database->subcategory->data->category_id;
				$this->data->subcategory_id=$database->subcategory->data->id;
				break;
            }
            if (!$this->data->subcategory_id) $this->map->error[]="Invalid subcategory: " . $data['category_name'] . ":" . $data['subcategory_name'];
        }
        if (array_key_exists("temp_competitor_sku",$data)) {
            $error=$this->competitor_sku($data['temp_competitor_sku'], TRUE);
            if ($error) $this->map->error['temp_competitor_sku']=$error;
        }
        if (isset($this->map->fields['leadtime']['leadtime_option']->column)) {
	        $this->data->leadtime_option=import_match($data['leadtime_option'],$this->constant->leadtime_option,TRUE);
	        $this->data->leadtime_text=$data['leadtime_text'];
	        switch (TRUE) {
	          case (!strlen($this->data->leadtime_option)):
	            $this->map->error['leadtime_option']="Allowed values: " . implode(", ", $this->constant->leadtime_option);
	            break;
	          case ( ($this->data->leadtime_option == "Weeks") && (!strlen($this->data->leadtime_text)) ):
	            $this->map->error['leadtime_text']="Cannot be blank";
	            break;
	          case ( ($this->data->leadtime_option != "Weeks") && (strlen($this->data->leadtime_text)) ):
	            $this->map->error['leadtime_text']="Must be blank";
	            break;
	        }
		}
        if (array_key_exists("parameter0",$data)) {
	        $this->data->parameters=array();
	        for ($i=0; $i < $this->constant->parameters; $i++) {
	            $this->data->parameter[$i]=$data["parameter" . $i];
	            $this->data->nparameter[$i]=$data["nparameter" . $i];
	        }
		}
        if (array_key_exists("price0",$data)) {
	        $this->data->pricing=array();
	        for ($i=0; $i < 10; $i++) {
	            $price_field="price" . $i;
	            $qty_break_field="qty_break" . $i;
	            $price=floatval($data[$price_field]);
	            $qty_break=floatval($data[$qty_break_field]);
	            if ($price) {
	                if (sizeof($this->data->pricing)) {
	                    if ($qty_break <= $last_qty_break) $this->map->error[$qty_break_field]=number_format($qty_break) . " <= " . number_format($last_qty_break);
	                    if ($price > $last_price) $errors[$price_field]=number_format($price) . " > " . number_format($last_price);
	                  } else {
	                    $qty_break=0;
	                }
	                $this->data->pricing[]=new inventory_pricing_class($qty_break,$price);
	                $last_qty_break=$qty_break;
	                $last_price=$price;
	            }
	        }
		}
        switch (TRUE) {
          case (sizeof($this->map->error)):
          case (!$this->map->update):
          	break;
		  case (!$this->meta->rows):
			$this->update(FALSE);
          	break;
          default:
			$this->update(TRUE,TRUE);
		}
    }
	function ajax($search_terms,$limit,$keys=array(),$options=array()) {
    	return ( ($options['detail']=="sku") ? $this->ajax_sku($search_terms,$limit,$keys,$options) : $this->ajax_search($search_terms,$limit,$keys,$options) );
    }
	function ajax_search($search_terms,$limit,$keys=array(),$options=array()) {
	    $results=array();
		$query=$this->ajax_query_sku($search_terms);
		if ($limit) $query[]="limit {$limit}";
		$this->query($query);
        if ($this->meta->rows) {
			$source="sku";
		  } else {
			$source="name";
	        $query=$this->ajax_query_name($search_terms);
	        if ($limit) $query[]="limit {$limit}";
            $this->query($query);
		}
	    while($this->fetch = $this->fetch_array() ) {
	        $this->fetch();
	        $results[]=$this->ajax_fill($source,$search_terms);
	    }
	    $this->free_result();
		return $results;
	}
    function ajax_sku($search_terms,$limit,$keys=array(),$options=array()) {
	    $query_where=array();
        $query_where[]=new query_where("inventory.sku","like",$search_terms[0]. "%");
		$query_where[]=new query_where("inventory.active","=",1);
        $query=array("select");
        $query[]="category.name as 'category.name',";
        $query[]="subcategory.name as 'subcategory.name',";
        $query[]="inventory.* from inventory";
        $query[]="left join subcategory on subcategory.id=inventory.subcategory_id";
        $query[]="left join category on category.id=inventory.category_id";
        $query[]=$this->where($query_where);
        $query[]="order by inventory.sku";
        $query[]="limit {$limit}";
        $this->query($query);
        $results=array();
	    while($this->fetch = $this->fetch_array() ) {
	        $this->fetch();
            $data=array();
            $data['label']=$this->data->sku;
            $data['category_name']=$this->fetch['category.name'];
            $data['subcategory_name']=$this->fetch['subcategory.name'];
            $data['category_subcategory_id']=$this->data->category_id . ":" . $this->data->subcategory_id;
	        $results[]=$data;
	    }
	    $this->free_result();
		return $results;
    }
	function ajax_query_sku($search_terms=array(),$options=array()) {
    global $database, $menu;
		if (!is_array($search_terms)) $search_terms=preg_split("/ /",$search_terms);
	    $query_where=array();
        $query_where[]=new query_where("inventory.active","=",1);
        $query_where[]=new query_where("category.active","=",1);
        if ($database->user->access("Administrator",FALSE)) {
			$query_where[]=new query_where("subcategory.status","in",array("Active","Pending"));
		  } else {
			$query_where[]=new query_where("subcategory.status","=","Active");
		}
        $query_where[]=new query_where("subcategory.id","in",$menu->subcategory);
        $query_where['sku'][]=new query_where("inventory.sku","=",implode(" ",$search_terms));
        $query_where['sku'][]=new query_where("inventory_xref.competitor_sku","=",implode(" ",$search_terms),"or");

        $select=array();
        $select[]="inventory.sku as 'code'";
        $select[]="concat(category.name,': ',subcategory.name,' (',inventory.sku,')') as 'ajax_label'";
        $left_join=array();
        $left_join[]="left join inventory_xref on inventory_xref.sku=inventory.sku";
        $left_join[]="left join subcategory on subcategory.id=inventory.subcategory_id";
        $left_join[]="left join category on category.id=subcategory.category_id";

        $group_by=array();
        $group_by[]="group by inventory.sku";
        $group_by[]="order by category.sort_order,category.name,subcategory.name";
        if ($options['catalog']) {
			$select[]="inventory.*";
            $group_by[]="limit 1";
        }
        $query=array("select");
        $query[]=implode(",",$select);
        $query[]="from inventory";
        $query[]=implode("\n",$left_join);
        $query[]=trim($this->where($query_where));
        $query[]=implode("\n",$group_by);
		return $query;
	}
	function ajax_query_name($search_terms=array(),$options=array()) {
    global $database, $menu;
		if (!is_array($search_terms)) $search_terms=preg_split("/ /",$search_terms);
		$query_search=array();
        $query_search['search_terms']=array();
	    foreach ($search_terms as $item) {
			if ($_SESSION['user']->language_code=="EN") {
	    		$query_search['search_terms'][]=new query_where("concat(category.name,' ',subcategory.name,' ',coalesce(subcategory.search_words,''))","like","%" . $item . "%");
			  } else {
	    		$query_search['search_terms'][]=new query_where("concat(ifnull(content_category.name,category.name),' ',ifnull(content_subcategory.name,subcategory.name),' ',coalesce(subcategory.search_words,''))","like","%" . $item . "%");
			}
	    }
	    $query_where=array();
        $query_where[]=new query_where("inventory.active","=",1);
        $query_where[]=new query_where("category.active","=",1);
        $query_where[]=new query_where("not isnull(category.id)","","");
        if ($database->user->access("Administrator",FALSE)) {
			$query_where[]=new query_where("subcategory.status","in",array("Active","Pending"));
		  } else {
			$query_where[]=new query_where("subcategory.status","=","Active");
		}
        $query_where[]=new query_where("subcategory.id","in",$menu->subcategory);
        $query_where[]=new query_where("not isnull(subcategory.id)","","");
        if (array_key_exists("subcategory_id",$options)) $query_where[]=new query_where("inventory.subcategory_id","=",$options['subcategory_id']);
        $query_where[]=new query_where($this->where($query_search,"omit"),"","","and_group");
        $select=array();
        $select[]="subcategory.id as 'code'";
        $select['ajax_label']="concat(category.name,': ',subcategory.name) as 'ajax_label'";
        $left_join=array();
        $left_join[]="left join subcategory on subcategory.id=inventory.subcategory_id";
        $left_join[]="left join category on category.id=subcategory.category_id";
        $group_by=array();
        $group_by[]="ajax_label";
        $order_by=array();
        $order_by[]="category.sort_order";
        $order_by[]="category.name";
        $order_by[]="subcategory.sort_order";
        $order_by[]="subcategory.name";
        $order_by[]="inventory.sort_order";
        $order_by[]="inventory.sku";
        if ($options['catalog']) {
        	$select[]="inventory.*";
			$group_by[]="inventory.sku";
		}
        if ($_SESSION['user']->language_code !="EN") {
			$select['ajax_label']="concat( ifnull(content_category.name,category.name),': ',ifnull(content_subcategory.name,subcategory.name) ) as 'ajax_label'";
	        $left_join[]="left join content as content_category on content_category.record_type='category' and content_category.record_id=category.id and content_category.language_code=" . fn_escape($_SESSION['user']->language_code);
	        $left_join[]="left join content as content_subcategory on content_subcategory.record_type='subcategory' and content_subcategory.record_id=subcategory.id and content_subcategory.language_code=" . fn_escape($_SESSION['user']->language_code);
        }
        $results=array("select");
        $results[]=implode(",\n",$select);
        $results[]="from inventory";
        $results[]=implode("\n",$left_join);
        $results[]=trim($this->where($query_where));
        $results[]="group by " . implode(", ",$group_by);
        $results[]="order by " . implode(", ",$order_by);
		return $results;
    }
    function ajax_fill($source,$search_terms=array()) {
//		if ((func_num_args()) > 1) return;
        $results=array();
		$results['value']=$this->fetch['code'];
        if (sizeof($search_terms)) $results['value'] .= "|" . implode(" ",$search_terms);
		$results['label']=utf8_encode($this->fetch['ajax_label']);
		$results['source']=$source;
        return $results;
    }
    function scs_hotfix($execute=FALSE) {
        $this->hotfix=new mysql_hotfix_class("inventory", "Inventory");
        $this->hotfix->version("08/21/2024", "Add competitor_sku");
        $this->hotfix->version("07/22/2024", "Create empty prj");
        $this->hotfix->version("07/28/2022", "Initial release");
        $this->hotfix->process($execute);
    }
    function scs_hotfix_20240821($meta) {
        global $database;
        $this->hotfix->trace[]=__FUNCTION__;

        $this->transaction("start");
        $fields=array();
        $fields[]="DROP COLUMN `xprj`";
        $fields[]="ADD COLUMN `competitor_sku` MEDIUMTEXT NULL AFTER `new_date`";
        $query=array("ALTER TABLE `inventory`");
        $query[]=implode(", ", $fields);
        $this->query($query);
        $meta->count=$this->affected_rows();

        $items=array();
        $query=array("select inventory_xref.sku, inventory_xref.competitor_sku from inventory_xref");
        $query[]="left join inventory on inventory.sku=inventory_xref.sku";
        $query[]="where not isnull(inventory.sku)";
        $query[]="order by inventory_xref.sku";
        $database->temp->query($query);
        while ($database->temp->fetch = $database->temp->fetch_array()) {
            $sku=$database->temp->fetch['sku'];
            if (!array_key_exists($sku, $items)) $items[$key]=array();
            $items[$sku][]=str_replace(",", "", $database->temp->fetch['competitor_sku']);
        }

        foreach ($items as $sku => $item) {
            $query=array("update inventory");
            $query[]="set competitor_sku=" . fn_escape(implode(",",$item));
            $query[]="where sku=" . fn_escape($sku);
            $database->temp->query($query);
        }
        $this->transaction("comment");
    }
    function scs_hotfix_20240722($meta) {
        global $database;
        $this->hotfix->trace[]=__FUNCTION__;
        $query=array("ALTER TABLE `inventory`");
        $query[]="CHANGE COLUMN `prj` `xprj` MEDIUMTEXT NULL DEFAULT NULL,";
        $query[]="ADD COLUMN `prj` MEDIUMTEXT NULL AFTER `subcategory_id`;";
        $this->query($query);
        $meta->count=$this->affected_rows();
    }
}
class inventory_constant_class {
	var $parameters=20;
	var $documents=array("overview"=>"Overview","features"=>"Features","applications"=>"Applications","specials"=>"Specials");
    var $leadtime_option=array("Subcategory","In Stock","Weeks","Call");
}
class inventory_data_class {
	var $id=0;
	var $sku;
	var $category_id=0;
	var $subcategory_id=0;
    var $prj;
    var $parameter=array();
    var $nparameter=array();
	var $documents=array();
	var $pricing=array();
    var $sort_order=0;
	var $active=1;
    var $new_date=0;
    var $competitor_sku=array();
    var $leadtime_option="Subcategory";
    var $leadtime_text;
}
class inventory_pricing_class {
	var $price=0;
    var $qty_break=0;
    function __construct($qty_break,$price) {
		$this->qty_break=$qty_break;
		$this->price=$price;
    }
}
/*
CREATE TABLE `inventory` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sku` varchar(80) NOT NULL,
  `category_id` int(11) DEFAULT '0',
  `subcategory_id` int(11) DEFAULT '0',
  `prj` mediumtext,
  `image_url` mediumtext,
  `documents` mediumtext,
  `pricing` mediumtext,
  `active` int(10) unsigned DEFAULT '1',
  `leadtime_option` varchar(20) NOT NULL DEFAULT '',
  `leadtime_text` mediumtext,
  `sort_order` int(10) unsigned NOT NULL DEFAULT '0',
  `new_date` date NOT NULL DEFAULT '0000-00-00',
  `competitor_sku` mediumtext,
  `parameter0` varchar(80) NOT NULL DEFAULT '',
  `parameter1` varchar(80) NOT NULL DEFAULT '',
  `parameter2` varchar(80) NOT NULL DEFAULT '',
  `parameter3` varchar(80) NOT NULL DEFAULT '',
  `parameter4` varchar(80) NOT NULL DEFAULT '',
  `parameter5` varchar(80) NOT NULL DEFAULT '',
  `parameter6` varchar(80) NOT NULL DEFAULT '',
  `parameter7` varchar(80) NOT NULL DEFAULT '',
  `parameter8` varchar(80) NOT NULL DEFAULT '',
  `parameter9` varchar(80) NOT NULL DEFAULT '',
  `parameter10` varchar(80) NOT NULL DEFAULT '',
  `parameter11` varchar(80) NOT NULL DEFAULT '',
  `parameter12` varchar(80) NOT NULL DEFAULT '',
  `parameter13` varchar(80) NOT NULL DEFAULT '',
  `parameter14` varchar(80) NOT NULL DEFAULT '',
  `parameter15` varchar(80) NOT NULL DEFAULT '',
  `parameter16` varchar(80) NOT NULL DEFAULT '',
  `parameter17` varchar(80) NOT NULL DEFAULT '',
  `parameter18` varchar(80) NOT NULL DEFAULT '',
  `parameter19` varchar(80) NOT NULL DEFAULT '',
  `nparameter0` decimal(18,9) DEFAULT '0.000000000',
  `nparameter1` decimal(18,9) DEFAULT '0.000000000',
  `nparameter2` decimal(18,9) DEFAULT '0.000000000',
  `nparameter3` decimal(18,9) DEFAULT '0.000000000',
  `nparameter4` decimal(18,9) DEFAULT '0.000000000',
  `nparameter5` decimal(18,9) DEFAULT '0.000000000',
  `nparameter6` decimal(18,9) DEFAULT '0.000000000',
  `nparameter7` decimal(18,9) DEFAULT '0.000000000',
  `nparameter8` decimal(18,9) DEFAULT '0.000000000',
  `nparameter9` decimal(18,9) DEFAULT '0.000000000',
  `nparameter10` decimal(18,9) DEFAULT '0.000000000',
  `nparameter11` decimal(18,9) DEFAULT '0.000000000',
  `nparameter12` decimal(18,9) DEFAULT '0.000000000',
  `nparameter13` decimal(18,9) DEFAULT '0.000000000',
  `nparameter14` decimal(18,9) DEFAULT '0.000000000',
  `nparameter15` decimal(18,9) DEFAULT '0.000000000',
  `nparameter16` decimal(18,9) DEFAULT '0.000000000',
  `nparameter17` decimal(18,9) DEFAULT '0.000000000',
  `nparameter18` decimal(18,9) DEFAULT '0.000000000',
  `nparameter19` decimal(18,9) DEFAULT '0.000000000',
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku_key` (`sku`),
  FULLTEXT KEY `search_key` (`sku`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Inventory (08/21/2024)'
*/
?>