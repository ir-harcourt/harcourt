<?php
require_once "scs_header.php";
$database->user->access("Customer");
print header("Access-Control-Allow-Origin: *");
$catalog=new catalog_output_class();

class catalog_output_class {
	var $action;
    var $document=0;
    var $page=0;
    var $item=0;
    var $access=0;
    var $sku;
    var $title=array("Products");
    var $search_source;
    var $search_code;
    var $search_term;
    var $search_count=array("total"=>0,"category"=>0,"subcategory"=>0,"inventory"=>0);
    var $category_id=0;
    var $subcategory_id=0;
    var $new_date=0;
    var $tabs;
    var $inventory;
    var $portal_category=0;
	var $category=array();
    var $subcategory=array();
    var $parameter=array();
    var $php_self;
    var $email;
    var $request=array();
	function scs_table_version() {
		$results=array();
        $results['10/28/2020']="Alter page_item_break";
        $results['08/29/2020']="WordPress integration";
        $results['02/18/2020']="Drop sonic, prj_new";
        $results['08/23/2019']="Integrate smart SKU";
        $results['08/12/2019']=array("Show categories w/o SKU's","Add subcategory output");
        $results['04/20/2019']="Update Product Range titles";
        $results['03/11/2019']="Update pricing";
        $results['06/22/2017']="Update pricing";
        $results['02/12/2017']="New layout";
        $results['08/10/2016']="Add sonic";
        $results['07/22/2016']="Add portal subcategory";
        $results['07/07/2016']="Add pending subcategory";
        $results['03/23/2016']="Add industry, portal";
        $results['11/24/2015']="Rewrite cart functions";
        $results['10/08/2015']="Add link name (PO 60302)";
        $results['09/23/2015']="Add page_breaks";
        $results['08/06/2015']="Revise sortable tabs";
        $results['07/08/2015']="Add sortable tabs";
        $results['06/03/2015']="Add download tab";
        $results['05/29/2015']="Drop inventory_xref competitor";
        $results['04/15/2015']="Log activity";
        $results['04/06/2015']="Automatic PRJ landing page";
        $results['03/12/2015']=array("Parameteric search","Add category option");
        $results['02/12/2015']="Integrate new skins";
        $results['01/06/2015']="Include category tab on SKU drilldown";
        $results['08/25/2014']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
	function __construct() {
	global $database, $forms, $menu;
	require_once "classes/category.php";
	require_once "classes/subcategory.php";
	require_once "classes/inventory.php";
	require_once "classes/inventory_xref.php";
	require_once "classes/portalcontent.php";
	require_once "classes/configure.php";
		$this->email=$_COOKIE['harcourt'];
        if (isset($_SESSION['scscpq_wp'])) {
	        $referer_url=parse_url($_SERVER['HTTP_REFERER']);
	        parse_str($referer_url['query'],$this->request);
		   } else {
           	$this->request=$_GET;
		}
		$this->action=$this->request['action'];
		$this->page=intval($this->request['scs_page']);
		$this->item=intval($this->request['item']);
		$this->document=intval($this->request['document']);
		$this->new_date=intval($this->request['new_date']);
        $this->search_source=$this->request['search_source'];
        $this->portal_category=$this->request['portal_category'];
    	switch (TRUE) {
          case (!$_SESSION['user']->id):
			break;
          case ($this->email):
          case (isset($_SESSION['user']->bot_code)):
          case (isset($_SESSION['user']->punchout)):
	        $this->access=TRUE;
			break;
          case ($this->action == "email"):
			$email=new email_class($this->request['email'],TRUE);
            if (!$email->error) {
            	$this->email=$email->address;
            	$menu->cookie($email->address);
	        	$this->access=TRUE;
			}
            break;
          case (!$this->email):
	        $_SESSION['document_viewer']=( ($_SESSION['user']->login_document_id) ? $_SESSION['user']->login_document_id : 156);
	        $_SESSION['document_viewer_override']=0;
            require_once "document_viewer.php";
          	return;
        }
        $this->php_self=( ($_SESSION['scscpq_wp']) ? "/catalog/" : $_SERVER['PHP_SELF']);
		if ($_SESSION['user']->portal_id) {
        	$database->portalcontent->portal_load($_SESSION['user']->portal_id);
            switch (TRUE) {
              case (!$database->portal->data->active):
              	$_SESSION['user']->portal_id=0;
              	break;
              case (isset($_SESSION['portal_legacy'])):
              	$database->portal->data->legacy=$_SESSION['portal_legacy'];
			}
		}
        switch (TRUE) {
          case (!($_SESSION['user']->portal_id)):
          case (!$database->portal->data->active):
          case (!$database->portal->data->legacy):
            break;
          default:
            $referer_url=parse_url($_SERVER['HTTP_REFERER']);
            $request_url=parse_url($_SERVER['REQUEST_URI']);
            switch (TRUE) {
              case ($request_url['query']):
              case ($referer_url['path'] == $this->php_self):
                break;
              default:
                $this->action="legacy_menu";
            }
        }
		list($this->search_code,$this->search_term)=preg_split("/\|/",$this->request['search_code'],2);
        for ($parameter_id=0; $parameter_id < $database->subcategory->constant->parameters; $parameter_id ++) {
			$this->parameter[$parameter_id]=trim($this->request["parameter{$parameter_id}"]);
        }
        $this->search_complete=$this->request['search_complete'];
		switch ($this->search_source) {
		  case "sku":
			$this->sku=$this->search_code;
            break;
		  case "name":
			$this->subcategory_id=$this->search_code;
            break;
		  case "term":
            break;
		  default:
			$this->sku=$this->request['sku'];
			$this->category_id=intval($this->request['category_id']);
			$this->subcategory_id=intval($this->request['subcategory_id']);
		}
		$database->category->query("select * from category");
        while ($database->category->fetch = $database->category->fetch_array() ) {
			$database->category->fetch();
            $this->category[$database->category->data->id]=$database->category->data;
        }
        $database->category->free_result();
		$database->subcategory->query("select * from subcategory");
        while ($database->subcategory->fetch = $database->subcategory->fetch_array() ) {
			$database->subcategory->fetch();
            $this->subcategory[$database->subcategory->data->id]=$database->subcategory->data;
        }
        $database->subcategory->free_result();
		$query=array("select subcategory_id as 'subcategory_id' from inventory");
        $query[]="where new_date >= " . fn_escape($database->inventory->new_date());
        $query[]="and inventory.active = '1'";
        $query[]="group by subcategory_id";
		$database->temp->query($query);
        while ($database->temp->fetch = $database->temp->fetch_array() ) {
			if (array_key_exists($database->temp->fetch['subcategory_id'],$this->subcategory)) $this->subcategory[$database->temp->fetch['subcategory_id']]->new_inventory=TRUE;
        }
		$database->temp->free_result();
		switch (TRUE) {
          case ($this->action != "search"):
          	break;
		  case ($this->search_source == "sku"):
			$database->inventory->read($this->sku,"sku");
            if (!$database->inventory->meta->rows) {
            	$this->action="";
			  } else {
                $this->subcategory_id=$database->inventory->data->subcategory_id;
                $this->category_id=$this->subcategory[$this->subcategory_id]->category_id;
                $this->inventory=$database->inventory->data;
			}
            break;
		  case ($this->search_source == "name"):
            $this->category_id=$this->subcategory[$this->search_code]->category_id;
            $this->subcategory_id=$this->search_code;
            break;
		}
        if ( (!$_SESSION['user']->portal_id) && (in_array($this->action,array("portal","legacy_menu","legacy_products"))) ) $this->action="";
        switch (TRUE) {
          case ($this->action != "portal"):
        	break;
          case (!$_SESSION['user']->portal_id):
          case (!$this->portal_category):
		  case (!in_array($this->portal_category,$database->portalcontent->portal->category_list)):
			$this->action="";
            break;
        }
	    $query_where=array();
	    $query_where[]=new query_where("record_type","in",array("category","subcategory"));
	    $query_where[]=new query_where("record_item","=","");
	    $query_where[]=new query_where("language_code","in",array("EN",$_SESSION['user']->language_code));
	    $query=array("select");
        $query[]="if (language_code='EN',2,1) as sort_key,";
        $query[]="content.* from content";
	    $query[]=$database->where($query_where);
	    $query[]="order by record_type,sort_key";
	    $database->content->query($query);
        while ($database->content->fetch = $database->content->fetch_array() ) {
			$database->content->fetch();
            switch ($database->content->data->record_type) {
              case "category":
				if ( (array_key_exists($database->content->data->record_id,$this->category)) && (!isset($this->category[$database->content->data->record_id]->language_code)) ) {
					$this->category[$database->content->data->record_id]->language_code=$database->content->data->language_code;
					$this->category[$database->content->data->record_id]->name=$database->content->data->name;
					$this->category[$database->content->data->record_id]->description=$database->content->data->description;
                }
			  	break;
              case "subcategory":
				if ( (array_key_exists($database->content->data->record_id,$this->subcategory)) && (!isset($this->subcategory[$database->content->data->record_id]->language_code)) ) {
					$this->subcategory[$database->content->data->record_id]->language_code=$database->content->data->language_code;
					$this->subcategory[$database->content->data->record_id]->name=$database->content->data->name;
					$this->subcategory[$database->content->data->record_id]->description=$database->content->data->description;
                }
			  	break;
			}
		}
        $database->content->free_result();
        if ($this->subcategory_id) $this->category_id=$this->subcategory[$this->subcategory_id]->category_id;
        $referer=parse_url($_SERVER['HTTP_REFERER']);
		$options=array();
        $options['subcategory_id']=$this->subcategory_id;
        $options['comment']=array();
	    switch (TRUE) {
          case ($_SESSION['user']->bot_code):
          	break;
		  case ( ($this->search_source) && ($this->search_source == "sku") ):
			$options['comment'][]=$this->search_term;
			$options['sku']=$this->search_code;
			$database->log->update("search:sku",$options);
			break;
		  case ( ($this->search_source) && ($subcategory_id) ):
			$options['comment'][]=$this->search_code;
			$options['comment'][]=$this->subcategory[$subcategory_id]->name;
			$database->log->update("search:category");
			break;
		  case ($this->search_source):
			$options['comment'][]=$this->search_code;
			$database->log->update("search:general",$options);
			break;
          case ($this->subcategory_id):
          	if ( (!$this->sku) && (!$this->page) ) {
	            $options['comment'][]="Subcategory: " . $this->subcategory[$this->subcategory_id]->name;
	            $database->log->update("page:products",$options);
			}
            break;
          case ($this->category_id):
			$options['comment'][]="Category: " . $this->category[$this->category_id]->name;
			$database->log->update("page:products",$options);
          	break;
		  case ($referer['path'] != $this->php_self):
			$options['comment'][]="Landing Page";
			$database->log->update("page:products",$options);
            break;
	    }
	    if ($this->document) {
	        $database->document->read($this->document);
	        $database->content->page("document",$this->document);
	        if ( (!$database->document->meta->rows) || (!$database->content->meta->rows) ) $this->document=0;
	    }
	    switch (TRUE) {
	      case ($this->document):
	        $forms->title($database->document->data->name);
	        unset($forms->message[0]);
	        break;
	      case ($_SESSION['user']->portal_id):
	      case ($_SESSION['user']->landing_document_id):
	        break;
	    }
        $options=array("products_div"=>"products_div");
	    $forms->title(implode(" | ",$this->title));
	    unset($forms->message[0]);
	    $menu->head($options);
        if ( ($forms->title_constant) && (!sizeof($forms->message)) )  {
			$forms->title_constant="";
            $forms->message[]=fn_href("Products: Home",$menu->page->products->url . ( ($this->category_id) ? "#category" . $this->category_id : ""));
		}
	    print $forms->message();
	    switch (TRUE) {
	      case (!$_SESSION['user']->id):
	      case ($_SESSION['user']->status != "Active"):
	      case (!$_SESSION['user']->catalog):
	        $_SESSION['document_viewer']=157;
	        $_SESSION['document_viewer_override']=0;
            require_once "document_viewer.php";
			return;
	      case (!$this->access):
	        break;
	      case ((!$this->document) && ($_SESSION['user']->landing_document_id)):
	        $_SESSION['document_viewer']=$_SESSION['user']->landing_document_id;
	        $_SESSION['document_viewer_override']=0;
            require_once "document_viewer.php";
	        break;
          case ($this->action == "legacy_menu"):
          	$this->legacy_menu();
          	break;
          case ($this->action == "legacy_products"):
          	$this->legacy_products();
          	break;
		  default:
			$this->route();
	    }
	    $menu->copyright();
	}
    function route() {
	global $database, $forms, $menu;
	    print $forms->open();
        print $forms->hidden("action",$this->action);
        print $forms->hidden("category_id",$this->category_id);
        print $forms->hidden("subcategory_id",$this->subcategory_id);
        print $forms->hidden("document",$this->document);
        print $forms->hidden("scs_page",$this->page);
        print $forms->hidden("item",$this->item);
		print $forms->hidden("sku");
		print $forms->hidden("search_complete");
		$this->tabs=new jquery_tab_class("products_jquery");
        $this->tabs->options("content",array("class"=>"products_content"));
        foreach ($database->language->constant->product_sortable as $key => $value) {
            if (!array_key_exists($key,$this->tabs->output_order)) $this->tabs->output_order($key,$value);
        }
        switch (TRUE) {
		  case ($this->search_source=="sku"):
			$this->search_sku();
			break;
		  case ($this->search_source=="name"):
          case ( (in_array($this->action,array("subcategory","sku"))) && (array_key_exists($this->subcategory_id,$this->subcategory)) ):
			$this->subcategory();
           break;
		  case ($this->search_source=="term"):
			$this->search_term();
			break;
		  case ($this->action=="portal"):
			$this->portal();
			break;
		  default:
			$this->summary();
		}
	    print $forms->close();
    }
    function search_term() {
    global $database, $forms, $menu;
	    $options=array();
	    $options['catalog']=TRUE;
	    if ($this->subcategory_id) $options['subcategory_id']=$this->subcategory_id;
		$query=$database->inventory->ajax_query_sku($this->search_code,$options);
		$database->inventory->query($query,TRUE);
        if (!$database->inventory->meta->found_rows) {
	        $query=$database->inventory->ajax_query_name($this->search_code,$options);
	        $database->inventory->query($query,TRUE);
	        switch (TRUE) {
	          case (strlen($this->search_code) < 2):
	            print "<p class=center>Your search term must include at least 2 characters</p>\n";
	            return;
	          case (!$database->inventory->meta->found_rows):
	            print "<p class=center>No results for search term: <b>" . $this->search_code . "</b></p>\n";
	            return;
	        }
		}
        $summary_list=array();
        $detail_list=array();
        while ($database->inventory->fetch = $database->inventory->fetch_array() ) {
            $database->inventory->fetch();
            $subcategory_id=$database->inventory->data->subcategory_id;
            $category_id=$this->subcategory[$subcategory_id]->category_id;
            if (!array_key_exists($category_id,$summary_list)) {
				$this->search_count['category']++;
            	$summary_list[$category_id]=array();
				$detail_list[$category_id]=array();
			}
            if (!in_array($subcategory_id,$summary_list[$category_id])) {
				$this->search_count['subcategory']++;
            	$summary_list[$category_id][]=$subcategory_id;
				$detail_list[$category_id][$subcategory_id]=array();
			}
            $detail_list[$category_id][$subcategory_id][]=$database->inventory->data;
			$this->search_count['inventory']++;
        }
		$this->category_id=key($detail_list);
        $this->subcategory_id=key($detail_list[$this->category_id]);
        switch (TRUE) {
          case ($this->search_count['inventory']==1):
			$database->inventory->data=$detail_list[$this->category_id][$this->subcategory_id][0];
			$this->output_sku();
            break;
          case ($this->item):
			$database->inventory->data=$detail_list[$this->category_id][$this->subcategory_id][($this->item - 1)];
			$this->output_sku();
            break;
          case ($this->search_count['subcategory']==1):
            $this->output_subcategory($detail_list[$this->category_id][$this->subcategory_id]);
            break;
		  default:
			$this->output_summary($summary_list);
		}
        $url_query=array();
        if (strlen($this->search_code)) $url_query['search_code']=$this->search_code;
        if ($this->new_date) $url_query['new_date']=1;
    }
    function query_subcategory() {
    global $database, $forms, $menu;
	    $query_where=array();
	    $query_where[]=new query_where("inventory.active","=","1");
	    $query_where[]=new query_where("category.active","=","1");
        if ($database->user->access("Administrator",FALSE)) {
			$query_where[]=new query_where("subcategory.status","in",array("Active","Pending"));
		  } else {
			$query_where[]=new query_where("subcategory.status","=","Active");
		}
	    if ($this->subcategory_id) {
        	$query_where[]=new query_where("inventory.subcategory_id","=",$this->subcategory_id);
	        foreach ($this->subcategory[$this->subcategory_id]->parameters as $parameter) {
	            switch (TRUE) {
	              case (!in_array($parameter->id,$this->subcategory[$this->subcategory_id]->parameter_search)):
	              case (!strlen($this->parameter[$parameter->id])):
	                break;
	              case ($parameter->numeric):
	                $query_where["parameter{$parameter->id}"]=new query_where("inventory.nparameter{$parameter->id}","=",$this->parameter[$parameter->id]);
	                break;
	              default:
	                $query_where["parameter{$parameter->id}"]=new query_where("inventory.parameter{$parameter->id}","=",$this->parameter[$parameter->id]);
	            }
	        }
		}
        if ($this->new_date) {
        	$query_where['new_date'][]=new query_where("inventory.new_date",">=",$database->inventory->new_date());
        	$query_where['new_date'][]=new query_where("subcategory.new_date",">=",$database->inventory->new_date(),"or");
		}
		$query=
        	"select \n" .
            "inventory.* from inventory \n" .
	        "left join subcategory on subcategory.id=inventory.subcategory_id \n" .
	        "left join category on category.id=subcategory.category_id \n" .
            $database->where($query_where) .
			"order by inventory.sort_order, inventory.sku";
		return $query;
    }
    function portal() {
    global $database, $forms, $menu;
        print "<div class=products_content>";
		$database->portalcontent->portal_detail($this->portal_category);
        print "</div>";
    }
    function legacy_menu() {
	global $database, $forms, $menu;
		if (!$database->portalcontent->portal->header->id) return;
        print "<div class=products_content>";
	    print "<table class='large noborder'>\n";
	    print "<tr>\n";
	    $standard_options=array();
	    $standard_options["onmouseover"]="image_rollover(this,'images/portal_standard2.jpg');";
	    $standard_options["onmouseout"]="image_rollover(this,'images/portal_standard1.jpg');";
	    $standard_options["title"]="Harcourt Standard Products!";
	    $custom_options=array();
	    $custom_options["onmouseover"]="image_rollover(this,'images/portal_custom2.jpg');";
	    $custom_options["onmouseout"]="image_rollover(this,'images/portal_custom1.jpg');";
	    $custom_options["title"]="Custom Designs for " . $_SESSION['user']->company_name;
	    if ($_SESSION['user']->catalog) print "<td width=50% class=center>" . fn_href($forms->img('scs_images/portal_standard1.jpg',$standard_options),$this->php_self) . "</td>\n";
	    if (sizeof($database->portalcontent->portal->item)) print "<td width=50% class=center>" . fn_href($forms->img("scs_images/portal_custom1.jpg",$custom_options),$this->php_self,array("action"=>"legacy_products")) . "</td>\n";
	    print "</tr>\n";
	    print "</table>\n";
        print "</div>";
    }
    function legacy_products() {
	global $database, $forms, $menu;
		print $database->portalcontent->portal_custom();
    }
    function summary() {
	global $database, $forms, $menu;
		print "<div class=products_summary>";
		$results=array();
		if ($_SESSION['user']->portal_id) {
			$results[]=$database->portalcontent->portal_image(array("class"=>"left"));
	        $results[]=$database->portalcontent->portal_headline();
 	        $results[]=$database->portalcontent->portal_summary();
		}
        switch (TRUE) {
          case ($_SESSION['user']->portal_id):
			$results[]="<h1>Standard Products</h1>";
            break;
        }
		print implode("\n",$results);
	    $query_where=array();
	    $query_where[]=new query_where("category.active","=",1);
        if ($database->user->access("Administrator",FALSE)) {
			$query_where[]=new query_where("subcategory.status","in",array("Active","Pending"));
		  } else {
			$query_where[]=new query_where("subcategory.status","=","Active");
		}
        if ($this->new_date) {
	        $query_where[]=new query_where("inventory.active","=",1);
	        $query_where[]=new query_where("inventory.subcategory_id","in",$menu->subcategory);
	        if ($this->new_date) {
	            $query_where['new_date'][]=new query_where("inventory.new_date",">=",$database->inventory->new_date());
	            $query_where['new_date'][]=new query_where("subcategory.new_date",">=",$database->inventory->new_date(),"or");
	        }
	        $query=array("select");
	        $query[]="category.id as 'category.id',";
            $query[]="inventory.subcategory_id as 'subcategory.id'";
            $query[]="from inventory";
            $query[]="left join subcategory on subcategory.id=inventory.subcategory_id";
	    	$query[]="left join category on category.id=subcategory.category_id";
			$query[]=$database->where($query_where);
			$query[]="group by subcategory.id";
			$query[]="order by category.sort_order,category.name,subcategory.sort_order,subcategory.name";
		  } else {
	        $query_where[]=new query_where("subcategory.id","in",$menu->subcategory);
	        $query=array("select");
	        $query[]="category.id as 'category.id',";
            $query[]="subcategory.id as 'subcategory.id'";
            $query[]="from subcategory";
	    	$query[]="left join category on category.id=subcategory.category_id";
			$query[]=$database->where($query_where);
			$query[]="order by category.sort_order,category.name,subcategory.sort_order,subcategory.name";
        }
	    $database->temp->query($query);
        $results=array();
		$summary_list=array();
        while ($database->temp->fetch = $database->temp->fetch_array() ) {
			$subcategory_id=$database->temp->fetch['subcategory.id'];
			$category_id=$this->subcategory[$subcategory_id]->category_id;
			if (!array_key_exists($category_id,$summary_list)) $summary_list[$category_id]=array();
			$summary_list[$category_id][]=$subcategory_id;
        }
		$database->temp->free_result();
        $this->output_summary($summary_list);
		print "</div> <!-- products_summary -->";
    }
    function output_summary($summary_list) {
	global $database, $forms, $menu;
        $url_query=array();
        $url_query['action']="subcategory";
        if (strlen($this->search_code)) $url_query['search_code']=$this->search_code;
		if ($this->category_id) $url_query['category_id']=$this->category_id;
        $url_query['subcategory_id']=0;
        if ($this->new_date) $url_query['new_date']=1;
        $img_query=array();
        $img_query['width']=$database->registry->local->thumbnail;
        $img_query['height']=$database->registry->local->thumbnail;
		foreach ($summary_list as $category_id => $subcategory_list) {
			$results[]="<a class=anchor id=category{$category_id}></a><h2>" . $this->category[$category_id]->name . "</h2>";
        	$results[]="<div class=catalog_subcategory_list>";
            $results[]="<ul>";
            foreach ($subcategory_list as $subcategory_id) {
				$href_query=array();
				$url_query['subcategory_id']=$subcategory_id;
                switch (TRUE) {
				  case ($this->subcategory[$subcategory_id]->new_date >= $database->inventory->new_date() ):
					$watermark="new_subcategory";
                    $href_query['title']="New Item!";
                    break;
				  case (isset($this->subcategory[$subcategory_id]->new_inventory)):
					$watermark="new_inventory";
                    $href_query['title']="New Sizes!";
                    break;
                  default:
					$watermark="";
                }
            	$text=array();
				$text[]=$forms->img($this->subcategory[$subcategory_id]->thumbnail_url,$img_query);
            	$text[]="<br>" . $this->subcategory[$subcategory_id]->name;
                switch (TRUE) {
                  case ($this->subcategory[$subcategory_id]->status == "Pending"):
                    $li_style="style='border: 1px solid #00b0f0;'";
                    break;
                  case $watermark:
					$li_style="style='border: 1px solid #f00;'";
			        break;
                  default:
                  	$li_style="";
                }
				$results[]="<li $li_style>" . fn_href(implode("",$text),$this->php_self,$url_query,$href_query) . "</li>";
            }
            $results[]="</ul>";
			$results[]="</div>";
        }
		$results[]="</div>";
		print implode("\n",$results);
    }
    function sku() {
    global $database, $forms, $menu;
		$query=
			$this->query_subcategory() .
            $database->item_limit($this->item);
		$database->inventory->query($query,TRUE);
		$database->inventory->fetch(TRUE);
		$database->inventory->free_result();
        $this->output_sku();
	}
    function search_sku() {
    global $database, $forms, $menu;
        $this->output_sku();
		$url=fn_url($this->php_self,array("action"=>"subcategory","subcategory_id"=>$this->subcategory_id));
        print "<p class='center'>" . $forms->button($this->category[$this->category_id]->name . ": " . $this->subcategory[$this->subcategory_id]->name,array("onclick"=>"window.location.href=" . fn_escape($url) . ";")) . "</p>\n";
    }
    function subcategory() {
    global $database, $forms, $menu;
		$data=array();
        $query=$this->query_subcategory();
        if ( ($this->subcategory[$this->subcategory_id]->page_break) && (!$_SESSION['user']->bot_code) ) $query .= $database->page_limit($this->page);
		$database->inventory->query($query,TRUE);
        while ($database->inventory->fetch = $database->inventory->fetch_array() ) {
			$database->inventory->fetch();
            $data[$database->inventory->data->sku]=$database->inventory->data;
		}
        $database->inventory->free_result();
        $this->output_subcategory($data);
    }
    function search() {
    global $database, $forms, $menu;
		switch (TRUE) {
		  case (!$this->subcategory_id):
          case (!sizeof($this->subcategory[$this->subcategory_id]->parameter_search)):
			return;
		}
    	$results=array();
        $results[]="<script type='text/javascript'>";
        $results[]="function fn_search_select(obj,search_complete) {";
		$url_query=array();
        $url_query['action']="subcategory";
        $url_query['subcategory_id']=$this->subcategory_id;
        if (strlen($this->search_code)) $url_query['search_code']=$this->search_code;
        if ($this->new_date) $url_query['new_date']=1;
        foreach ($this->parameter as $key => $value) {
			if (strlen($value)) $url_query["parameter{$key}"]=$value;
        }
        $url_query["page"]=1;
        $url_query["search_complete"]=1;
        $text=array();
        foreach ($url_query as $key => $value) {
			$text[]=fn_escape($key) . ": " . fn_escape($value);
        }
        $results[]="var url_query={" . implode(", ",$text) . "};";
        $results[]="if (search_complete=='0') {";
        $results[]="  url_query['search_complete']='0';";
		$results[]="  url_query[obj.id]=obj.value;";
        $results[]="}";
	    $results[]="window.location.href = '" . $this->php_self . "?' + $.param(url_query);";
        $results[]="}";
        $results[]="</script>";
		$query_where=array();
	    $query_where[]=new query_where("category.active","=",1);
	    $query_where[]=new query_where("subcategory.id","=",$this->subcategory_id);
        if ($database->user->access("Administrator",FALSE)) {
			$query_where[]=new query_where("subcategory.status","in",array("Active","Pending"));
		  } else {
			$query_where[]=new query_where("subcategory.status","=","Active");
		}
	    $query_where[]=new query_where("inventory.active","=",1);
        foreach ($this->subcategory[$this->subcategory_id]->parameters as $parameter) {
			switch (TRUE) {
			  case (!in_array($parameter->id,$this->subcategory[$this->subcategory_id]->parameter_search)):
              case (!strlen($this->parameter[$parameter->id])):
				break;
			  case ($parameter->numeric):
				$query_where["parameter{$parameter->id}"]=new query_where("inventory.nparameter{$parameter->id}","=",$this->parameter[$parameter->id]);
                break;
              default:
				$query_where["parameter{$parameter->id}"]=new query_where("inventory.parameter{$parameter->id}","=",$this->parameter[$parameter->id]);
			}
        }
        $parameter_results=array();
        foreach ($this->subcategory[$this->subcategory_id]->parameters as $parameter) {
			if (!in_array($parameter->id,$this->subcategory[$this->subcategory_id]->parameter_search)) continue;
            $parameter_results[$parameter->id]=array();
			$parameter_query_where=$query_where;
            if ($parameter->numeric) {
            	$parametric_query_field="inventory.nparameter{$parameter->id}";
				$parameter_query_where["parameter{$parameter->id}"]=new query_where("inventory.nparameter{$parameter->id}","<>",0);
              } else {
            	$parametric_query_field="inventory.parameter{$parameter->id}";
				$parameter_query_where["parameter{$parameter->id}"]=new query_where("inventory.parameter{$parameter->id}","<>","");
            }
            $query=
				"select \n" .
				$parametric_query_field . " as 'parameter_results'\n" .
                "from inventory \n" .
                "left join subcategory on subcategory.id=inventory.subcategory_id \n" .
                "left join category on category.id=subcategory.category_id \n" .
                $database->where($parameter_query_where) .
                "group by parameter_results \n" .
                "order by parameter_results";
			$database->temp->query($query);
            while ($database->temp->fetch = $database->temp->fetch_array() ) {
				$parameter_results[$parameter->id][]=$parameter->output($database->temp->fetch['parameter_results']);
            }
			$database->temp->free_result();
		}
        $results[]="<p class='standard'>";
        foreach ($this->subcategory[$this->subcategory_id]->parameter_search as $parameter_id) {
			switch (TRUE) {
			  case (!array_key_exists($parameter_id,$this->subcategory[$this->subcategory_id]->parameters)):
              case (sizeof($parameter_results[$parameter_id]) < 2):
              	break;
			  default:
				$parameter=$this->subcategory[$this->subcategory_id]->parameters[$parameter_id];
				$results[]="<span class=nobr>{$parameter->name} : ";
                $results[]=$forms->select("parameter{$parameter_id}",$parameter_results[$parameter_id],$this->parameter[$parameter_id],TRUE,array("onchange"=>"fn_search_select(this,'0');"));
                $results[]="</span>";
			}
		}
		$results[]="</p>";
		return implode("",$results);
    }
    function output_subcategory($data) {
    global $database, $forms, $menu;
		print $this->subcategory_title();
	    print "<div id='cart_dialog'>";
        print "<div id='cart_contents'></div>";
	    print "</div>\n";
		$this->output_overview();
		if (!$this->subcategory[$this->subcategory_id]->prj_landing_page) {
			if ($this->page) $this->tabs->landing_tab();
        	$this->tabs->item($menu->language('catalog_products'),$this->inventory($data),array("id"=>"catalog_products"));
		}
		$prj=$this->subcategory[$this->subcategory_id]->prj;
		if ( ($this->action=="sku") && (array_key_exists($this->sku,$data)) ) {
			$database->inventory->data=$data[$this->sku];
			if ($database->inventory->data->prj) $prj=$database->inventory->data->prj;
            $this->output_sku_tab();
		}
		$this->prj(array($prj));
        $this->output_download();
		$this->tabs_output();
    }
    function output_download() {
    global $database, $forms, $menu;
		$query_where=array();
        $query_where['subcategory']=array();
        $query_where['subcategory'][]=new query_where("content.record_type","=","subcategory");
        $query_where['subcategory'][]=new query_where("content.record_id","=",$this->subcategory_id);
        $query_where['subcategory'][]=new query_where("content.record_item","like","download:%");
        if ($this->sku) {
	        $query_where['inventory']=array();
	        $query_where['inventory'][]=new query_where("content.record_type","=","inventory","or");
	        $query_where['inventory'][]=new query_where("content.record_id","=",$database->inventory->data->id);
	        $query_where['inventory'][]=new query_where("content.record_item","like","download:%");
        }
		$query=
			"select \n" .
            "if (content.record_type = 'subcategory',1,0) as sort_key, \n" .
            "content.* from content \n" .
			$database->where($query_where) .
            "order by sort_key, content.record_item";
		$database->content->query($query);
        if (!$database->content->meta->rows) return;
	    $results=array();
	    $results[]="<table class='standard noborder'>";
	    while ($database->content->fetch = $database->content->fetch_array() ) {
			$database->content->fetch();
            list($content_download,$content_id)=preg_split("/\:/",$database->content->data->record_item);
			switch (TRUE) {
			  case ($database->content->data->record_type != "inventory"):
				break;
			  case (!array_key_exists($this->subcategory_id,$this->subcategory)):
			  case (!array_key_exists($content_id,$this->subcategory[$this->subcategory_id]->download)):
				$database->content->data->name="";
				break;
			  default:
				$database->content->data->name=$this->subcategory[$this->subcategory_id]->download[$content_id];
			}
            if ($database->content->data->name) {
				$local_log=array();
                $local_log[]="download";
                $local_log[]=$database->content->data->meta->name;
	            $local_log[]=$this->sku;
	            $local_log[]=$this->subcategory_id;
	            $results[]="<tr>";
	            $url_options=array();
	            $url_options['content_id']=$database->content->encode($database->content->data->id);
	            $url_options['local_log']=base64_encode(implode("\t",$local_log));
	            $href_options=array();
	            $href_options['target']="_blank";
	            $results[]="<td>" . fn_href($database->content->data->name,"/scs_file.php",$url_options,$href_options) . "</td>";
	            $results[]="</tr>";
			}
	    }
		$database->content->free_result();
		$results[]="</table>";
		$this->tabs->item($menu->language('catalog_download'),$results,array("id"=>"catalog_download"));
    }
    function subcategory_title($sku=FALSE) {
    global $database, $forms, $menu;
		$results=array();
        $text=array();
        $text[]=$this->subcategory[$this->subcategory_id]->name;
        switch (TRUE) {
		  case ($this->subcategory[$this->subcategory_id]->new_date >= $database->inventory->new_date() ):
			$text[]="(New Item!)";
            break;
		  case (isset($this->subcategory[$this->subcategory_id]->new_inventory)):
			$text[]="(New Sizes!)";
            break;
		}
		$results[]="<div id=products_header>";
		$results[]="<h1>" . implode(" ",$text) . "</h1>";
		$results[]="</div>";
        $results[]="<div class=products_content>";
        if ($this->subcategory[$this->subcategory_id]->thumbnail_url)$results[]="<div style='display:none;'><img src='" . $this->subcategory[$this->subcategory_id]->thumbnail_url . "' id=cart_thumbnail></div>";
		$results[]="<div id=products_image>";
        if ($this->subcategory[$this->subcategory_id]->image_url) $results[]="<span>" . $forms->img($this->subcategory[$this->subcategory_id]->image_url,array("align"=>"top","width"=>450)) . "</span>";
        if ($this->subcategory[$this->subcategory_id]->sketch_url) $results[]="<span>" . $forms->img($this->subcategory[$this->subcategory_id]->sketch_url,array("align"=>"top","width"=>450)) . "</span>";
		$results[]="</div>";
        $results[]="</div>";

        return implode("\n",$results);
    }
    function output_sku_tab() {
    global $database, $forms, $menu;
        $menu->configure=new configure_class("");
        $menu->configure->inventory_msrp();
        $results=array();
		$results[]="<table class='noborder large'>";
        $results[]="<tr>";
        $results[]="<td width='50%'>";
        $data=array();
        $data[]="Part number: " . $database->inventory->data->sku;
        $data[]="Category: " . $this->category[$this->category_id]->name;
        $data[]="Subcategory: " . $this->subcategory[$this->subcategory_id]->name;
		switch (TRUE) {
		  case ($database->inventory->data->new_date >= $database->inventory->new_date()):
			$data[]="New Item!";
            break;
		  case ($this->subcategory[$this->subcategory_id]->new_date >= $database->inventory->new_date()):
			$data[]="New Series!";
            break;
        }
        $results[]="<p>" . implode("<br>\n",$data) . "</p>";
        $results[]=$menu->configure->msrp->output_verbose;
        if ($_SESSION['user']->ecommerce) {
        	$text=array();
			$field_sku=fn_base64(array("sku",$database->inventory->data->sku));
			$field_list=fn_base64(array("list",$database->inventory->data->sku));
            $text[]=$forms->text($field_sku,"",6,6,"",array("class"=>"products_sku_quantity"));
            $text[]=$forms->button("Add to Cart",array("class"=>"cart_button","onclick"=>"cart_update(this," . fn_escape($field_sku) . "," . fn_escape($field_list) . ");"));
            $results[]="<p>" . implode(" ",$text) . "</p>";
        }
        $results[]="</td>";
        $results[]="<td width='50%'>";
		$results[]="<b>Specifications:</b>";
        foreach ($this->subcategory[$this->subcategory_id]->parameters as $parameter) {
			$results[]="<br>" . $parameter->name . ": <b>" . $database->inventory->data->parameter[$parameter->id] . "</b>";
        }
		$results[]="</td>";
		$results[]="</tr>";
		$results[]="</table>";
        $this->tabs->item($menu->language('catalog_sku'),$results,array("id"=>"catalog_sku"));
		if ($database->user->access("Internal",FALSE)) $this->xref($database->inventory->data->sku);
    }
    function output_sku() {
    global $database, $forms, $menu;
        $this->subcategory_id=$database->inventory->data->subcategory_id;
		$this->category_id=$this->subcategory[$this->subcategory_id]->category_id;
		print $this->subcategory_title(TRUE);
		$this->output_sku_tab();
		$this->output_overview();
		$this->prj(array($database->inventory->data->prj,$this->subcategory[$this->subcategory_id]->prj));
        $this->output_download();
		$this->tabs_output();
    }
	function prj($prj) {
    global $database, $forms, $menu;
		switch (TRUE) {
		  case (!$_SESSION['user']->id);
		  case (!$_SESSION['user']->cad);
		  case ($_SESSION['user']->status != "Active");
          case ( (!$this->email) && (!isset($_SESSION['user']->punchout)) ):
          case ( (!$prj[0]) && (!$prj[1]) ):
			return;
		  case ($prj[0]):
			$url=$database->inventory->prj($prj[0]);
            break;
		  default:
			$url=$database->inventory->prj($prj[1]);
		}
        if ($this->action == "sku") {
			$this->tabs->landing_tab();
			$database->log->update("PARTSolutions:configurator",array("sku"=>$database->inventory->data->sku,"subcategory_id"=>$database->inventory->data->subcategory_id));
		}
        $items=array();
        $items[]="<table class='standard noborder'>";
        $items[]="<tr>";
        $items[]="<td width='80%'>";
		$items[]=$forms->iframe("",$url,array("id"=>"scsps_configurator","height"=>"660px","onload"=>"pcom_size();"),FALSE);
        $items[]="</td>";
        $items[]="<td width='20%' id='products_cad_html'></td>";
        $items[]="</tr>";
        $items[]="</table>";
		$this->tabs->item($menu->language('catalog_cad'),$items,array("id"=>"catalog_cad"));
    }
    function output_overview() {
    global $database, $forms, $menu;
		$query_where=array();
        $query_where[]=new query_where("record_type","=","subcategory");
        $query_where[]=new query_where("record_id","=",$this->subcategory_id);
        $query_where[]=new query_where("record_item","like","overview:%");
	    $query_where[]=new query_where("language_code","in",array("EN",$_SESSION['user']->language_code));
        $query=array("select");
        $query[]="if (language_code='EN',2,1) as sort_key,";
        $query[]="content.* from content";
		$query[]=$database->where($query_where);
		$query[]="order by sort_key, record_item";
		$database->content->query($query);
        if (!$database->content->meta->rows) return;
        $results=array();
        $items=array();
        while ($database->content->fetch = $database->content->fetch_array() ) {
			$database->content->fetch();
            if (in_array($database->content->data->record_item, $items)) continue;
            $items[]=$database->content->data->record_item;
	        switch ($database->content->data->filetype) {
	          case "image":
        		$results[]="<p class=products_overview>" . $database->content->data->name . "</p>";
	            $results[]="<img src='" . fn_url("/scs_file.php",array("content_id"=>$database->content->encode($database->content->data->id))) . "'>";
	            break;
	          case "html":
            	$results[]="<p class=products_overview>" . $database->content->data->name . "</p>";
            	$results[]="<div>" . $database->content->data->content . "</div>";
				break;
              default:
	            $url_options=array();
	            $url_options['content_id']=$database->content->encode($database->content->data->id);
	            $href_options=array();
	            $href_options['target']="_blank";
            	$results[]="<p class=products_overview>" . fn_href($database->content->data->name,"/scs_file.php",$url_options,$href_options) . "</p>";
			}
            $results[]="<hr>";
        }
        $this->tabs->item("Overview",$results,array("id"=>"catalog_overview"));
    }
    function inventory($data) {
    global $database, $forms, $menu;
		if ( ($this->subcategory[$this->subcategory_id]->output) || (!sizeof($data)) ) return;
		if ( ($this->action=="subcategory") && ($this->search_code) ) $this->tabs->landing_tab();
		$category_id=$this->subcategory[$this->subcategory_id]->category_id;
        $database->subcategory->data=$this->subcategory[$this->subcategory_id];
        $menu->configure=new configure_class("");
		$results=array();
        $results[]=$forms->hidden("cart_quantity");
		$results[]=$this->search();
        if ($this->subcategory[$this->subcategory_id]->description) $results[]="<div class='products_content' style='margin-bottom: 10px;'>" . $this->subcategory[$this->subcategory_id]->description . "</div>";
        $results[]="<table class='products_table'>";
        $results[]="<thead>";
        $results[]="<tr>";
        $results[]="<th>" . ( (array_key_exists("sku",$this->subcategory[$this->subcategory_id]->options)) ? $this->subcategory[$this->subcategory_id]->options['sku'] : "Part#" ) . "</th>";
        foreach ($this->subcategory[$this->subcategory_id]->parameters as $parameter) {
            $results[]="<th>" . wordwrap($parameter->name,12,"<br>") . "</th>";
        }
        if ($_SESSION['user']->price_type) $results[]="<th>" . ( (array_key_exists("price",$this->subcategory[$this->subcategory_id]->options)) ? $this->subcategory[$this->subcategory_id]->options['price'] : "Price" ) . "</th>";
        if ($_SESSION['user']->ecommerce) {
        	$results[]="<th class=nobr>Typical<br>Lead Time</th>";
        	$results[]="<th>Quantity</th>";
        	$results[]="<th class=nobr>Add to<br>Cart</th>";
        }
        $results[]="</tr>";
        $results[]="</thead>";
        $url_query=array();
        $url_query['action']="sku";
        $url_query['subcategory_id']=$this->subcategory_id;
        if (strlen($this->search_code)) $url_query['search_code']=$this->search_code;
        if ($this->new_date) $url_query['new_date']=1;
        if (sizeof($this->subcategory[$this->subcategory_id]->parameter_search)) {
            $url_query["search_complete"]=$this->search_complete;
            foreach ($this->parameter as $key => $value) {
                if (strlen($value)) $url_query["parameter{$key}"]=$value;
            }
        }
        $url_query['search_complete']=$this->search_complete;
        $url_query['sku']="";
        $url_query['scs_page']=$this->page;
        $results[]="<tbody>";
        foreach ($data as  $database->inventory->data) {
        	$results[]="<tr>";
            $url_query['sku']=$database->inventory->data->sku;
            $text=array();
            switch (TRUE) {
              case ($this->subcategory[$this->subcategory_id]->new_date >= $database->inventory->new_date() ):
              case ($database->inventory->data->new_date >= $database->inventory->new_date() ):
                $text[]=$forms->font("red","*");
            }
            $text[]=fn_href($database->inventory->data->sku,$this->php_self,$url_query);
            $results[]="<td class='nobr'>" . implode("",$text) . "</td>";
            foreach ($this->subcategory[$this->subcategory_id]->parameters as $parameter) {
                $results[]="<td>" . $database->inventory->data->parameter[$parameter->id] . "</td>";
            }
            if ($_SESSION['user']->price_type) {
            	$menu->configure->inventory_msrp();
                $results[]="<td class='right nobr'>" . $menu->configure->msrp->output_simple . "</td>";
            }
	        if ($_SESSION['user']->ecommerce) {
	            $results[]="<td class=center>" . $database->inventory->leadtime() . "</td>";
	            $field_sku=fn_base64(array("sku",$database->inventory->data->sku));
	            $field_list=fn_base64(array("list",$database->inventory->data->sku));
	            $results[]="<td class=center>" . $forms->text($field_list,"",6,6,"",array("class"=>"products_quantity")) . "</td>";
	            $results[]="<td class=center>" . fn_href($forms->img("/scs_images/addtocart.png",array("id"=>$field_icon,"class"=>"products_cart","title"=>"Add to Cart")),"javascript:cart_update(this," . fn_escape($field_list) . "," . fn_escape($field_sku) . ")") . "</td>";
	        }
            $results[]="</tr>";
        }
        $results[]="</tbody>";
        $results[]="</table>";
		switch (TRUE) {
          case (!$this->subcategory[$this->subcategory_id]->page_break):
          case ($_SESSION['user']->bot_code):
          case ($database->inventory->meta->found_rows <= $database->query_limit):
        	break;
          default:
            $url_query['action']="subcategory";
            $url_query["scs_page"]="%page%";
            unset($url_query['sku']);
            $url=fn_url($this->php_self,$url_query);
            $page_break=new page_item_break($database->inventory->meta->found_rows,"window.location.href=" . fn_escape($url) . ";");
            $results[]=$page_break->page($this->page);
        }
		return $results;
    }
    function xref($sku) {
    global $database, $forms, $menu;
		$query=
        	"select * from inventory_xref \n" .
            "where sku=" . fn_escape($sku) . "\n" .
            "order by competitor";
		$database->inventory_xref->query($query);
        if (!$database->inventory_xref->meta->rows) return;
        $items=array();
        while ($database->inventory_xref->fetch = $database->inventory_xref->fetch_array() ) {
			$database->inventory_xref->fetch();
	        $items[]=$database->inventory_xref->data->competitor_sku;
        }
		$database->inventory_xref->free_result();
        $results=array();
        $results[]="<p class='standard catalog'>";
        $results[]="&bull; " . implode("<br>\n&bull; ",$items);
        $results[]="</p>";
		$this->tabs->item($menu->language('catalog_competitors'),$results,array("id"=>"catalog_competitors"));
    }
    function tabs_output() {
    global $database, $forms, $menu;
		$this->tabs->landing_tab("Product Range",TRUE);
		$this->tabs->output();
	    $js=array();
	    $js[]="<script type='text/javascript'>";
	    $js[]="$(document).ready(function() {";
	    $js[]="$('.products_cart').on('mouseover', function(){";
	    $js[]="$(this).attr('src', '/scs_images/addtocart-hover.png');";
	    $js[]="});";
	    $js[]="$('.products_cart').on('mouseout', function(){";
	    $js[]="$(this).attr('src', '/scs_images/addtocart.png');";
	    $js[]="});";
	    $js[]="});";
	    $js[]="</script>";
        print implode("\n",$js) . "\n";
    }
}
?>