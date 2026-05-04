<?php
class menu_class {
	var $title;
    var $meta=array();
	var $messages=array();
	var $errors=array();
    var $errors_text=array();
	var $css=array();
	var $script=array();
    var $script_text=array();
    var $page=array();
	var $background=array();
    var $foreground=array();
	var $login_attempt=5;
    var $cad_max=5;
    var $url_prefix="";
    var $domain="";
    var $company=array();
    var $omit=FALSE;
    var $search=FALSE;
    var $search_term;
    var $nav_field=array("first"=>"&lt;&lt; First","previous"=>"&lt; Previous","return"=>"Return","next"=>"Next &gt;","last"=>"Last &gt;&gt;");
	function __construct() {
	    $this->background['red']="style='background: #ff0000'";
	    $this->background['green']="style='background: #00ff00'";
	    $this->background['yellow']="style='background: #ffff66'";
	    $this->background['silver']="style='background: #dddddd'";
        $this->background['greenbar']="style='background: #99cc99'";
	    $this->foreground['red']="style='color: #ff0000'";
        if (fn_development_server()) {
        	$this->url_prefix="";
            $this->domain="localhost";
          } else {
        	$this->url_prefix="";
            $this->domain=strtolower($_SERVER['SERVER_NAME']);
		}
        if ($_SESSION['user']->status == "Administrator") $this->search=TRUE;
		$this->page["home"]=new menu_page("/index.php",FALSE,"Home");
		$this->page["administrator"]=new menu_page("/administrator.php",FALSE,"Home");
		$this->page["products"]=new menu_page("/products.php",FALSE,"Products page");
        $this->css[]="/css/harcourt.css";
        $this->script[]="/js/harcourt.js";
        $this->company['name']="";
        $this->company['address1']="";
        $this->company['csz']="";
        $this->company['phone']="";
	}
	function head($omit=FALSE) {
    	$this->omit=$omit;
		print "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">\n";
	    print "<head>\n";
	    if ($this->title) print "<title>" . $this->title . "</title>\n";
	    print "<meta http-equiv=\"content-type\" content=\"text/html; charset=UTF-8\">\n";
        switch (TRUE) {
          case ($this->meta['description']):
          	break;
          case ($description):
			$this->meta['description']=$description;
            break;
          default:
			$this->meta['description']=$this->title;
        }
	    foreach ($this->meta as $key => $value) {
	        if ($value) print "<meta name=\"" . $key . "\" content=\"" . $value . "\">\n";
	    }
        if (sizeof($this->css)) {
        	foreach ($this->css as $css) {
				print "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $css . "\">\n";
            }
        }
        if (sizeof($this->script)) {
        	foreach ($this->script as $script) {
				print "<script type=\"text/javascript\" src=\"" . $script . "\"></script>\n";
            }
        }
        if (sizeof($this->script_text)) {
        	print "<script language=javascript>\n";
            foreach ($this->script_text as $text) {
            	print "$text\n";
            }
            print "</script>\n";
        }
        print "<link rel=\"icon\" href=\"/H.ico\" type=\"image/x-icon\"></link>\n";
	    print "</head>\n";
	    print "<body>\n";
        if ($this->omit) return;
        print "<table border=0>\n";
		print "<tr><td width=1024>\n";
		print "<table width=1024 border=0>\n";
        print "<tr>\n";
        print "<td width=700><div align=left><span style=\"color:#666\"><a href=\"/\"><img src=\"/images/logo.jpg\" width=209 height=61 border=0 align=left style=\"padding:20px 0px 20px 20px\"/></a></span></div></td>\n";
        switch (TRUE) {
          case (!$this->search):
          case (!in_array(149,$_SESSION['document'])):
			print
				"<td width=286>" .
            	"<div align=right><span style=\"color:#666; font-size:18px; font-family:Calibri, Arial;\">&quot;your tooling component supplier for the 21st century&quot;</span></div>\n" .
                "</td>";
          	break;
          default:
        	print
				"<td width=286>" .
				"<form id=search_form name=search_form method=post action='/catalog.php'>\n" .
                "<input type=hidden name=action value='search'>\n" .
                "<input type=hidden name=initial value='1'>\n" .
                "<input type=text name=search_term class=search_text" . fn_html_value($this->search_term) . ">" .
				"<input type=image class=search_image src='/images/black-search-rollover.gif' width=77 height=25 border=0 " .
                " onmouseover=\"image_rollover(this,'/images/Red-search-rollover.gif');\"" .
                " onmouseout=\"image_rollover(this,'/images/black-search-rollover.gif');\">" .
                "</form>\n" .
                "</td>\n";
        }
        print "</tr></table>\n";
        $user_menu=array();
        $user_menu[]=new user_menu_class("HOME","/","",234,"menuhome");
        $user_menu[]=new user_menu_class("ABOUT US","/about_us.php");
        $user_menu[]=new user_menu_class("PRODUCTS","/products.php");
        $user_menu[]=new user_menu_class("QUALITY","/quality.php");
        $user_menu[]=new user_menu_class("CONTACT US","/contact_us.php");
//        $user_menu[]=new user_menu_class("FEEDBACK","/feedback/feed-back-sample.html");
        switch (TRUE) {
          case ($_SESSION['user']->status == "Administrator"):
			$user_menu[]=new user_menu_class("ADMINISTRATOR","/administrator.php");
          	break;
          case (($_SESSION['user']->id) && ($_SESSION['user']->status == "Active")):
			$user_menu[]=new user_menu_class("NEWSROOM","/deploy-to-web/Main.html","newsroom");
          	break;
          case ($_SESSION['user']->id):
			$user_menu[]=new user_menu_class("MY ACCOUNT","/login.php");
          	break;
          default:
			$user_menu[]=new user_menu_class("NEW USER","/login.php");
		}
		print "<table width=1024 border=0>\n";
        print "<tr>\n";
        print "<td height=24>\n";
        foreach ($user_menu as $item) {
			print "<div class='" . $item->css . "'><a href='" . $item->url . "' " . $item->target . ">\n";
			print "<div class='home left' style=\"width:" . $item->px . "px;\">" . $item->text . "</div>\n";
            print "</a></div>\n";
        }
		print "</td></tr>\n";
	    print "</table>\n";
    }
    function copyright($copyright=TRUE) {
    	if (!$this->omit) {
	        print "<br>";
	        print "<table width=1024 border=0>\n";
	        print "<tr>\n";
	        print "<td><img src='images/bottom_bar.jpg' alt='bottombar' width=1024 height=1></td>\n";
	        print "</tr>\n";
	        print "</table>\n";

	        print "<table width=1024 border=0>\n";
	        print "<tr class=bottom>\n";
	        print "<td width=62>&nbsp;</td>\n";
	        print "<td width=450 class=left>\n";
	        print "<a href=\"/site_map.php\">SITE MAP</a> |\n";
	        print "<a href=\"/privacy_policy.php\">PRIVACY POLICY</a> |\n";
	        print "<a href=\"/legal_notice.php\">LEGAL NOTICE</a>\n";
	        if ($_SESSION['impersonate']) print " | <a href=\"/logoff.php\">STOP IMPERSONATE</a>\n";
	        print "</td>\n";
	        print "<td width=450 class=right style=\"color: #999; font-family:Calibri, Arial; font-size:10px;\">COPYRIGHT HARCOURT INDUSTRIAL " . date("Y") . ". ALL RIGHTS RESERVED.</td>\n";
	        print "<td width=62>&nbsp;</td>\n";
	        print "</tr>\n";
	        print "</table>\n";
	        print "</td></tr>\n";
	        print "</table>\n";
		}
	    print "</body>\n";
	    print "</html>\n";
    }
	function message($debug=array()) {
    	if (is_array($debug)) {
	        foreach ($debug as $key => $value) {
	            $this->messages[]="<b>Key:</b> $key <b>value:</b> $value";
	        }
    	}
	    if (sizeof($this->errors)) $this->messages[]="Correct " . sizeof($this->errors) . " errors";
	    foreach ($this->messages as $text) {
        	if ($output) {
            	$output .= "<br>$text\n";
              } else {
              	$output .= "<span class=large><b>$text</b></span>\n";
            }
        }
        if ($output) {
        	print "<p class='standard center'>" . $output . "</p>\n";
        }

	}
    function document_viewer($document,$height=0,$override=FALSE) {
	    $_SESSION['document_viewer']=$document;
	    $_SESSION['document_viewer_override']=$override;

        switch (TRUE) {
		  case (!$height):
			print "<iframe src='document_viewer.php' id='document_viewer' width='100%' height='600' frameborder='0' scrolling=no onload='iframe_size(this,600);'></iframe>\n";
            break;
		  case ($height < 0):
			print "<iframe src='document_viewer.php' id='document_viewer' width='100%' height='1' frameborder='0' scrolling=no onload='iframe_size(this,0);' ></iframe>\n";
            break;
		  default:
			print "<iframe src='document_viewer.php' id='document_viewer' width='100%' height='$height' frameborder='0' scrolling=no></iframe>\n";
		}
    }
    function access($document=0) {
	    switch (TRUE) {
	      case (!$_SESSION['user']->id):
	        fn_url_redirect($this->page["login"]);
          case ($_SESSION['user']->status == "Administrator"):
          case (($document) && (in_array($document,$_SESSION['document']))):
			return;
          default:
			fn_url_redirect($this->page["home"]);
	    }
    }
    function landing_page() {
	    switch (TRUE) {
	      case ($_SESSION['user']->status == "Administrator"):
	        $redirect=$this->page["administrator"];
	        break;
	      case (!$_SESSION['user']->id):
	      case ($_SESSION['user']->status != "Active"):
	        $redirect=$this->page["home"];
	        break;
	      default:
	        $redirect=$this->page["products"];
	    }
	    if (strtolower($_SERVER['PHPSELF']) != $redirect->page) fn_url_redirect($redirect);
    }
    function select($field_name,$data,$compare,$blank=TRUE,$script="") {
		$output="<select name='$field_name' id='$field_name' " . $this->errors[$field_name];
		if ($script) $output .= " onchange=\"javascript:$script\"";
        $output .= ">\n";
        if ($blank) $output .= "<option></option>\n";
		foreach ($data as $key => $value) {
			if ((isset($data[0])) && (isset($data[sizeof($data) - 1]))) $key=$value;
            if ($key == $compare) {
	            $selected="selected";
			   } else {
	            $selected="";
	        }
	        $output .= "<option value='$key' $selected>$value</option>\n";
	    }
	    $output .= "</select>\n";
        return $output;
    }
    function checkbox($field_name,$compare,$value=1,$script="") {
    	if ($compare == $value) {
        	$checked="checked";
		  } else {
          	$checked="";
		}
		$output = "<input type=checkbox name='$field_name' id='$field_name' value='$value' $checked " . $this->errors[$field_name];
		if ($script) $output .=" onclick=\"javascript:$script\"";
		$output .= ">\n";
        return $output;
    }
    function textarea($field_name,$text,$rows, $cols, $script="") {
		$output = "<textarea name='$field_name' rows=$rows cols=$cols " . $this->errors[$field_name];
        if ($script) $output .= " onchange=\"javascript:$script\"";
        $output .= ">$text</textarea>\n";
        return $output;
    }
	function image($image, $comment="", $width=0, $height=0, $align="") {
	    switch (TRUE) {
	      case (!$image):
	      case (!file_exists($image)):
          	return;
	    }
	    if ($comment) $image_alt="alt='" . htmlentities($comment,ENT_QUOTES) . "'";
	    if ($align) $image_align="align=$align";
	    $url_query=array();
	    $url_query["image"]=$image;
	    if ($width) $url_query["width"]=$width;
	    if ($height)  $url_query["height"]=$height;
	    return "<img src='" . fn_url($menu->url_prefix . "/image.php",FALSE,$url_query) . "' $image_alt $image_attribute border=0>";
    }
    function nav_field($url,$item="",$last_item="") {
		print "<p class='standard center'>";
        foreach ($this->nav_field as $key => $value) {
        	switch (TRUE) {
			  case ((!$item) && ($key != "return")):
				break;
              case (!isset($url[$key])):
              case (!$url[$key]):
				print "<input type=button class='nav_inactive' value='$value'>\n ";
                break;
			  default:
				print "<input type=button class='nav_active' value='$value'  onclick=\"javascript:window.location='" . $url[$key] . "';\">\n ";
			}
        }
        if ($item) print "<br>Item " . number_format($item) . " of " . number_format($last_item);
        print "</p>\n";
    }
}
class user_menu_class {
	var $text;
    var $url;
    var $target;
    var $px;
    var $css;
    function __construct($text,$url,$target="",$px=140,$css="menu") {
//$px=114 when adding more
//$px was 140
    	$this->text=$text;
        $this->url=$url;
        if ($target) $this->target= "target=$target";
        $this->px=$px;
		$this->css=$css;
    }
}
?>