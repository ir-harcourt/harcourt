<?php
$database->subcategory = new subcategory_class();
class subcategory_class extends database_class {
	function scs_table_version() {
		$results=array();
        $results['12/09/2024']="Export PRJ, hotfix";
        $results['07/25/2024']="Add hotfix";
        $results['12/18/2023']="Add price_increase";
        $results['06/05/2023']="Add currency";
        $results['10/06/2020']="Add images";
        $results['04/12/2020']="Add weeks";
        $results['02/18/2020']="Add leadtime";
        $results['08/13/2019']="Add output";
        $results['04/20/2019']="Add options";
        $results['03/08/2019']="Add prj_new";
        $results['07/08/2016']="Change field active to status";
        $results['03/11/2016']="Add industry, portal";
        $results['01/28/2016']="sort_order INT(6)";
        $results['09/23/2015']="Add page_break";
        $results['06/03/2015']="Add download";
        $results['04/06/2015']="Add prj_landing_page";
        $results['03/19/2015']=array("Expand parameters","Add Search","Add search_words");
        $results['02/09/2015']="Add new_date";
        $results['09/02/2014']="Incorporate language";
        $results['06/26/2011']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
	function __construct() {
    	$this->data=new subcategory_data_class();
        $this->fetch=array();
    	$this->meta=new database_meta_class();
        $this->constant=new subcategory_constant_class();
    }
    function fetch($fetch=FALSE) {
        if ($fetch) $this->fetch=$this->fetch_array();
	    $this->data=new subcategory_data_class();
	    $this->data->id=$this->fetch['id'];
	    $this->data->category_id=$this->fetch['category_id'];
	    $this->data->name=$this->fetch['name'];
	    $this->data->prj=$this->fetch['prj'];
	    $this->data->prj_landing_page=$this->fetch['prj_landing_page'];
	    $this->data->image_url=$this->fetch['image_url'];
	    $this->data->thumbnail_url=$this->fetch['thumbnail_url'];
	    $this->data->sketch_url=$this->fetch['sketch_url'];
	    $this->data->parameters=unserialize($this->fetch['parameters']);
        if ($this->fetch['parameter_search']) $this->data->parameter_search=explode("^",$this->fetch['parameter_search']);
	    $this->data->documents=unserialize($this->fetch['documents']);
	    $this->data->sort_order=$this->fetch['sort_order'];
        if ($this->fetch['search_words']) $this->data->search_words=explode("^",$this->fetch['search_words']);
	    if ($this->fetch['download']) $this->data->download=unserialize($this->fetch['download']);
	    $this->data->new_date=fn_date($this->fetch['new_date'],"mysql");
	    if ($this->fetch['options']) $this->data->options=unserialize($this->fetch['options']);
	    $this->data->output=$this->fetch['output'];
	    $this->data->status=$this->fetch['status'];
	    $this->data->page_break=$this->fetch['page_break'];
        if ($this->fetch['industry']) $this->data->industry=explode("^",substr($this->fetch['industry'],1,-1));
        if ($this->fetch['portal']) $this->data->portal=explode("^",substr($this->fetch['portal'],1,-1));
	    $this->data->leadtime_option=$this->fetch['leadtime_option'];
	    $this->data->leadtime_text=$this->fetch['leadtime_text'];
        foreach ($this->constant->images as $type) {
	        if ($this->data->$type) $this->data->images[$type]=$_SERVER['DOCUMENT_ROOT'] . $this->data->$type;
        }
        if (strlen($this->fetch['currency'])) $this->data->currency=json_decode($this->fetch['currency'],TRUE);
	    $this->data->price_increase=intval($this->fetch['price_increase']);
	    $this->data->lookup=intval($this->fetch['lookup']);
    }
    function read($id,$field="id") {
		$query="select * from subcategory where $field=" . fn_escape($id);
        $this->query($query);
        if ($this->meta->rows) {
        	$this->fetch(TRUE);
			$this->free_result();
		  } else {
			$this->data=new subcategory_data_class();
        }
    }
    function update($update=FALSE) {
		$fields=array();
	    $fields[]="id=" . fn_escape($this->data->id,FALSE);
	    $fields[]="category_id=" . fn_escape($this->data->category_id,FALSE);
	    $fields[]="name=" . fn_escape($this->data->name);
	    $fields[]="prj=" . fn_escape($this->data->prj, "null");
	    $fields[]="prj_landing_page=" . fn_escape($this->data->prj_landing_page,FALSE);
	    $fields[]="image_url=" . fn_escape($this->data->image_url);
	    $fields[]="thumbnail_url=" . fn_escape($this->data->thumbnail_url);
	    $fields[]="sketch_url=" . fn_escape($this->data->sketch_url);
	    $fields[]="parameters=" . fn_escape(serialize($this->data->parameters));
	    $fields[]="parameter_search=" . fn_escape(implode("^",$this->data->parameter_search));
	    $fields[]="documents=" . fn_escape(serialize($this->data->documents));
	    $fields[]="download=" . fn_escape(serialize($this->data->download));
	    $fields[]="sort_order=" . fn_escape($this->data->sort_order,FALSE);
	    $fields[]="search_words=" . fn_escape(implode("^",$this->data->search_words));
	    $fields[]="new_date=" . fn_escape($this->data->new_date,"date");
	    $fields[]="page_break=" . fn_escape($this->data->page_break,FALSE);
	    $fields[]="options=" . fn_escape(serialize($this->data->options));
	    $fields[]="status=" . fn_escape($this->data->status);
	    $fields[]="output=" . fn_escape($this->data->output);
	    $fields[]="leadtime_option=" . fn_escape($this->data->leadtime_option);
	    $fields[]="leadtime_text=" . fn_escape($this->data->leadtime_text);
	    $fields[]="industry=" . fn_escape("^" . implode("^",$this->data->industry) . "^");
	    $fields[]="portal=" . fn_escape("^" . implode("^",$this->data->portal) . "^");
        $fields[]="currency=" . fn_escape(json_encode($this->data->currency));
	    $fields[]="price_increase=" . fn_escape($this->data->price_increase,FALSE);
        $fields[]="lookup=" . fn_escape($this->data->lookup,FALSE);
        $query=array();
    	if ($update) {
          	$query[]="update subcategory set";
          	$query[]=implode(",\n",$fields);
			$query[]="where id=" . fn_escape($this->data->id);
		  } else {
        	$query[]="insert into subcategory set";
        	$query[]=implode(",",$fields);
        }
		$this->query($query);
		if ((!$this->data->id) && (!$this->meta->error)) $this->data->id=$this->insert_id();
    }
    function delete($id) {
	    $query=array("delete from subcategory where");
        $query[]="id=" . fn_escape($id);
		$this->query($query);
    }
    function access_update($field,$id,$data) {
		$this->access_delete($field,$id);
	    $query=array();
	    $query_where=array();
	    $query_where[]=new query_where("id","in",$data);
	    $query[]="update subcategory";
	    $query[]="set {$field}=";
	    $query[]="case";
	    $query[]="when length({$field}) > 2 then concat({$field},'{$id}^')";
	    $query[]="else '^{$id}^'";
	    $query[]="end";
	    $query[]=$this->where($query_where);
	    $this->query($query);
    }
    function access_delete($field,$id) {
	    $query=array();
	    $query[]="update subcategory";
	    $query[]="set {$field}=replace({$field},'^{$id}^','^')";
	    $query[]="where {$field} like '%^{$id}^%'";
	    $this->query($query);
    }
    function select($compare,$select_options=array(),$forms_options=array()) {
    global $forms;
    	switch (TRUE) {
          case (array_key_exists("name",$forms_options)):
          	$forms->options['name']=$forms_options['name'];
            break;
           case ($select_options['combined']):
           	$forms->options['name']="category_subcategory";
            break;
           default:
           	$forms->options['name']="subcategory_id";
		}
        if (!array_key_exists("output",$forms_options)) $forms->options['output']="";
		if (!array_key_exists("blank",$forms_options)) $forms->options['blank']=TRUE;
 		$query_where=array();
        if ($select_options['category_id']) $query_where[]=new query_where("category.id","=",$select_options['category_id']);
        if ($select_options['output']) $query_where[]=new query_where("subcategory.output","=",$select_options['output']);
	    $query=array("select");
        $query[]="category.name as 'category_name',";
        $query[]="subcategory.* from subcategory";
        $query[]="left join category on category.id=subcategory.category_id";
        $query[]=trim($this->where($query_where));
        $query[]="order by category.name,subcategory.name";
	    $this->query($query);
        $results=array();
	    while ($this->fetch = $this->fetch_array()) {
	        $this->fetch();
            if (!array_key_exists($this->fetch['category_name'],$results)) {
            	$results[$this->fetch['category_name']]=array();
            	if ($select_options['combined']) $results[$this->fetch['category_name']][$this->data->category_id]=$this->fetch['category_name'] . " (" . $select_options['combined'] . ")";
			}
            $text=array($this->data->name);
            if ($this->data->status != "Active") $text[]="(" . $this->data->status . ")";
            $key=array();
            if ($select_options['combined']) $key[]=$this->data->category_id;
            $key[]=$this->data->id;
            $results[$this->fetch['category_name']][implode(":",$key)]=implode(" ",$text);
	    }
	    $this->free_result();
        return $forms->optgroup($forms->options['name'],$results,$compare,$forms->options['blank'],$forms_options);
    }
    function thumbnail() {
    global $database;
		if ( (!$this->data->images['image_url']) || (!file_exists($this->data->images['image_url'])) ) return;
		if ( ($this->data->images['thumbnail_url']) && (file_exists($this->data->images['thumbnail_url'])) ) unlink($this->data->images['thumbnail_url']);
	    $image=array();
	    $image['folder']="/images_subcategory/";
	    $image['type']="thumbnail";
	    if ($this->data->id) {
	        $image['suffix']=$this->data->id;
	      } else {
	        $image['suffix']="_upload";
	    }
	    $image['period']=".";
		$image['filetype']=end(preg_split("/\./",strtolower($this->data->images['image_url'])));
        $this->data->thumbnail_url=implode("",$image);
        $this->data->images['thumbnail_url']=$_SERVER['DOCUMENT_ROOT'] . $database->subcategory->data->thumbnail_url;
		$imagesize=getimagesize($this->data->images['image_url']);
		$image_thumbnail=imagecreatetruecolor($database->registry->local->thumbnail, $database->registry->local->thumbnail);
        if ($imagesize['2']==3) {
            imagealphablending($image_thumbnail, FALSE);
            imagesavealpha($image_thumbnail,TRUE);
			imagecolorallocatealpha($image_thumbnail,255,255,255,127);
        }
        switch ($imagesize['2']) {
          case 1:
            $image_source=imagecreatefromgif($this->data->images['image_url']);
            break;
          case 2:
            $image_source=imagecreatefromjpeg($this->data->images['image_url']);
            break;
          case 3:
            $image_source=imagecreatefrompng($this->data->images['image_url']);
            break;
        }
        if ($imagesize['0'] < $imagesize['1']) {
            $width_height=$imagesize['0'];
          } else {
            $width_height=$imagesize['1'];
        }
        imagecopyresampled($image_thumbnail, $image_source, 0, 0, 0, 0, $database->registry->local->thumbnail, $database->registry->local->thumbnail, $width_height, $width_height);
        switch ($imagesize['2']) {
          case 1:
            imagegif($image_thumbnail,$this->data->images['thumbnail_url']);
            break;
          case 2:
            imagejpeg($image_thumbnail,$this->data->images['thumbnail_url']);
            break;
          case 3:
            imagepng($image_thumbnail,$this->data->images['thumbnail_url']);
            break;
        }
        imagedestroy($image_thumbnail);
    }
    function leadtime() {
    	switch (TRUE) {
          case ($this->data->leadtime_option != "Weeks"):
          	return $this->data->leadtime_option;
          case (strlen($this->data->leadtime_text)):
          	return $this->data->leadtime_text . " Week(s)";
		}
    }
    function map($options) {
    global $database;
        $this->map=new table_map_class($options);
	    $this->map->item("id",array("name"=>"Subcategory ID","type"=>"id","required"=>TRUE));
	    $this->map->item("category_name",array("name"=>"Category Name","width"=>60,"import"=>FALSE));
	    $this->map->item("name",array("name"=>"Subcategory Name","width"=>80,"import"=>FALSE));
	    $this->map->item("leadtime_option",array("name"=>"Leadtime Option","width"=>20,"required"=>TRUE));
	    $this->map->item("leadtime_text",array("name"=>"Leadtime Weeks","width"=>20,"required"=>TRUE));
        $this->map->item("status",array("name"=>"Status","required"=>FALSE));
        $this->map->item("prj",array("name"=>"PRJ","width"=>60,"required"=>FALSE));

        $this->map->subcategory=array();
        $this->query($this->export_query());
		while ($this->fetch = $this->fetch_array() ) {
        	$this->fetch();
            $this->map->subcategory[$this->data->id]=$this->data->name;
        }
	}
    function export_query() {
		$query_where=array();
        $query_where[]=new query_where("category.id","<>",0);
        if ($this->map->options['category_id']) $query_where[]=new query_where("subcategory.category_id","=",$this->map->options['category_id']);
        $query=array("select");
        $query[]="category.name as 'category_name',";
        $query[]="subcategory.* from subcategory";
        $query[]="left join category on category.id = subcategory.category_id";
        $query[]=$this->where($query_where);
        $query[]="order by category.name, subcategory.name";
		return $query;
    }
    function export_data() {
		$this->data->category_name=$this->fetch['category_name'];
    }
    function import_update($data) {
    global $database;
		$this->read($data['id']);
        switch (TRUE) {
          case (!$this->meta->rows):
          case (!array_key_exists($data['id'],$this->map->subcategory)):
          	$this->map->error['id']="Not found: " . $data['id'];
            return;
		}
        $this->data->leadtime_option=import_match($data['leadtime_option'],$this->constant->leadtime_option,TRUE);
        $this->data->leadtime_text=$data['leadtime_text'];
        if (array_key_exists("prj", $data)) {
            $this->data->prj=$data['prj'];
            if (!prj_verify($data['prj'])) $this->map->error['prj']="Malformed PRJ {$data['prj']}";
        }
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
		if ( ($this->map->update) && (!sizeof($this->map->error)) ) $this->update(TRUE);
    }
    function images_unlink($type) {
		if ( ($this->data->images[$type]) && (file_exists($this->data->images[$type])) ) unlink($this->data->images[$type]);
	}
    function image_rename() {
        foreach ($this->constant->images as $type) {
			if (!$this->data->images[$type]) continue;
            $image_url=$this->data->images[$type];
            $this->data->images[$type]=str_replace("_upload",$this->data->id,$this->data->images[$type]);
            $this->data->$type=str_replace("_upload",$this->data->id,$this->data->$type);
            rename($image_url,$this->data->images[$type]);
        }
    }
    function scs_hotfix($execute=FALSE) {
        $this->hotfix=new mysql_hotfix_class("subcategory", "Product subcategory");
        $this->hotfix->version("12/09/2024", "PRJ Unique");
        $this->hotfix->version("07/25/2024", "Add lookup");
        $this->hotfix->version("05/13/2024", "Initial release");
        $this->hotfix->process($execute);
    }
    function scs_hotfix_20241209($meta) {
        global $database;
        $this->hotfix->trace[]=__FUNCTION__;
        $query=array("update subcategory set prj=NULL");
        $query[]="where status='inactive'";
        $this->query($query);
        $query=array("ALTER TABLE `subcategory`");
        $query[]="CHANGE COLUMN `prj` `prj` VARCHAR(1024) NULL DEFAULT NULL ,";
        $query[]="ADD UNIQUE INDEX `prj_UNIQUE` (`prj` ASC);";
        $this->query($query);
        $meta->count=$this->affected_rows();
    }
    function scs_hotfix_20240725($meta) {
        global $database;
        $this->hotfix->trace[]=__FUNCTION__;
        $query=array("ALTER TABLE `subcategory`");
        $query[]="ADD COLUMN `lookup` INT(1) NULL DEFAULT '1' AFTER `custom`;";
        $this->query($query);
        $meta->count=$this->affected_rows();
    }
}
class subcategory_data_class {
	var $id=0;
	var $category_id=0;
	var $name;
	var $prj;
    var $prj_landing_page;
	var $image_url;
	var $thumbnail_url;
	var $sketch_url;
	var $parameters=array();
	var $parameter_search=array();
	var $documents=array();
	var $search_words=array();
	var $download=array();
	var $sort_order=0;
	var $new_date=0;
	var $page_break=0;
    var $industry=array();
    var $portal=array();
    var $options=array();
    var $output;
	var $status="Active";
    var $leadtime_option="Call";
    var $leadtime_text;
    var $images=array();
    var $currency=array();
    var $price_increase=0;
    var $custom=0;
    var $lookup=1;
}
class subcategory_constant_class {
	var $parameters=20;
	var $parameter_type=array(
    	"Alphanumeric","Uppercase","Lowercase","Numeric","Decimal:0","Decimal:1","Decimal:2","Decimal:3","Decimal:4","Decimal:5","Decimal:6","Decimal:7","Decimal:8","Decimal:9");
    var $download_count=4;
    var $overview=9;
    var $options=array("sku"=>"Part#","price"=>"Price");
    var $status=array("Active","Pending","Inactive");
    var $output=array(""=>"Products & CAD","configurator"=>"Only CAD (Static SKU)","smart"=>"Only CAD (SMART SKU)");
    var $leadtime_option=array("In Stock","Weeks","Call");
    var $images=array("image_url","thumbnail_url","sketch_url");
    var $tabs=array("specials"=>"Specials", "customs"=>"Customs");
}
class subcategory_parameter_class {
	var $id;
	var $name;
    var $type;
    var $numeric=FALSE;
    var $decimal=0;
    function __construct($id,$name="",$type="Alphanumeric") {
		$this->id=$id;
		$this->name=trim($name);
        $this->type=$type;
        switch (TRUE) {
          case (in_array($type,array("Alphanumeric","Uppercase","Lowercase"))):
          	break;
          case ($type == "Numeric"):
			$this->numeric=TRUE;
            break;
		  default:
			$this->numeric=TRUE;
            $this->decimal=substr($type,-1,1);
		}
    }
    function output($data) {
		switch (TRUE) {
		  case (!$this->numeric):
			return $data;
		  case ($this->type == "Numeric"):
			return floatval($data);
		  default:
			return number_format($data,$this->decimal,".","");
        }
    }
}
/*
CREATE TABLE `subcategory` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned DEFAULT '0',
  `name` varchar(80) DEFAULT NULL,
  `prj` varchar(1024) DEFAULT NULL,
  `image_url` mediumtext,
  `thumbnail_url` mediumtext,
  `sketch_url` mediumtext,
  `parameters` mediumtext,
  `parameter_search` mediumtext,
  `documents` mediumtext,
  `sort_order` int(10) unsigned DEFAULT '0',
  `new_date` date NOT NULL DEFAULT '0000-00-00',
  `search_words` mediumtext,
  `download` mediumtext,
  `prj_landing_page` int(10) unsigned DEFAULT '0',
  `page_break` int(10) unsigned DEFAULT '1',
  `industry` mediumtext,
  `portal` mediumtext,
  `options` mediumtext,
  `output` varchar(12) NOT NULL DEFAULT '',
  `status` varchar(20) NOT NULL DEFAULT '',
  `leadtime_option` varchar(20) NOT NULL DEFAULT '',
  `leadtime_text` mediumtext,
  `currency` mediumtext,
  `price_increase` bigint(20) DEFAULT '0',
  `custom` int(11) DEFAULT '0',
  `lookup` int(11) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_key` (`category_id`,`name`),
  UNIQUE KEY `prj_UNIQUE` (`prj`),
  KEY `output_index` (`output`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Product subcategory (12/09/2024)'
*/
?>