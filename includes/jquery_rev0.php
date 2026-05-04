<?php
class jquery_tab_class {
    var $output_order=array();
    var $contents=array();
    function __construct() {
    global $forms;
        $this->meta=new stdClass();
        $this->meta->options=array();
        $this->meta->content_options=array();
        $content_options=array();
		switch (TRUE) {
          case (!func_num_args()):
			$this->meta->version=1;
    		break;
          case (is_array(func_get_arg(0))):
            $this->meta->version=1;
			$this->meta->options=func_get_arg(0);
            if (func_num_args() > 1) $this->meta->content_options=func_get_arg(1);
            break;
          default:
            $this->meta->version=0;
            for ($i=0; $i < func_num_args(); $i++) {
            	switch ($i) {
                  case 0:
                    $this->meta->options['id']=func_get_arg($i);
                    break;
                  case 1:
                  	$this->meta->options['class']=func_get_arg($i);
                    break;
                  case 2:
                  	$this->meta->content_options['class']=func_get_arg($i);
                }
            }
        }
        if (!array_key_exists("id",$this->meta->options)) $this->meta->options['id']="scs_jquery_tabs";
        if (!array_key_exists("class",$this->meta->options)) $this->meta->options['class']="scs_jquery_tabs";
        if (!array_key_exists("class",$this->meta->content_options)) $this->meta->content_options['class']="scs_jquery_tabs";
        $this->meta->item=0;
		$this->meta->landing_tab=0;
		$this->meta->landing_name="";
		$this->meta->tabs=array();
        $this->meta->output_order=array();
		$this->landing_tab($_REQUEST);
        $this->options=new stdClass();
		$this->options->lineheight='content';
    }
    private function tab_name($name) {
		return "jquerytab_" . base64_encode(trim($name));
    }
    function options($options=array()) {
		foreach ($this->options as $option_code => $option_value) {
			if (array_key_exists($option_code,$options)) $this->options->{$option_code}=$options[$option_code];
        }
    }
    function output_order($id,$name) {
		$output_order=new StdClass();
        $output_order->name=$name;
        $this->meta->output_order[$id]=$output_order;
    }
    function landing_tab($request=array(),$override=FALSE) {
    global $forms;
		switch (TRUE) {
		  case (!func_num_args()):
			$this->meta->landing_tab=sizeof($this->meta->tabs);
			return;
		  case ( ($override) && (!is_array($request)) && (strlen($request)) ):
			if (!$this->landing_name) $this->landing_name=$this->tab_name($request);
			return;
		  case (!sizeof($forms->error)):
          case (!is_array($request)):
			return;
		}
		foreach ($request as $field => $field_value) {
			switch (TRUE) {
			  case (substr($field,0,10)=="jquerytab_"):
				$landing_name=$field;
				break;
        	  case (!isset($landing_name)):
				break;
			  case (array_key_exists($field,$forms->error)):
				$this->landing_name=$landing_name;
				return;
        	}
    	}
    }
    function item($name,$contents) {
    global $forms;
		if ($forms->debug) scs_function_debug(__FILE__,__METHOD__,func_num_args(),func_get_args());
		$options=array();
		$iframe_options=array();
		if (!$this->meta->version) {
			if ( (func_num_args() > 3) && (is_array(func_get_args(3))) ) $iframe_options=func_get_arg(3);
			if ( (func_num_args() > 2) && (is_string(func_get_args(2))) ) $iframe_options['url']=func_get_arg(2);
		  } else {
			if ( (func_num_args() > 2) && (is_array(func_get_args(2))) ) $options=func_get_arg(2);
			if ( (func_num_args() > 3) && (is_array(func_get_args(3))) ) $iframe_options=func_get_arg(3);
        }
        switch (TRUE) {
          case (!array_key_exists("id",$options)):
          case (!array_key_exists($options['id'],$this->meta->output_order)):
          	break;
          case (isset($this->meta->output_order[ $options['id'] ]->item)):
			return;
          default:
			$this->meta->output_order[ $options['id'] ]->item=$this->meta->item;
		}
		$this->meta->item++;
        $tab=new stdClass();
        $tab->name=$name;
        $tab->tab_name=$this->tab_name($name);
        if (array_key_exists("id",$options)) {
        	$tab->content_id=$options['id'];
           } else {
			$tab->content_id=$this->meta->options['id'] . "_" . $this->meta->item;
		}
        $tab->id="tab_" . $tab->content_id;
        $options=array();
        $options[]="a href=" . fn_escape("#" . $tab->content_id);
        $options[]="id=" . fn_escape("href_" . $tab->content_id);
        $options[]="class=" . fn_escape($this->meta->content_options['class']);
		switch (TRUE) {
		  case (array_key_exists("url",$iframe_options)):
          	$url=$iframe_options['url'];
            unset($iframe_options['url']);
			$tab->type="javascript";
            $iframe_options['src']="";
			if (!array_key_exists('resize',$iframe_options)) $iframe_options['resize']=TRUE;
			$options[]="onclick=\"fn_jquery_url(" . fn_escape($tab->id) . "," . fn_escape($url) . ");\"";
            if (!array_key_exists('style',$iframe_options)) $iframe_options['style']="padding:0px;";
			$this->contents[$this->meta->item]=$forms->iframe($tab->id,"",$iframe_options,FALSE);
          	break;
          case ( (is_array($contents)) && (!sizeof($contents)) ):
          case ( (!is_array($contents)) && (!strlen($contents)) ):
			return;
		  default:
			$tab->type="hyperlink";
	        $this->contents[$this->meta->item]=
				$forms->hidden($this->tab_name($name), $name) .
            	"<div id=" . fn_escape($tab->content_id) . ">\n";
			if (is_array($contents)) {
				$this->contents[$this->meta->item] .= implode("\n",$contents);
			  } else {
				$this->contents[$this->meta->item] .= trim($contents);
			}
			$this->contents[$this->meta->item] .= "\n</div>\n";
		}
        $tab->url="<" . implode(" ",$options) . ">$name</a>";
		$this->meta->tabs[]=$tab;
        if ( (!$this->meta->landing_tab) && ($tab->tab_name == $this->meta->landing_name) ) $this->meta->landing_tab=(sizeof($this->meta->tabs) - 1);
	}
    function output($debug=array()) {
    global $forms;
		if ($forms->debug) {
			$results=array();
            if (!is_array($debug)) $debug[]=$debug;
			if (sizeof($debug)) $results[]="<p class='standard'><b>Local Content:</b><br>\n" . implode("<br>\n",$debug) . "</p>";
            $tabs_content=array();
            $tabs_content[]="ID: " . $this->meta->options['id'];
            $tabs_content[]="Class: " . $this->meta->options['class'];
            $tabs_content[]="# Items: " . $this->meta->item;
            if ($this->landing_name) $tabs_content[]="Landing tab name: " . $this->landing_name;
			$results[]="<p class='standard'><b>Tabs Content:</b><br>\n" . implode("<br>\n",$tabs_content) . "</p>";
            foreach ($this->meta->tabs as $tab_item => $tab) {
				$tabs_content=array();
                foreach ($tab as $key => $value) {
                	$tabs_content[]="$key: $value";
                }
				$results[]="<p class='standard'><b>Tab #{$tab_item}:</b><br>\n" . implode("<br>\n",$tabs_content) . "</p>";
            }
            $this->item("SCS Debug",$results);
        }
		if (!sizeof($this->meta->tabs)) return;
        $output_order=array();
	    foreach ($this->meta->tabs as $id => $item) {
	        if ( (array_key_exists($item->name,$this->meta->output_order)) && (!isset($item->item)) ) $this->meta->output_order[$item->name]->item=$id;
	    }
        foreach ($this->meta->output_order as $item) {
			if (isset($item->item)) $output_order[]=$item->item;
        }
        for ($i=0; $i < $this->meta->item; $i++) {
			if (!in_array($i,$output_order)) $output_order[]=$i;
        }
        $results[]="<div " . trim($forms->options($this->meta->options['id'],$this->meta->options)) . ">";
        $results[]="<ul>";
        $landing_tab=0;
        foreach ($output_order as $key => $item) {
			$tab=$this->meta->tabs[$item];
			if ( ($this->meta->landing_tab) && ($item == $this->meta->landing_tab) ) $landing_tab=$key;
        	$results[]="<li id='" . $tab->id . "'>" . $tab->url . "</li>";
        }
        $results[]="</ul>";
		foreach ($this->contents as $contents) {
			if (is_array($contents)) {
            	$results[]=implode("\n",$contents);
			  } else {
        		$results[]=$contents;
			}
		}
        $results[]="</div>";
        $results[]="";
        $results[]="<script type='text/javascript'>";
        $results[]="$(document).ready(function() {";
        $results[]="var tabs=$('#" . $this->meta->options['id'] . "').tabs({heightStyle: '" . $this->options->lineheight . "'});";
        $results[]="$('#" . $this->meta->options['id'] . "').tabs( { active: $landing_tab } ) });";
        if (!sizeof($forms->jquery)) {
	        $results[]="var jquery_active_tabs = new Array();";
	        $results[]="function fn_jquery_url(id,url) {";
	        $results[]="if (jquery_active_tabs.indexOf(id) == -1) {";
	        $results[]="jquery_active_tabs.push(id);";
	        $results[]="document.getElementById(id).src=url;";
	        $results[]="}";
	        $results[]="}";
		}
        $forms->jquery[]=$this->meta->options['id'];
        $results[]="</script>";
        print implode("\n",$results);
	}
}

?>