<?php
$database->language = new language_class();
class language_class extends database_class {
	function scs_table_version() {
		$results=array();
        $results['01/16/2022']="Enhance menu options";
        $results['02/23/2015']="Add image_url";
        $results['09/11/2014']="Expand menu options";
        $results['07/23/2014']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
	function __construct() {
    	$this->data=new language_data_class();
        $this->fetch=array();
    	$this->meta=new database_meta_class();
        $this->constant=new language_constant_class();
    }
    function fetch($fetch=FALSE) {
        if ($fetch) $this->fetch=$this->fetch_array();
    	$this->data=new language_data_class();
	    $this->data->id=$this->fetch['id'];
	    $this->data->code=$this->fetch['code'];
	    $this->data->country_code=$this->fetch['country_code'];
	    $this->data->name=$this->fetch['name'];
	    $this->data->local_name=$this->fetch['local_name'];
	    $this->data->ps_name=$this->fetch['ps_name'];
	    $this->data->priority=$this->fetch['priority'];
        $obj=json_decode($this->fetch['menu'],TRUE);
        if (is_array($obj)) $this->data->menu=$obj;
        if (!is_array($this->data->menu)) $this->data->menu=array();
        foreach ($this->constant->menu as $key => $value) {
			if (!array_key_exists($key,$this->data->menu)) $this->data->menu[$key]=$value;
        }
	    $this->data->image_url=$this->fetch['image_url'];
	    $this->data->active=$this->fetch['active'];
    }
    function read($id,$field="id") {
        $query=array("select * from language");
        $query[]="where {$field}=" . fn_escape($id);
        $this->query($query);
        if ($this->meta->rows) {
        	$this->fetch(TRUE);
			$this->free_result();
		  } else {
			$this->data=new language_data_class();
		}
    }
    function update($update=FALSE) {
		$fields=array();
		$fields[]="id=" . fn_escape($this->data->id,FALSE);
		$fields[]="code=" . fn_escape($this->data->code);
		$fields[]="country_code=" . fn_escape($this->data->country_code);
		$fields[]="name=" . fn_escape($this->data->name);
		$fields[]="local_name=" . fn_escape($this->data->local_name);
		$fields[]="ps_name=" . fn_escape($this->data->ps_name);
		$fields[]="image_url=" . fn_escape($this->data->image_url);
		$fields[]="priority=" . fn_escape($this->data->priority,FALSE);
		$fields[]="menu=" . fn_escape(json_encode($this->data->menu));
		$fields[]="active=" . fn_escape($this->data->active,FALSE);
        $query=array();
    	if ($update) {
			$query[]="update language set";
            $query[]=implode(",\n",$fields);
            $query[]="where id=" . fn_escape($this->data->id);
		  } else {
			$query[]="insert into language set";
            $query[]=implode(",\n",$fields);
        }
		$this->query($query);
		if ((!$this->data->id) && (!$this->meta->error)) $this->data->id = $this->insert_id();
    }
    function delete($id) {
	    $query="delete from language where id=" . fn_escape($id);
		$this->query($query);
    }
    function select($compare,$select_options=array(),$forms_options=array() ) {
    global $forms;
		if (array_key_exists("field",$select_options)) {
        	$field=$select_options['field'];
		  } else {
          	$field="language_code";
		}
		if (array_key_exists("blank",$select_options)) {
        	$blank=$select_options['blank'];
		  } else {
          	$blank=TRUE;
		}
		if (array_key_exists("local",$select_options)) {
        	$local=$select_options['local'];
		  } else {
          	$local=TRUE;
		}
		$results=array();
		if ($local) {
        	$query_order="local_name";
		  } else {
        	$query_order="name";
		}
	    $query=
        	"select * from language \n" .
            $this->where($query_where) .
            "order by priority,$query_order";
	    $this->query($query);
        $results=array();
	    while ($this->fetch=$this->fetch_array() ) {
        	$this->fetch();
            if ($local) {
				$text=$this->data->local_name;
			  } else {
				$text=$this->data->name;
			}
            if ($this->data->country_code) $text .= " (" . $this->data->country_code . ")";
			$results[$this->data->code]=$text;
	    }
	    $this->free_result();
		return $forms->select($field,$results,$compare,$blank,$forms_options);
    }
}
class language_data_class {
	var $id;
    var $code;
    var $country_code;
    var $name;
    var $local_name;
    var $ps_name;
    var $menu=array();
    var $image_url;
    var $priority=1;
    var $active=1;
}
class language_constant_class {
	var $menu=array();
    var $product_sortable=array();
    var $product_fixed=array();
    function __construct() {
//Menu
		$this->menu['menu_home']="Home";
        $this->menu['menu_products']="Products";
        $this->menu['menu_quality']="Quality";
        $this->menu['menu_contact_us']="Contact";
        $this->menu['menu_dashboard']="Dashboard";
        $this->menu['menu_solutions']="Solutions";
        $this->menu['menu_myaccount']="My Account";
        $this->menu['menu_newuser']="Request Access";
        $this->menu['menu_history']="History & Values";
        $this->menu['menu_privacy']="Privacy";
        $this->menu['menu_legal']="Legal Notice";
        $this->menu['menu_company']="Company";
        $this->menu['menu_search']="Search";
        $this->menu['menu_search_placeholder']="Product Search";
        $this->menu['menu_cart']="Items";
        $this->menu['menu_cart_title']="View Items";
        $this->menu['menu_portal']="Portal";
//Catalog tabs
        $this->menu['catalog_overview']="Overview";
        $this->menu['catalog_products']="Product Details";
		$this->menu['catalog_sku']="Part#";
        $this->menu['catalog_cad']="Configure";
        $this->menu['catalog_search']="Search";
        $this->menu['catalog_download']="Download";
        $this->menu['catalog_competitors']="Competitors";
        $this->menu['catalog_tabs']="Tabs";
        $this->menu['catalog_specials']="Specials";
        $this->menu['catalog_customs']="Customs";
        $this->menu['catalog_profile']="Profile";
//Cart
        $this->menu['cart_cart']="Shopping Cart";
        $this->menu['cart_add']="Add to Cart";
        $this->menu['cart_clear']="Clear Cart";
        $this->menu['cart_update']="Update Cart";
        $this->menu['cart_checkout']="Check Out";

// Products Tabs - Sortable
		$this->product_sortable['catalog_overview']="Product overview, features and applications";
		$this->product_sortable['catalog_products']="Tabular list of products";
		$this->product_sortable['catalog_sku']="SKU detail, including specifications and pricing (where available)";
		$this->product_sortable['catalog_specials']="Special order";
        $this->product_sortable['catalog_customs']="Custom";
        $this->product_sortable['catalog_download']="Download additional files, including CAD and PDF datasheets";
		$this->product_sortable['catalog_cad']="CAD configurator and 3D viewer";
 		$this->product_sortable['cart_cart']="Shopping cart";
		$this->product_sortable['catalog_competitors']="Competitor SKU";
	}
/* hotfix
CREATE TABLE `xlanguage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(3) NOT NULL DEFAULT '',
  `country_code` char(2) NOT NULL DEFAULT '',
  `name` varchar(45) NOT NULL DEFAULT '',
  `local_name` varchar(45) NOT NULL DEFAULT '',
  `ps_name` varchar(20) NOT NULL DEFAULT '',
  `menu` mediumtext,
  `priority` int(10) unsigned NOT NULL DEFAULT '1',
  `active` int(10) unsigned NOT NULL DEFAULT '1',
  `image_url` varchar(60) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code_key` (`code`,`country_code`),
  UNIQUE KEY `name_key` (`country_code`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Languages (02/23/2015)'


ALTER TABLE `harcourt`.`xlanguage`
CHARACTER SET = utf8mb4 , COLLATE = utf8mb4_unicode_ci ,
COMMENT = 'Languages (10/03/2025)' ;


or
ALTER TABLE `xlanguage`
CHARACTER SET = utf8mb4 ,
COMMENT = 'Languages (10/03/2025)' ;



*/
}
/*
CREATE TABLE `language` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(3) NOT NULL DEFAULT '',
  `country_code` char(2) NOT NULL DEFAULT '',
  `name` varchar(45) NOT NULL DEFAULT '',
  `local_name` varchar(45) NOT NULL DEFAULT '',
  `ps_name` varchar(20) NOT NULL DEFAULT '',
  `menu` mediumtext,
  `priority` int(1) unsigned NOT NULL DEFAULT '1',
  `active` int(1) unsigned NOT NULL DEFAULT '1',
  `image_url` varchar(60) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code_key` (`code`,`country_code`),
  UNIQUE KEY `name_key` (`country_code`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Languages (02/23/2015)'
*/
?>