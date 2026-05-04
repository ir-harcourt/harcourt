<?php

	function head() {
	global $database, $forms;
		switch (TRUE) {
		  case (!func_num_args()):
            break;
		  case (is_array(func_get_arg(0))):
			$this->options=func_get_arg(0);
            break;
		  default:
			$this->options['menu_sent']=func_get_arg(0);
        }
        if (!array_key_exists("menu_sent",$this->options)) $this->options['menu_sent']=FALSE;
        if (!array_key_exists("scs_div",$this->options)) $this->options['scs_div']=TRUE;
        if (!array_key_exists("top_div",$this->options)) $this->options['top_div']="";
        if (!array_key_exists("products_div",$this->options)) $this->options['products_div']="";
        if (!array_key_exists("outerContainer_div",$this->options)) $this->options['outerContainer_div']="outerContainer_div";
		$forms->html_open();
        if ($menu_sent) return;
    	$this->menu_sent=TRUE;
		$results=array();
        if ($this->options['top_div']) $results[]=$this->div($this->options['top_div'],TRUE);
		switch (TRUE) {
	      case (!$_SESSION['user']->id):
	      case ($_SESSION['user']->status != "Active"):
	        $results[]=$this->div("landing-header",TRUE,"background top_menu");
            break;
          default:
	        $results[]=$this->div("mainHeader",TRUE,"background top_menu");
		}
	    $results[]=$this->div("outerContainer");
	    $results[]=$this->div("container");
	    $results[]=$this->div("left-header");
	    $results[]="<a href='/'><img src='/scs_images/logo.png' /></a>";
	    $results[]=$this->div();
	    $results[]=$this->div("right-header");
	    $results[]=$this->div("top");
        $language_list=array();
	    $results[]=$this->div("language");
	    $languages=$this->language;
	    if ($_SESSION['user']->language_code) {
	        unset($languages[$_SESSION['user']->language_code]);
	        $languages[$_SESSION['user']->language_code]=$this->language[$_SESSION['user']->language_code];
	    }
        foreach($languages as $language) {
			$option=new forms_html5_class();
            $option->set("value",$language->name);
            $option->set_item("data","imagesrc",$language->image_url);
            $language_list[$language->code]=$option;
        }
		if ($_SESSION['user']->ecommerce) $results[]="<div id='menu_cart'>" . fn_href("<img src='/scs_images/cart.png' id=menu_cart_img> <span id=menu_cart_size>" . sizeof($this->cart) . "</span> Items",$this->page->cart->url) . "</div>";
		$results[]=$forms->select('langOption',$language_list,$_SESSION['user']->language_code,FALSE,array("id"=>"languageSelect"));
	    $results[]=$this->div();

		switch (TRUE) {
	      case (!$_SESSION['user']->id):
	      case ($_SESSION['user']->status != "Active"):
          	$results[]=$this->div("text");
	        $results[]="<img src='images/warning.png' />";
	        $results[]="<span class='editable'>You're not viewing the full website!";
            if (!$_SESSION['user']->id) $results[]="<a href='login.php'>Log in</a> to see more.</span>";
			$results[]=$this->div();
            break;
		  default:
			$results[]=$this->div("text");
            $results[]="Engineering Productivity Solutions &#0153;";
			$results[]=$this->div();
		}
	    $results[]=$this->div();
        $results[]=$this->div("bottom");
		switch (TRUE) {
	      case (!$_SESSION['user']->id):
	      case ($_SESSION['user']->status != "Active"):
			if (!$_SESSION['user']->id) $results[]="<a class='red-button' href='/login.php'>Log In</a>";
	        $results[]="<nav>";
	        $results[]="<ul id='nav'>";
			$results[]="<li>" . fn_href(strtoupper($this->language("menu_home")),"/") . "</li>";
            $results[]="<li>" . fn_href(strtoupper($this->language("menu_contact_us")),"/contact.php") . "</li>";
            $results[]=$this->ribbon("solutions");
	        $results[]="</ul>";
	        $results[]="</nav>";
			break;
		  default:
	        $results[]="<nav>";
	        $results[]="<ul id='nav'>";
	        if (!isset($this->page->dashboard)) $results[]="<li>" . fn_href(strtoupper($this->language("menu_home")),"/") . "</li>";

            $results[]=$this->ribbon("products");


            $results[]=$this->ribbon("solutions");
            $results[]="<li>" . fn_href(strtoupper($this->language("menu_contact_us")),"/contact.php") . "</li>";
            if (isset($this->page->dashboard)) $results[]="<li>" . fn_href(strtoupper($this->page->dashboard->name),$this->page->dashboard->url) . "</li>";
            $results[]="</ul>";
            $results[]="</nav>";
		}
		switch (TRUE) {
	      case (!$_SESSION['user']->id):
	      case ($_SESSION['user']->status != "Active"):
	      case (!$_SESSION['user']->catalog):
			break;
		  default:
	        $results[]=$forms->open(array("name"=>"productSearch","url"=>"/products.php?action=search","method"=>"get"));
//	        $results[]=$forms->hidden("action","search");
	        $results[]=$forms->hidden("search_source","term");
	        $results[]="<input type='text' name='search_code' id='ajax_inventory_search' placeholder='Product Search'/>";
	        $results[]="<button type='submit'><img src='/scs_images/red-magnifier.png' title='Product Search'></button>";
	        $results[]=$forms->close();
		}
	    $results[]=$this->div();
	    $results[]=$this->div();
	    $results[]=$this->div();
	    $results[]=$this->div();
	    $results[]=$this->div();
	    $results[]=$this->div("top_buffer");
	    $results[]=$this->div();
		if (strlen($database->registry->motd->message)) {
        	$options=array();
            $options["class"]="motd";
            $options['style']="background-color: #" . $database->registry->motd->background . "; color: #" . $database->registry->motd->foreground . ";";
            $results[]=$forms->div($database->registry->motd->message,$options);
		}
        switch (TRUE) {
          case ($this->options['products_div']):
			$results[]=$this->div("products_div",TRUE);
            break;
          case ($this->options['scs_div']):
			$results[]=$this->div("scs-wrapper",TRUE);
            if ($this->options['outerContainer_div']) $results[]="<div class='outerContainer'>";
			$results[]=$this->div("scs_content_div");
		}
		print implode("\n",$results);
    }

?>