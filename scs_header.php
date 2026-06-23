<?php
/*
COPYRIGHT NOTICE:
Copyright 1981 - 2025 Suburban Computer Services, Inc All Rights Reserved.

This program is furnished under a license restricing its use solely for the operation
of a designated computer for a particular  purpose, and may  not be copied, reproduced
disclosed,  or otherwise  used without the prior written, consent of Suburban Computer
Services, Inc.  Title to and ownership of the program shall at all times remain the
property of Suburban Computer Services.

development	http://localhost:8085
production	https://harcourt.co
*/

if (($_SERVER['SERVER_ADMIN'] ?? '') != "host@suburbancomputer.com") {
	ini_set("display_errors", "1");
	ini_set("error_log", $_SERVER['DOCUMENT_ROOT'] . "/error.log");
}

if (!ob_get_level()) ob_start();
print header("Access-Control-Allow-Origin: *");

ini_set('default_charset', 'UTF-8');
ini_set('memory_limit', '512M');
date_default_timezone_set("America/Detroit");
error_reporting(22517);
set_include_path(
	get_include_path() .
    PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . "/includes" .
    PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . "/includes_legacy" .
    PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . "/composer" .
    PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . "/scsps");

$_scs_https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 80) == 443;
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_secure', $_scs_https ? '1' : '0');
ini_set('session.cookie_httponly', 'true');
unset($_scs_https);

// standard library
require_once "database_mysqli.php";
require_once "functions.php";
require_once "forms_rev2.php";

// standard tables
require_once "classes/registry.php";
require_once "classes/country.php";
require_once "classes/currency.php";

// local tables
require_once "classes/bot.php";
require_once "classes/cad.php";
require_once "classes/campaign.php";
require_once "classes/carrier.php";
require_once "classes/category.php";
require_once "classes/content.php";
require_once "classes/document.php";
require_once "classes/inventory.php";
require_once "classes/industry.php";
require_once "classes/language.php";
require_once "classes/log.php";
require_once "classes/subcategory.php";
require_once "classes/pricing.php";
require_once "classes/orderhd.php";
require_once "classes/orderln.php";
require_once "classes/search.php";
require_once "classes/remote.php";
require_once "classes/blacklist.php";
require_once "classes/user.php";
require_once "classes/profile.php";
require_once "classes/search.php";
require_once "classes/hero.php";

require_once "classes/portal.php";
require_once "classes/portalcontent.php";
require_once "classes/portalcategory.php";
require_once "classes/portalitem.php";

$env = parse_ini_file(dirname($_SERVER['DOCUMENT_ROOT']) . '/.env');
$database->connect($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'], $env['DB_NAME']);
$database->set_charset("utf8mb4");

$database->registry=new registry_class();
switch (TRUE) {
  case (harcourt_api_key()):
    break;
  case (!isset($SCS_API)):
	session_start();

/*
09/18/2025 Session ID changing
    if (!file_exists("debug.txt")) {
        $fh=fopen("debug.txt", "a+");
        $text=array();
        $text[]="Date";
        $text[]="PHP Session";
        $text[]="User ID";
        $text[]="IP Address";
        $text[]="URL";
        $text[]="Referrer";
        fputcsv($fh, $text, "\t");
        fclose($fh);
    }

    $fh=fopen("debug.txt", "a+");
    $text=array();
    $text[]=date("m/d/Y H:i:s");
    $text[]=session_id();
    $text[]=(isset($_SESSION['user'])) ? $_SESSION['user']->id : "";
    $text[]=harcourt_remote_addr();
    $text[]=$_SERVER['PHP_SELF'];
    $text[]= $_SERVER['HTTP_REFERER'];
    fputcsv($fh, $text,  "\t");
    fclose($fh);
*/
	$database->log->track($database->registry->local->track);
	$menu=new menu_class();
	$menu->initial();
	switch (TRUE) {
	  case (!$menu->initial_timestamp):  // not initial
	  case (isset($_SESSION['campaign'])): // campaign
	  case ($_SESSION['user']->id < 0): // bot
	    $menu->page(FALSE);
	    break;
	  default:
	    $menu->page(TRUE);
	}
    break;
}
class menu_class {
    var $initial_timestamp=0;
    var $initial_source;
    var $page;
    var $wp=FALSE;
    var $url_prefix="";
    var $menu_sent=FALSE;
    var $catalog=FALSE;
    var $search_term;
    var $options=array();
    var $language=array();
    var $currency=array();
    var $category=array();
    var $subcategory=array();
    var $pages=array();
    var $cart=array();
    var $divs=array();
	function __construct() {
	    global $database;
		$this->page=new stdClass();
	    $query=array("select");
		$query[]="if (code='EN',1,2) as sort_key,";
		$query[]="language.* from language";
		$query[]="where active='1'";
		$query[]="order by sort_key,priority,name";
	    $database->language->query($query);
	    while ($database->language->fetch = $database->language->fetch_array() ) {
	        $database->language->fetch();
	        $this->language[$database->language->data->code]=$database->language->data;
	    }
		$database->language->free_result();
	    $query=array("select");
        $query[]="if(code='USD',0,1) as sort_key,";
        $query[]="currency.* from currency";
        $query[]="where exchange_rate > 0";
        $query[]="and active=1";
        $query[]="order by sort_key,name";
	    $database->currency->query($query);
  	    while ($database->currency->fetch = $database->currency->fetch_array() ) {
	        $database->currency->fetch();
	        $this->currency[$database->currency->data->code]=$database->currency->data;
	    }
		$database->currency->free_result();
        if (isset($_SESSION['cart'])) $this->cart=$_SESSION['cart'];
	}
    function initial() {
        global $database, $forms;
		if ($_REQUEST['scscpq_wp']) $_SESSION['scscpq_wp']=1;
	    switch (TRUE) {
	      case ( (isset($_SESSION['user'])) && ($_SESSION['user']->status == "Pending") ):
			$database->user->read($_SESSION['user']->id);
            $database->user->session();
	//		$_SESSION['user']=$database->user->data;
            $database->profile->load();
          	return;
	      case ($_SERVER['PHP_SELF']=="/scs_file.php");
	      case ($_SERVER['PHP_SELF']=="/stock.php");
	      case (isset($_SESSION['user'])):
			return;
		}
        $this->initial_timestamp=strtotime("now");
        foreach ($_GET as $key => $value) {
			if (preg_match("/^campaign$/i",$key)) {
            	if ($database->campaign->active($value)) {
					$_SESSION['campaign']=$database->campaign->data->code;
				  } else {
					unset($_SESSION['campaign']);
                }
                break;
			}
        }
	    if ($_COOKIE['harcourt']) $this->cookie($_COOKIE['harcourt']);
		$this->initial_user();
        if (!$this->initial_source) $this->initial_bot();
        if (!$this->initial_source) {
	        $database->user->data=new user_data_class();
	        $database->user->data->language_code=$this->request_language();
	        if ($_COOKIE['harcourt']) $this->cookie($_COOKIE['harcourt']);
            $database->user->session();
	 //       $_SESSION['user']=$database->user->data;
            $database->profile->load();
        }
		$url=parse_url($_SERVER['HTTP_REFERER']);
        if ( ($url['host']) && (!preg_match("/harcourt.co/i",$url['host'])) ) $database->log->update("initial:referer",array("date_time"=>$this->initial_timestamp,"user_id"=>$database->user->data->id,"comment"=>preg_replace("/^(www\.)/i","",$url['host'])));
        if (isset($_SESSION['campaign'])) $database->log->update("initial:campaign",array("date_time"=>$this->initial_timestamp,"user_id"=>$database->user->data->id,"comment"=>$_SESSION['campaign']));
	}
    function initial_user() {
        global $database;
        $database->user->read(harcourt_remote_addr(), "ip");
        switch (TRUE) {
          case ($database->user->meta->rows):
        	$this->login_update();
            return TRUE;
          case ($database->remote->access($_COOKIE['harcourt'],$_COOKIE['remote'])):
          	$this->login_update(1);
            return TRUE;
		}
	}
    function login_update($remote=0) {
        global $database;
    	if (!$this->initial_timestamp) $this->initial_timestamp=strtotime("now");
		$database->user->data->last_login=$this->initial_timestamp;
        $database->user->update(TRUE);
        $database->user->data->remote=$remote;
        $database->user->session();
	//	$_SESSION['user']=$database->user->data;
        $database->profile->load();
        $comment=array();
        $type=array("user");
        $type[]=( ($remote) ? "remote login" : "login");
        if ($remote) $comment[]="Domain: " . $database->user->data->domain . " IP Address " . harcourt_remote_addr();
		$database->log->update($type,array("date_time"=>$this->initial_timestamp,"user_id"=>$database->user->data->id,"comment"=>$comment));
        $this->initial_source="user";
    }
    function initial_bot() {
        global $database;
		$bot=$database->bot->match_bot(harcourt_remote_addr());
		if ($bot->id) {
        	$database->bot->session($bot);
            $this->initial_source="bot";
		}
    }
    function request_language() {
	    $languages=preg_split("/[;|,]/",$_SERVER['HTTP_ACCEPT_LANGUAGE']);
        foreach ($languages as $language) {
	        foreach ($this->language as $code => $data) {
	            if (preg_match("/^" . $code . "/i",$language)) return $code;
	        }
        }
		return "EN";
    }
	function page($redirect=FALSE) {
	    global $database, $forms;
		$forms->html_load();
		$forms->html_meta("noarchive, noimageindex","robots");
		$this->page=new stdClass();
        if (isset($_SESSION['scscpq_wp'])) {
	        $this->page->home=new menu_page($this->language('menu_home'),"/",array(),array(),"_top");
	        $this->page->products=new menu_page($this->language('menu_products'),($_SESSION['user']->language_code == "FR") ? "/catalogue/" : "/catalog/");
	        $this->page->quality=new menu_page($this->language('menu_quality'),"/quality.php");
	        $this->page->contact_us=new menu_page($this->language('menu_contact_us'),"/contact_us.php");
	        $this->page->newuser=new menu_page($this->language('menu_newuser'),"/newuser.php");
	        $this->page->cart=new menu_page($this->language('cart_cart'),"/cart.php");
	        $this->page->checkout=new menu_page($this->language('cart_checkout'),"/checkout.php");
		  } else {
	        $this->page->home=new menu_page($this->language('menu_home'),"/scs_index.php");
	        $this->page->products=new menu_page($this->language('menu_products'),"/catalog.php");
	        $this->page->quality=new menu_page($this->language('menu_quality'),"/quality.php");
	        $this->page->contact_us=new menu_page($this->language('menu_contact_us'),"/contact_us.php");
	        $this->page->newuser=new menu_page($this->language('menu_newuser'),"/newuser.php");
	        $this->page->cart=new menu_page($this->language('cart_cart'),"/cart.php");
	        $this->page->checkout=new menu_page($this->language('cart_checkout'),"/checkout.php");
        }
		$this->catalog=$_SESSION['user']->catalog;
        if ($database->user->access("Executive",FALSE)) {
			$this->catalog=TRUE;
        	$this->page->dashboard=new menu_page($this->language('menu_dashboard'),"/dashboard.php");
		}
        $this->category=array();
        $this->subcategory=array();
        $query_where=array();
        if ($database->user->access("Administrator",FALSE)) {
			$query_where['status']=new query_where("subcategory.status","in",array("Active","Pending"));
		  } else {
			$query_where['status']=new query_where("subcategory.status","=","Active");
		}
        $query_other=array();
        if ($_SESSION['user']->industry_id) $query_other[]="subcategory.industry like " . fn_escape("%^" . $_SESSION['user']->industry_id . "^%");
        if ($_SESSION['user']->portal_id) $query_other[]="subcategory.portal like " . fn_escape("%^" . $_SESSION['user']->portal_id . "^%");
        $query=array("select id,category_id from subcategory");
        $query[]=$database->where($query_where);
        if (sizeof($query_other)) $query[]=" and(" . implode("\n or ", $query_other) . ")";
        $query[]="order by id";
		$database->temp->query($query);
        while ($database->temp->fetch = $database->temp->fetch_array() ) {
			if (!in_array($database->temp->fetch['category_id'],$this->category)) $this->category[]=$database->temp->fetch['category_id'];
			$this->subcategory[]=$database->temp->fetch['id'];
        }
		$database->temp->free_result();
        $this->pages=array();
        $query=array();
        $query[]="select * from campaign";
        $query[]="where active='1'";
        $query[]="and type != 'Invite'";
        $query[]="order by sort_order,name";
        $database->campaign->query($query);
        while ($database->campaign->fetch = $database->campaign->fetch_array() ) {
			$database->campaign->fetch();
            if ($database->campaign->active()) {
            	switch (TRUE) {
                  case ($database->campaign->data->type == "Public"):
                  case ( (isset($_SESSION['campaign'])) && ($database->campaign->data->code == $_SESSION['campaign']) ):
					$this->pages['solutions'][ $database->campaign->data->name ]=fn_url("/campaign.php",array("campaign"=>$database->campaign->data->code));
                  	break;
                }
            }
        }
        $query_where=array();
        $query_where[]=new query_where("pages.active","=",1);
        $query_where[]=new query_where("pages.record_type","<>","");
		$query_where[]=new query_where("pages.user_type","in",array_merge(array(""),$database->user->type_list()));
		$query=array("select");
        $query[]="pages.record_type as 'record_type',";
        $query[]="if(isnull(content.id),pages.name,content.name) as 'name',";
        $query[]="pages.document_id as 'document_id',";
        $query[]="pages.category_id as 'category_id',";
        $query[]="pages.url as 'url'";
        $query[]="from pages";
        $query[]="left join content on content.record_type='pages' and content.record_id=pages.id and content.language_code=" . fn_escape($_SESSION['user']->language_code);
        $query[]=$database->where($query_where);
        $query[]="order by pages.record_type,pages.sort_order,pages.name";
		$database->temp->query($query);
		while ($database->temp->fetch = $database->temp->fetch_array() ) {
			if ( ($database->temp->fetch['record_type'] == 'products') && (!in_array($database->temp->fetch['category_id'],$this->category)) ) continue;
			if (!array_key_exists($database->temp->fetch['record_type'],$this->pages)) $this->pages[ $database->temp->fetch['record_type'] ]=array();
			$this->pages[ $database->temp->fetch['record_type'] ][ $database->temp->fetch['name'] ]=$database->temp->fetch['url'];
        }
		$database->temp->free_result();
        switch (TRUE) {
          case (!$redirect):
          	return;
          case (isset($this->page->dashboard)):
	        $url=$this->page->dashboard->url;
	        break;
	      case (!$_SESSION['user']->id):
	      case ($_SESSION['user']->status != "Active"):
          case (!sizeof($this->category)):
	        $url=$this->page->home->url;
	        break;
	      default:
	        $url=fn_url($this->page->products->url);
	    }
		$parse=parse_url($url);
		if (!preg_match("/^" . preg_quote($_SERVER['PHP_SELF'],"/") . "/i", $parse['path'])) {
        	fn_url_redirect($url);
		}
    }

	function head() {
	    global $database, $forms;
        if (isset($_SESSION['scscpq_wp'])) {
        	$this->wp=TRUE;
        	if (isset($_REQUEST['debug_wp'])) $this->wp=$_REQUEST['debug_wp'];
		  } else {
			$forms->html->script("js","/scs_scripts/scscpq_wp.js?v=LOCAL");
		}
		$forms->html_open();
        if ($menu_sent) return;
    	$this->menu_sent=TRUE;
		$results=array();
        $results[]=$this->div("scs_body",TRUE);
        if (!$this->wp) $results[]=$this->ribbon();
        $results[]=$this->div("scs_content",TRUE);
		print implode("\n",$results);
    }
    function copyright() {
        global $database, $forms;
		$results=array();
        foreach ($this->divs as $div) {
        	$results[]=$this->div();
        }
        print implode("\n",$results);
        $forms->html_close();
    }

    function div($name="", $id=FALSE, $classes="") {
    	$resutls=array();
        switch (TRUE) {
          case ( (!$name) && (sizeof($this->divs)) ):
        	$name=array_pop($this->divs);
            $results[]="</div>";
            $results[]="<!-- {$name} -->";
		  	break;
		  case ($name):
			array_push($this->divs,(($id) ? "#" : ".") . $name);
            $results[]="<div";
            $results[]=( ($id) ? "id" : "class") . "='" . $name . "'";
            if ($classes) $results[]="class='" . $classes . "'";
            $results[]=">";
            break;
		}
        return implode(" ",$results);
    }
    function language($field) {
		switch (TRUE) {
		  case (!$field):
			$results="Missing";
            break;
          case (!$_SESSION['user']->language_code):
          case (!array_key_exists($_SESSION['user']->language_code,$this->language)):
		  case (!array_key_exists($field,$this->language[$_SESSION['user']->language_code]->menu)):
			$results=$field;
            break;
		  default:
			$results=$this->language[$_SESSION['user']->language_code]->menu[$field];
		}
		return $results;
    }
    function cookie($text, $cookie="harcourt") {
		$options=array();
	    $options['expires']=( ($text) ? strtotime("+ 1 year") : strtotime("-1 hour"));
	    $options['secure']=( ($_SERVER['HTTPS']) ? 1 : 0);
	    $options['httponly']=1;
        $options['domain']=$_SERVER['HTTP_HOST'];// fix this
//	    $options['samesite']="None";
		fn_cookie($cookie,$text,$options);
    }
    function document_viewer($document,$height=0,$override=FALSE,$login_url="") {
        global $forms;
	    $_SESSION['document_viewer']=$document;
	    $_SESSION['document_viewer_override']=$override;
	    $url_query=array();
	    if ($login_url) $url_query['login_url']=$login_url;
	    $url=fn_url('document_viewer.php',$url_query);
        switch (TRUE) {
		  case (!$height):
			print "<iframe src='$url' id='document_viewer' width='100%' height='600' frameborder='0' scrolling=no onload='iframe_size(this,600);'></iframe>\n";
            break;
		  case ($height < 0):
			print "<iframe src='$url' id='document_viewer' width='100%' height='1' frameborder='0' scrolling=no onload='iframe_size(this,0);'></iframe>\n";
            break;
		  default:
			print "<iframe src='$url' id='document_viewer' width='100%' height='$height' frameborder='0' scrolling=no></iframe>\n";
		}
    }
    function tab_viewer($url="") {
        global $database, $forms;
		if (!$url) $url=$_SERVER['PHP_SELF'];
	    $query_where=array();
	    $query_where[]=new query_where("content.record_type","=","document");
	    $query_where[]=new query_where("content.record_id","=","pages.document_id","","field");
	    $query_where[]=new query_where("content.language_code","in",array("EN",$_SESSION['user']->language_code));
	    $query=
	        "select \n" .
            "pages.record_type as 'pages_record_type', \n" .
            "pages.name as 'pages_name', \n" .
	        "if (language_code='EN',2,1) as sort_language, \n" .
	        "content.* from content \n" .
			"left join pages on pages.url=" . fn_escape($url) . "\n" .
	        $database->where($query_where) .
	        "order by sort_language";
	    $database->content->query($query);
	    $database->content->fetch(TRUE);
        if (in_array($database->content->fetch['pages_record_type'],array("general","solutions"))) $database->log->update("page:" . $database->content->fetch['pages_record_type'],array("comment"=>$database->content->fetch['pages_name']));
	    $database->content->free_result();
        print "\n<!-- Body Content -->\n";
		echo $database->content->data->content;
        print "\n<!-- End Body Content -->\n";
    }
    function solutions() {
        global $database;
    	if (func_num_args()) {
        	$url=func_get_arg(0);
		  } else {
			$url=$_SERVER['PHP_SELF'];
		}
		$query_where=array();
        $query_where[]=new query_where("record_type","=","solutions");
        $query_where[]=new query_where("url","=",$url);
        $query=
        	"select user_type,name from pages \n" .
            $database->where($query_where);
		$database->temp->query($query);
		$database->temp->fetch();
        if (strlen($database->temp->fetch['user_type'])) $database->user->access($database->temp->fetch['user_type']);
    }
	function ribbon() {
        global $database, $forms;
	    $catalog_url=( (!isset($_SESSION['punchout'])) ? "/catalog.php" : $_SESSION['punchout']->url);
		$style=array();
        $style[]="";
        $style[]="<script>";
        $style[]="function menu_search() {";
        $style[]="	search_code=jQuery('#scscpq_search_text').val();";
        $style[]="	search_code=search_code.trim()";
        $style[]="	if (search_code.length < 2) {";
        $style[]="		alert('Product Search must be at least 2 characters')";
        $style[]="		return;";
        $style[]="	}";
        $style[]="	location.href='{$catalog_url}?search_source=term&search_code=' + encodeURIComponent(search_code);";
        $style[]="}";
        $style[]="</script>";
        $style[]="";
		$results=array();
	    $results[]=implode("\n",$style);
	    $results[]=$this->div("scs_menu",TRUE);
        $options=array();
        $options['height']=70;
        $options['style']="transform: translate(0%, 10px);";
        $options['title']="Harcourt Industrial";
	    $home_url=( (!isset($_SESSION['punchout'])) ? "/" : "");
		$results[]="<div class='scs_menu_left' style='padding-top: 10px;')>" . fn_href($forms->img("/scs_images/logo.png",$options),$home_url) . "</div>";
        if (isset($this->page->dashboard)) $results[]="<div class=scs_menu_left>" . fn_href($this->page->dashboard->name,$this->page->dashboard->url) . "</div>";
        $results[]="<div class='scs_menu_left scscpq_impersonate'>" . fn_href("Stop Impersonate","/user_impersonate.php") . "</div>";
	    $results[]="<div class='scs_menu_left scscpq_newuser'>" . fn_href("New user", "/newuser.php") . "</div>";
        $results[]="<div class='scs_menu_right scscpq_cart'></div>";
        $results[]="<div class='scs_menu_right scscpq_search'></div>";
        $results[]="<div id='menu-item-141' class='scs_menu_right scscpq_catalog'>" . fn_href("Catalog",$catalog_url,array(),array("class"=>"scs_menu_button")) . "</div>";
	    $results[]=$this->div();
	    return implode("",$results);
    }
}
function prj_verify($prj,$options=array()) {
	if (!array_key_exists("mandatory",$options)) $options['mandatory']=FALSE;
    if ( (!strlen($prj)) && (!$options['mandatory']) ) return TRUE;
	parse_str($prj,$results);
    switch (TRUE) {
      case (!array_key_exists("info",$results)):
      	return FALSE;
      default:
		return TRUE;
	}
}
class user_menu_class {
	var $name;
    var $url;
    var $target;
    var $px;
    var $css;
    function __construct($item,$options=array()) {
		if (array_key_exists("target",$options)) $this->target="target=" . $options['target'];
		if (array_key_exists("css",$options)) {
        	$this->css=$options['css'];
		  } else {
			$this->css="menu";
		}
		if (array_key_exists("px",$options)) {
        	$this->px=$options['px'];
		  } else {
			$this->px=140;
		}
		$this->name=$item->name;
        $this->url=$item->url;
    }
}
function harcourt_api_key() {
    global $database, $forms;
    switch (TRUE) {
      case (!isset($database->registry->local->api_key)):
      case (!array_key_exists("api_key", $_REQUEST)):
      case ($_REQUEST['api_key'] != $database->registry->local->api_key):
      case (!isset($database->registry->cron->url)):
      case (!in_array($_SERVER['SCRIPT_NAME'], $database->registry->cron->url)):
        return FALSE;
    }
    $forms->cron=TRUE;
    return TRUE;
}
function harcourt_remote_addr() {
    switch (TRUE) {
      case (strlen($_SERVER['HTTP_X_REAL_IP'])):
        return $_SERVER['HTTP_X_REAL_IP'];
      case (strlen($_SERVER['HTTP_X_FORWARDED_FOR'])):
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
      default:
        return $_SERVER['REMOTE_ADDR'];
    }
}
?>