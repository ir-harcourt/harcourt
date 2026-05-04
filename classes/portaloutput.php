<?php
require_once "classes/portal.php";
require_once "classes/portalitem.php";
require_once "classes/portalcategory.php";
require_once "classes/portalcontent.php";
class portaloutput_class {
    var $error;
    var $portal_id;
    var $language_code;
    var $preview;
    var $url;
	var $header;
    var $output=array();
    var $image_list=array();
    var $item_list=array();
    var $category_list=array();
    var $inventory_list=array();
    var $content_list=array();
    var $portal_content=array();
    var $item=array();
    var $category=array();
    var $inventory=array();
    var $content=array();
    var $query=array();
    function scs_table_version() {
        $results=array();
        $results['11/18/2025']="Initial release";
        return $results;
    }
    function __construct($options=array()) {
        global $database, $forms, $menu;
        $this->portal_id=(array_key_exists("portal_id", $options)) ? intval($options['portal_id']) : $_SESSION['user']->portal_id;
        $this->language_code=($options['language_code']) ? $options['language_code'] : $_SESSION['user']->language_code;
        $this->preview=($options['preview']) ? $options['preview'] : "";
        $this->url=$menu->page->products->url;
        $this->error=$this->load();
    }
    function load() {
        global $database;
		$database->portal->read($this->portal_id);
        $this->query["Portal"]=array($database->portal->meta->query);
        if ( (!$database->portal->meta->rows) || (!$database->portal->data->active) ) return "Missing or inactive portal";

	    $query_where=array();
	    $query_where[]=new query_where("record_type","=","portal");
	    $query_where[]=new query_where("record_id","=",$this->portal_id);
	    $query_where[]=new query_where("language_code","in",array("EN",$this->language_code));
        $query=array("select");
        $query[]="if (language_code='EN',2,1) as sort_key,";
        $query[]="content.* from content";
        $query[]=$database->where($query_where);
        $query[]="order by sort_key";
        $query[]="limit 1";
	    $database->content->query($query);
        $this->query["Description/Headline"]=$query;
	    $database->content->fetch(TRUE);
		$this->header=$database->content->data;
        if (!$database->content->meta->rows) return "Missing portal name";

	    $query_where=array();
	    $query_where[]=new query_where("content.record_type","=","portalcontent");
	    $query_where[]=new query_where("content.record_id","=","portalcontent.id","","field");
	    $query_where[]=new query_where("content.language_code","in",array("EN",$this->language_code));
        $query=array("select");
	    $query[]="if (content.language_code='EN',2,1) as sort_key,";
        $query[]="content.* from content";
        $query[]="left join portalcontent on portalcontent.portal_id=" . fn_escape($this->portal_id) ." and portalcontent.form='Portal Image' and portalcontent.active=1";
        $query[]=$database->where($query_where);
        $query[]="order by sort_key,content.name";
	    $database->content->query($query);
        $this->query["Image"]=$query;
        if ($database->content->meta->rows) {
	        while ($database->content->fetch = $database->content->fetch_array()) {
	            $database->content->fetch();
				if (!array_key_exists($database->content->data->record_id, $this->image_list)) $this->image_list[$database->content->data->record_id]=$database->content->data;
	        }
		}

	    $query_where=array();
	    $query_where[]=new query_where("portal_id","=",$this->portal_id);
	    $query_where[]=new query_where("active","=",1);
        $query=array();
	    $query[]="select * from portalcategory";
	    $query[]=trim($database->where($query_where));
	    $query[]="order by output_order,name";
		$database->portalcategory->query($query);
        $this->query["Portal Category"]=$query;
		while ($database->portalcategory->fetch = $database->portalcategory->fetch_array()) {
			$database->portalcategory->fetch();
            $database->portalcategory->data->count=0;
			$this->category[$database->portalcategory->data->id]=$database->portalcategory->data;
	        $this->output[$database->portalcategory->data->id]=array();
			if ( ($database->portalcategory->data->headline_portalcontent_id) && (!in_array($database->portalcategory->data->headline_portalcontent_id,$this->content_list)) ) $this->content_list[]=$database->portalcategory->data->headline_portalcontent_id;
			if ( ($database->portalcategory->data->image_portalcontent_id) && (!in_array($database->portalcategory->data->image_portalcontent_id,$this->content_list)) ) $this->content_list[]=$database->portalcategory->data->image_portalcontent_id;
            if ($database->portalcategory->data->subcategory_id) {
            	$this->inventory_list[$database->portalcategory->data->subcategory_id]=$database->portalcategory->data->id;
			  } else {
            	$this->category_list[$database->portalcategory->data->id]=$database->portalcategory->data->id;
			}
        }

        if (sizeof($this->inventory_list)) {
        	$query_where=array();
	        $query_where[]=new query_where("inventory.active","=","1");
	        $query_where[]=new query_where("category.active","=","1");
	        if ($database->user->access("Administrator",FALSE)) {
	            $query_where[]=new query_where("subcategory.status","in",array("Active","Pending"));
	          } else {
	            $query_where[]=new query_where("subcategory.status","=","Active");
	        }
            $query_where[]=new query_where("inventory.subcategory_id","in",array_keys($this->inventory_list));
            $query=array();
            $query[]="select subcategory.* from subcategory";
            $query[]="left join inventory on inventory.subcategory_id=subcategory.id";
            $query[]="left join category on category.id=subcategory.category_id";
	        $query[]=trim($database->where($query_where));
	        $query[]="group by subcategory.id";
            $database->subcategory->query($query);
            $this->query["Subcategory"]=$query;
            while ($database->subcategory->fetch = $database->subcategory->fetch_array()) {
				$database->subcategory->fetch();
				$this->category[ $this->inventory_list[$database->subcategory->data->id] ]->count=1;
	            $this->output[ $this->inventory_list[$database->subcategory->data->id] ][]=$database->subcategory->data->id;
                $this->inventory[ $this->inventory_list[$database->subcategory->data->id] ]=$database->subcategory->data;
            }
		}

        if (sizeof($this->category_list)) {
	    	$query_where=array();
            $query_where[]=new query_where("portal_id","=",$this->portal_id);
			$query_where[]=new query_where("portalcategory_id","in",array_keys($this->category_list));
			$query_where[]=new query_where("active","=",1);
            $query=array();
            $query[]="select * from portalitem";
	        $query[]=trim($database->where($query_where));
	        $query[]="order by output_order,name";
            $database->portalitem->query($query);
            $this->query["Portal Item"]=$query;
            while ($database->portalitem->fetch = $database->portalitem->fetch_array()) {
				$database->portalitem->fetch();
				$this->category[ $database->portalitem->data->portalcategory_id ]->count++;
	            $this->output[$database->portalitem->data->portalcategory_id][]=$database->portalitem->data->id;
	            $this->item[$database->portalitem->data->id]=$database->portalitem->data;
	            $this->item_list[]=$database->portalitem->data->id;
	            if (!in_array($database->portalitem->data->portalcategory_id,$this->category_list)) $this->category_list[]=$database->portalitem->data->portalcategory_id;
	            if ( ($database->portalitem->data->pdf_portalcontent_id) && (!in_array($database->portalitem->data->pdf_portalcontent_id,$this->content_list)) ) $this->content_list[]=$database->portalitem->data->pdf_portalcontent_id;
	            if ( ($database->portalitem->data->cad_portalcontent_id) && (!in_array($database->portalitem->data->cad_portalcontent_id,$this->content_list)) ) $this->content_list[]=$database->portalitem->data->cad_portalcontent_id;
	            if ( ($database->portalitem->data->hyperlink_portalcontent_id) && (!in_array($database->portalitem->data->hyperlink_portalcontent_id,$this->content_list)) ) $this->content_list[]=$database->portalitem->data->hyperlink_portalcontent_id;
	            if ( ($database->portalitem->data->other_portalcontent_id) && (!in_array($database->portalitem->data->other_portalcontent_id,$this->content_list)) ) $this->content_list[]=$database->portalitem->data->other_portalcontent_id;
            }
        }
        foreach ($this->category as $key => $item) {
			if (!$item->count) unset ($this->category[$key]);
        }
        $query_where=array();
        if (sizeof($this->category_list)) {
	        $query_where['portalcategory'][]=new query_where("record_type","=","portalcategory");
	        $query_where['portalcategory'][]=new query_where("record_id","in",$this->category_list);
	        $query_where['portalcategory'][]=new query_where("language_code","in",array("EN",$this->language_code));
		}
        if (sizeof($this->item_list)) {
	        $query_where['portalitem'][]=new query_where("record_type","=","portalitem","or");
	        $query_where['portalitem'][]=new query_where("record_id","in",$this->item_list);
	        $query_where['portalitem'][]=new query_where("language_code","in",array("EN",$this->language_code));
		}
        if (sizeof($this->content_list)) {
	        $query_where['portalcontent'][]=new query_where("record_type","=","portalcontent","or");
	        $query_where['portalcontent'][]=new query_where("record_id","in",$this->content_list);
	        $query_where['portalcontent'][]=new query_where("language_code","in",array("EN",$this->language_code));
		}
        if (!sizeof($query_where)) return "No portal content";

        $query=array("select");
        $query[]="if (language_code='EN',2,1) as sort_key,";
        $query[]="content.* from content";
        $query[]=$database->where($query_where);
        $query[]="order by record_type,record_id,sort_key";
		$database->content->query($query);
        $this->query["Content"]=$query;
		while ($database->content->fetch = $database->content->fetch_array() ) {
			$database->content->fetch();
			switch ($database->content->data->record_type) {
			  case "portalcategory":
				switch (TRUE) {
                  case (!array_key_exists($database->content->data->record_id,$this->category)):
                  case (isset($this->category[$database->content->data->record_id]->language_code)):
					break;
				  default:
					$this->category[$database->content->data->record_id]->language_code=$database->content->data->language_code;
					$this->category[$database->content->data->record_id]->name=$database->content->data->name;
					$this->category[$database->content->data->record_id]->headline=$database->content->data->description;
                }
                break;
			  case "portalitem":
				switch (TRUE) {
                  case (!array_key_exists($database->content->data->record_id,$this->item)):
                  case (isset($this->item[$database->content->data->record_id]->language_code)):
					break;
				  default:
					$this->item[$database->content->data->record_id]->language_code=$database->content->data->language_code;
					$this->item[$database->content->data->record_id]->name=$database->content->data->name;
                }
				break;
			  case "portalcontent":
				switch (TRUE) {
                  case (!array_key_exists($database->content->data->record_id,$this->content)):
                    $this->portalcontent[$database->content->data->record_id]=$database->content->data;
					$this->content[$database->content->data->record_id]=$this->content($database->content->data);
                    break;
                }
			}
        }
    }
    function image($options=array()) {
        global $database;
		if ( (!$this->header->id) || (!sizeof($this->image_list)) ) return;
        if (!array_key_exists("class",$options)) $options['class']="center";
        $results=array();
        foreach ($this->image_list as $data) {
            $results[]="<div class={$options['class']}>" . $this->content($data) . "</div>";
        }
        return "<div>" . implode("", $results) . "</div>";
    }
    function headline() {
        global $database, $forms;
		if ( (!$this->header->id) || (!strlen($this->header->description)) ) return;
		return "<div class=portal_headline>{$this->header->description}</div>";
    }
    function summary() {
	    global $database, $forms, $menu;
        if (!sizeof($this->category_list)) return;
        $results=array();
        $results[]="<div><h1>Custom Products</h1></div>";
        $results[]="<div class=catalog_subcategory_list>";
        $results[]="<ul>";
        foreach ($this->output as $category_id => $category_items) {
			$database->portalcategory->data=$this->category[$category_id];
        	$text=array();
	        $url_query=array();
            switch (TRUE) {
			  case (!array_key_exists($category_id,$this->category)):
              case ( ($database->portalcategory->data->subcategory_id) && (!$this->inventory[$category_id]->thumbnail_url) ):
              case ( (!$database->portalcategory->data->subcategory_id) && (!$database->portalcategory->data->image_portalcontent_id) ):
              	break;
              case ($database->portalcategory->data->subcategory_id):
	            $url_query['action']="subcategory";
	            $url_query['category_id']=$this->inventory[$category_id]->category_id;
	            $url_query['subcategory_id']=$this->inventory[$category_id]->id;
	            $text[]=$forms->img($this->inventory[$category_id]->thumbnail_url);
	            $text[]="<br>{$database->portalcategory->data->name}";
                break;
              default:
	            $url_query['action']="portal";
	            $url_query['portal_item']="";
	            $url_query['portal_category']=$category_id;
	            $text=array();
                $text[]=$this->content[$database->portalcategory->data->image_portalcontent_id];
	            $text[]="<br>{$database->portalcategory->data->name}";
                break;
            }
            if (sizeof($text)) {
                $output=array("<li>");
                if ($this->preview) {
                    $output[]=fn_href(implode("",$text),fn_escape_javascript($this->preview, $url_query));
                  } else {
                    $output[]=fn_href(implode("",$text),$this->url,$url_query);
                }
                $output[]="</li>";
                $results[]=implode("", $output);
            }
        }
        $results[]="</ul>";
        $results[]="</div>";
		return implode("",$results);
	}
    function detail($portal_category) {
        global $database, $forms;
        $database->portalcategory->data=$this->category[$portal_category];
        if (!sizeof($this->output[$portal_category])) return;
        $results=array();
	    $results[]="<div>";
        if ($database->portalcategory->data->image_portalcontent_id) $results[]="<span class=portal_detail_headline>{$this->content[$database->portalcategory->data->image_portalcontent_id]}</span>";
 	    $results[]="<span class=portal_detail_headline>{$database->portalcategory->data->name}</span>";
	    $results[]="</div>";
        $results[]="<div class='portal_detail'>";
        foreach ($this->output[$portal_category] as $item) {
	        $database->portalitem->data=$this->item[$item];
            if ($database->portalitem->data->subcategory_id) {
              } else {
	            $links=array();
	            if ($database->portalitem->data->pdf_portalcontent_id) $links[]=$this->portal_content($database->portalitem->data->pdf_portalcontent_id,"PDF");
	            if ($database->portalitem->data->cad_portalcontent_id) $links[]=$this->portal_content($database->portalitem->data->cad_portalcontent_id,"CAD");
	            if ($database->portalitem->data->hyperlink_portalcontent_id) $links[]=$this->portal_content($database->portalitem->data->hyperlink_portalcontent_id);
	            if ($database->portalitem->data->other_portalcontent_id) $links[]=$this->portal_content($database->portalitem->data->other_portalcontent_id,$database->portalitem->data->other_name);
	            $results[]="<div class='portal_detail'>" . $database->portalitem->data->name . ": " . implode(" | ",$links) . "</div>";
            }
        }
        $results[]="</div>";
        return implode("", $results);
    }
    function content($data, $query_options=array(), $forms_options=array()) {
        global $database, $forms;
        $this->trace[]=__FUNCTION__;
        switch ($data->filetype) {
		  case "image":
	        return "<img src='" . fn_url("/scs_file.php",array("content_id"=>$database->content->encode($data->id))) . "'>";
          case "html":
			return $forms->iframe("viewer" . $data->id,fn_url("/content_viewer.php",array("viewer"=>$database->content->encode($data->id))));
		  default:
			return $data->content;
        }
    }
    function portal_content($id,$text="",$options=array()) {
        global $database, $forms;
		if (!array_key_exists($id,$this->portalcontent)) return "Content Missing: {$id}";
    	$url_options=array();
		$url_options['content_id']=$database->content->encode($this->portalcontent[$id]->id);
		switch ($this->portalcontent[$id]->filetype) {
		  case "":
			return;
          case "hyperlink":
            return fn_href($this->portalcontent[$id]->name, $this->portalcontent[$id]->html_content,array(),array("target"=>"_blank"));
		  case "html":
			return $forms->iframe($this->portalcontent[$id]->filetype . "_" . $id,fn_url("/scs_file.php",$url_options));
		  case "image":
			if (!array_key_exists("title",$options)) $options['title']=$this->portalcontent[$id]->name;
			$url_options['resize']=TRUE;
			return $forms->img("",$options,$url_options);
		  default:
			return fn_href($text,"/scs_file.php",$url_options,array("target"=>"_blank"));
		}
    }
}
?>