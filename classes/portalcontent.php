<?php
$database->portalcontent=new portalcontent_class();
class portalcontent_class extends database_class {
	function scs_table_version() {
		$results=array();
        $results['11/13/2025']="Revisions";
        $results['10/09/2025']="Add form:URL";
        $results['03/29/2025']="Bug fix";
        $results['10/12/2024']="Improve output";
        $results['07/25/2017']="Log portal pages";
        $results['08/04/2016']="Add portalcategory subcategory";
        $results['07/22/2016']="Add portalitem subcategory";
        $results['03/24/2016']="Enhance output";
        $results['08/13/2014']="Add language support";
        $results['10/31/2012']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
	function __construct() {
    	$this->data=new portalcontent_data_class();
        $this->constant=new portalcontent_constant_class();
        $this->fetch=array();
    	$this->meta=new database_meta_class();
    }
    function fetch($fetch=FALSE) {
        if ($fetch) $this->fetch=$this->fetch_array();
    	$this->data=new portalcontent_data_class();
	    $this->data->id=$this->fetch['id'];
	    $this->data->portal_id=$this->fetch['portal_id'];
	    $this->data->name=$this->fetch['name'];
	    $this->data->form=$this->fetch['form'];
        $this->data->active=$this->fetch['active'];
    }
    function read($id,$field="id") {
        $query=array("select * from portalcontent");
        $query[]="where {$field}=" . fn_escape($id);
        $this->query($query);
        if ($this->meta->rows) {
            $this->fetch(TRUE);
          } else {
            $this->data=new portalcontent_data_class();
        }
    }
    function update($update=FALSE) {
		$fields=array();
        $fields[]="id=" . fn_escape($this->data->id,FALSE);
        $fields[]="portal_id=" . fn_escape($this->data->portal_id,FALSE);
        $fields[]="name=" . fn_escape($this->data->name);
        $fields[]="form=" . fn_escape($this->data->form);
        $fields[]="active=" . fn_escape($this->data->active,FALSE);
        $query=array();
    	if ($update) {
          	$query[]="update portalcontent set";
            $query[]=implode(",\n",$fields);
            $query[]="where id=" . fn_escape($this->data->id);
		  } else {
        	$query[]="insert into portalcontent set";
            $query[]=implode(",\n",$fields);
        }
		$this->query($query);
        if ( (!$this->data->id) && (!$this->meta->error) ) $this->data->id=$this->insert_id();
    }
    function delete($id) {
	    $query=array("delete from portalcontent where");
        $query[]="id=" . fn_escape($id);
		$this->query($query);
    }
    function select($compare,$select_options=array(),$forms_options=array()) {
        global $forms;
        $field=(array_key_exists("field",$select_options)) ? $select_options['field'] : "portalcontent_id";
        $blank=(array_key_exists("blank",$select_options)) ? $select_options['blank'] : $blank=TRUE;
        $query_where=array();
        if (array_key_exists("form",$select_options)) $query_where[]=new query_where("form","=",$select_options['form']);
        if (array_key_exists("portal_id",$select_options)) $query_where[]=new query_where("portal_id","in",array(0,$select_options['portal_id']));
        $query=array("select * from portalcontent");
        $query[]=$this->where($query_where);
        $query[]="order by form,name";
		$this->query($query);
		$results=array();
        while ($this->fetch=$this->fetch_array()) {
        	$this->fetch();
            if (!array_key_exists($this->data->form,$results)) $results[$this->data->form]=array();
			$results[$this->data->form][$this->data->id]=$this->data->name;
            if (!$this->data->portal_id) $results[$this->data->form][$this->data->id] .= " (Generic)";
        }
        $this->free_result();
        if (array_key_exists("form",$select_options)) {
			return $forms->select($field,$results[$select_options['form']],$compare,$blank,$forms_options);
		  } else {
			return $forms->optgroup($field,$results,$compare,TRUE,$options);
		}
    }





	function portal_menu() {
	    global $forms;
        $this->trace[]=__FUNCTION__;
		if (!$this->portal->header->id) return;
	    print "<table class='large noborder'>\n";
	    print "<tr>\n";
	    $standard_options=array();
	    $standard_options["onmouseover"]="image_rollover(this,'images/portal_standard2.jpg');";
	    $standard_options["onmouseout"]="image_rollover(this,'images/portal_standard1.jpg');";
	    $standard_options["title"]="Harcourt Standard Products!";
	    $custom_options=array();
	    $custom_options["onmouseover"]="image_rollover(this,'images/portal_custom2.jpg');";
	    $custom_options["onmouseout"]="image_rollover(this,'images/portal_custom1.jpg');";
	    $custom_options["title"]="Custom Designs for {$_SESSION['user']->company_name}";
	    if ($_SESSION['user']->catalog) print "<td width=50% class=center>" . fn_href($forms->img('images/portal_standard1.jpg',$standard_options),"/catalog.php",array("portal"=>"standard")) . "</td>\n";
	    if (sizeof($this->portal->item)) print "<td width=50% class=center>" . fn_href($forms->img("images/portal_custom1.jpg",$custom_options),"/catalog.php",array("portal"=>"custom")) . "</td>\n";
	    print "</tr>\n";
	    print "</table>\n";
    }
    private function portal_content($id,$text="",$options=array()) {
        global $database, $forms;
        $this->trace[]=__FUNCTION__;
		if (!array_key_exists($id,$this->portal->content)) return "Content Missing: $id";
    	$url_options=array();
		$url_options['content_id']=$database->content->encode($this->portal->content[$id]->id);
		switch ($this->portal->content[$id]->filetype) {
		  case "":
			return;
          case "hyperlink":
            return fn_href($this->portal->content[$id]->name, $this->portal->content[$id]->html_content,array(),array("target"=>"_blank"));
		  case "html":
			return $forms->iframe($this->portal->content[$id]->filetype . "_" . $id,fn_url("/scs_file.php",$url_options));
		  case "image":
			if (!array_key_exists("title",$options)) $options['title']=$this->portal->content[$id]->name;
			$url_options['resize']=TRUE;
			return $forms->img("",$options,$url_options);
		  default:
			return fn_href($text,"/scs_file.php",$url_options,array("target"=>"_blank"));
		}
    }
	function portal_custom() {
	    global $database, $forms;
        $this->trace[]=__FUNCTION__;
        $results=array();
        foreach ($this->portal->output as $category_id => $category_items) {
			if (!array_key_exists($category_id,$this->portal->category)) continue;
			$database->portalcategory->data=$this->portal->category[$category_id];
			$results[]="<div class='portalcategory' id='portal_" . $category_id . "_div'>";
			if ($database->portalcategory->data->headline_portalcontent_id) $results[]="<div>" . $this->portal_content($database->portalcategory->data->headline_portalcontent_id) . "</div>";
			if ($database->portalcategory->data->headline) $results[]="<div>" . $database->portalcategory->data->headline . "</div>";
			$data=array();
			if ($database->portalcategory->data->image_portalcontent_id) $data[]=$this->portal_content($database->portalcategory->data->image_portalcontent_id);
            foreach ($category_items as $item) {
				if (!array_key_exists($item,$this->portal->item)) continue;
				$database->portalitem->data=$this->portal->item[$item];
	            $links=array();
	            if ($database->portalitem->data->pdf_portalcontent_id) $links[]=$this->portal_content($database->portalitem->data->pdf_portalcontent_id,"PDF");
	            if ($database->portalitem->data->cad_portalcontent_id) $links[]=$this->portal_content($database->portalitem->data->cad_portalcontent_id,"CAD");
	            if ($database->portalitem->data->hyperlink_portalcontent_id) $links[]=$this->portal_content($database->portalitem->data->hyperlink_portalcontent_id);
	            if ($database->portalitem->data->other_portalcontent_id) $links[]=$this->portal_content($database->portalitem->data->other_portalcontent_id,$database->portalitem->data->other_name);
  	            $data[]=$database->portalitem->data->name . ": " . implode(" | ",$links);
			}
            $results[]="<div>" . implode("\n",$data) . "</div>";

            $results[]="</div>";
		}
		return implode("\n",$results);
	}



    function portal_summary($url) {
	    global $database, $forms, $menu;
        $this->trace[]=__FUNCTION__;
        if (!sizeof($this->portal->category_list)) return;
        $results=array();
        $results[]="<div><h1>Custom Products</h1></div>";
        $results[]="<div class=catalog_subcategory_list>";
        $results[]="<ul>";
        foreach ($this->portal->output as $category_id => $category_items) {
			$database->portalcategory->data=$this->portal->category[$category_id];
        	$text=array();
	        $url_query=array();
            switch (TRUE) {
			  case (!array_key_exists($category_id,$this->portal->category)):
              case ( ($database->portalcategory->data->subcategory_id) && (!$this->portal->inventory[$category_id]->thumbnail_url) ):
              case ( (!$database->portalcategory->data->subcategory_id) && (!$database->portalcategory->data->image_portalcontent_id) ):
              	break;
              case ($database->portalcategory->data->subcategory_id):
	            $url_query['action']="subcategory";
	            $url_query['category_id']=$this->portal->inventory[$category_id]->category_id;
	            $url_query['subcategory_id']=$this->portal->inventory[$category_id]->id;

	            $text[]=$forms->img($this->portal->inventory[$category_id]->thumbnail_url);
	            $text[]="<br>" . $database->portalcategory->data->name;
                break;
              default:
	            $url_query['action']="portal";
	            $url_query['portal_item']="";
	            $url_query['portal_category']=$category_id;
	            $text=array();
	            $text[]=$this->portal_content($database->portalcategory->data->image_portalcontent_id);
	            $text[]="<br>" . $database->portalcategory->data->name;
                break;
            }
            if (sizeof($text)) $results[]="<li>" . fn_href(implode("",$text),$url,$url_query) . "</li>";
        }
        $results[]="</ul>";
        $results[]="</div>";
		return implode("\n",$results);
	}
    function portal_detail($category_id) {
	    global $database, $forms, $menu;
        $this->trace[]=__FUNCTION__;
        $database->portalcategory->data=$this->portal->category[$category_id];
		$database->log->update("page:portal",array("subcategory_id"=>$category_id,"comment"=>"Portal: " . $database->portalcategory->data->name));
        $results=array();
		$results[]=$this->portal_image();
		$results[]=$this->portal_headline();

	    $results[]="<div>";
		if ($database->portalcategory->data->image_portalcontent_id) $results[]="<span class=portal_detail_headline>" . $this->portal_content($database->portalcategory->data->image_portalcontent_id) . "</span>";
 	    $results[]="<span class=portal_detail_headline>" . $database->portalcategory->data->name . "</span>";
	    $results[]="</div>";

        $results[]="<div class='portal_detail'>";
        foreach ($this->portal->output[$category_id] as $item) {
	        $database->portalitem->data=$this->portal->item[$item];
            if ($database->portalitem->data->subcategory_id) {
            	$database->subcategory->read($database->portalitem->data->subcategory_id);
                $status=array("Active");
				if ($database->user->access("Administrator",FALSE)) $status[]="Pending";
	            $img_query=array();
	            $img_query['width']=$database->registry->local->thumbnail;
	            $img_query['height']=$database->registry->local->thumbnail;
                $url_query=array();
                $url_query['action']="subcategory";
                $url_query['category_id']=$database->subcategory->data->category_id;
                $url_query['subcategory_id']=$database->subcategory->data->id;
				$results[]="<div class='portal_detail'>" . $forms->img($database->subcategory->data->thumbnail_url,$img_query) . "  <span style='vertical-align:30px;'>" . fn_href($database->portalitem->data->name,$menu->page->products->url,$url_query) . "</span></div>";
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
		//print implode("\n",$results);
    }
}
class portalcontent_data_class {
	var $id=0;
    var $portal_id=0;
	var $name;
    var $form="Link";
    var $active=1;
}
class portalcontent_constant_class {
    var $filetype=array("Image","HTML","PDF","Other");
    var $form=array('Portal Image','Portal HTML','Category Image','Category HTML','Link','Hyperlink');
}
/*
CREATE TABLE `portalcontent` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `portal_id` int(11) NOT NULL DEFAULT '0',
  `name` varchar(60) NOT NULL DEFAULT '',
  `form` varchar(45) NOT NULL DEFAULT '',
  `active` int(1) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_key` (`portal_id`,`form`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Customer portal content (08/13/2014)'
*/
?>