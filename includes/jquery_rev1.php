<?php
class jquery_tab_class {
    var $output_order=array();
    var $contents=array();
	function scs_table_version() {
		$results=array();
        $results['04/15/2025']="Hide until complete";
        $results['02/25/2024']="Add options:landing_tab to item";
        $results['03/26/2023']="Add tab_focus, tab_visibility";
        $results['12/10/2022']="Add script type (see danfoss/index.php";
        $results['08/02/2022']="Add tab id";
        $results['02/12/2017']="2.0 release";
        $results['file']=__FILE__;
        return $results;
    }
    function __construct($name="") {
        global $forms;
        $this->meta=new stdClass();
		( (is_string($name)) && (strlen($name) ) ? $this->meta->name=$name : $this->meta->name="scs_jquery");
        $this->meta->options=new stdClass();
		$this->meta->options->div=array("id"=>$this->meta->name . "_div","class"=>$this->meta->name . "_div", "style"=>"display:none;");
        $this->meta->options->loader=array("id"=>$this->meta->name . "_loader","class"=>"{$this->meta->name}_loader");
		$this->meta->options->header=array("id"=>$this->meta->name . "_header","class"=>$this->meta->name . "_header");
		$this->meta->options->tabs=array("id"=>$this->meta->name . "_tabs","class"=>$this->meta->name . "_tabs");
		$this->meta->options->content=array("class"=>$this->meta->name . "_content");
		$this->meta->options->lineheight="content";
        $this->meta->item=0;
		$this->meta->landing_tab=0;
		$this->meta->landing_name="";
		$this->meta->tabs=array();
        $this->meta->output_order=array();
		$this->landing_tab($_REQUEST);
        $this->options=new stdClass();
    }

    function options($type,$options) {
    	switch (TRUE) {
          case (!strlen($type)):
          case (!isset($this->meta->options->$type)):
          	die("Invalid option type: $type");
          case (!is_array($options) ):
          	break;
          default:
          	foreach ($this->meta->options->$type as $key => $value) {
            	if ( (array_key_exists($key,$options)) && (strlen($options[$key])) )  $this->meta->options->{$type}[$key]=$options[$key];
            }
		}
    }
    private function tab_name($name) {
		return "jquerytab_" . base64_encode(trim($name));
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
    function item($name, $contents, $options=array(), $iframe_options=array()) {
        global $forms;
		if ($forms->debug) scs_function_debug(__FILE__,__METHOD__,func_num_args(),func_get_args());
        switch (TRUE) {
          case (!array_key_exists("id",$options)):
          case (!array_key_exists($options['id'],$this->meta->output_order)):
          	break;
          case (isset($this->meta->output_order[ $options['id'] ]->item)):
			return;
          default:
			$this->meta->output_order[ $options['id'] ]->item=$this->meta->item;
		}
        if ($options['landing_tab']) $this->landing_tab();
		$this->meta->item++;
        $tab=new stdClass();
        $tab->name=$name;
        $tab->tab_name=$this->tab_name($name);
        $tab->content_id=(array_key_exists("id",$options)) ? $options['id'] : $this->meta->options->header['id'] . "_" . $this->meta->item;
        $tab->classname=(array_key_exists("class",$options)) ? $options['class'] : $this->meta->options->content['class'];
        $tab->id="tab_" . $tab->content_id;
        $options=array();
        $options[]="a href=" . fn_escape("#" . $tab->content_id);
        $options[]="id=" . fn_escape("href_" . $tab->content_id);
        $options[]="class=" . fn_escape($this->meta->options->tabs['class']);
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
		  case (array_key_exists("script",$iframe_options)):
			$tab->type="javascript";
			if (!array_key_exists('resize',$iframe_options)) $iframe_options['resize']=TRUE;
			$options[]="onclick=\"" . $iframe_options['script'] . "\"";
            unset($iframe_options['script']);

            $text=array();
            $text[]=$forms->hidden($this->tab_name($name), $name);
			$text[]="<div id=" . fn_escape($tab->content_id) . " class='" . $tab->classname . "'>";
            $text[]=(is_array($contents)) ? implode("",$contents) : $contents;
			$text[]="</div>";
            $this->contents[$this->meta->item]=implode("",$text);
            break;
          case ( (is_array($contents)) && (!sizeof($contents)) ):
          case ( (!is_array($contents)) && (!strlen($contents)) ):
			return;
		  default:
			$tab->type="hyperlink";
	        $this->contents[$this->meta->item]=
				$forms->hidden($this->tab_name($name), $name) .
            	"<div id=" . fn_escape($tab->content_id) . " class='" . $tab->classname . "'>\n";
			if (is_array($contents)) {
				$this->contents[$this->meta->item] .= implode("\n",$contents);
			  } else {
				$this->contents[$this->meta->item] .= trim($contents);
			}
			$this->contents[$this->meta->item] .= "\n</div>\n";
		}
        $tab->url="<" . implode(" ",$options) . ">$name</a>";
		$this->meta->tabs[]=$tab;
        if ( (!$this->meta->landing_tab) && ($tab->tab_name == $this->landing_name) ) $this->meta->landing_tab=(sizeof($this->meta->tabs) - 1);
	}
    function output($debug=array()) {
        global $forms;
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
        foreach ($this->meta->tabs as $id => $obj) {
        	$results[]=$forms->hidden($obj->id . "_no", $id);
        }
        $results[]="<div " . trim($forms->options("",$this->meta->options->loader)) . "><span class=jquery_loader></span><span class=jquery_loader_text>Loading ...</span></div>";
        $results[]="<div " . trim($forms->options("",$this->meta->options->div)) . ">";
        $results[]="<div " . trim($forms->options("",$this->meta->options->header)) . ">";
        $results[]="<ul " . trim($forms->options("",$this->meta->options->tabs)) . ">";
        $landing_tab=0;
        foreach ($output_order as $key => $item) {
			$tab=$this->meta->tabs[$item];
			$results[]=$forms->hidden("tab_" . $tab->content_id . "_content",$item);
            if ( ($this->meta->landing_tab) && ($item == $this->meta->landing_tab) ) $landing_tab=$key;
            $li_options=array("id"=>$tab->id,"class"=>$this->meta->options->tabs['class']);
        	$results[]="<li " . trim($forms->options("",$li_options)) . ">" . $tab->url . "</li>";
        }
        $results[]="</ul>";
        $results[]="</div> <!-- " . $this->meta->options->header['id'] . " -->";
		foreach ($this->contents as $contents) {
			if (is_array($contents)) {
            	$results[]=implode("\n",$contents);
			  } else {
        		$results[]=$contents;
			}
		}
        $results[]="</div> <!-- " . $this->meta->options->header['div'] . " -->";
        $results[]="";
        $options=array();
        $options[]="heightStyle: " . fn_escape($this->meta->options->lineheight);
        $options[]="active: {$landing_tab}";

        $results[]="<style>";
        $results[]=".jquery_loader { display: inline-block; border: 4px solid #f3f3f3; border-radius: 50%; border-top: 4px solid #0981be; width: 20px; height: 20px;  -webkit-animation: spin 2s linear infinite; animation: spin 2s linear infinite; }";
        $results[]=".jquery_loader_text { display: inline-block; vertical-align: top; margin-left: 10px; margin-top: 5px; }";
        $results[]="</style>";

        $results[]="<script>";
        $results[]="var scscpq_tabs;";
        $results[]="jQuery(document).ready(function() {";
        $results[]="    var tabs=jQuery(\"#{$this->meta->options->loader['id']}\").hide();";
        $results[]="    var tabs=jQuery(\"#{$this->meta->options->div['id']}\").show();";
        $results[]="    var tabs=jQuery(\"#{$this->meta->options->div['id']}\").tabs( {" . implode(",",$options) . " } );";
        $results[]="    scscpq_tabs=tabs;";
		$results[]="});";
        $results[]="function tab_focus(name) {";
        $results[]="    var field='#tab_' + name + '_no';";
        $results[]="    if (jQuery(field).length) jQuery(\"#{{$this->meta->options->div['id']}\").tabs({ active: jQuery(field).val() });";
        $results[]="}";
        $results[]="function tab_visibility(visible, name) {";
        $results[]="    var field='#tab_' + name;";
        $results[]="    if (visible) {";
        $results[]="        jQuery(field).show();";
        $results[]="      } else {";
        $results[]="        jQuery(field).hide();";
        $results[]="    }";
        $results[]="}";
        if (!sizeof($forms->jquery)) {
	        $results[]="var jquery_active_tabs = new Array();";
	        $results[]="function fn_jquery_url(id,url) {";
	        $results[]="    if (jquery_active_tabs.indexOf(id) == -1) {";
	        $results[]="        jquery_active_tabs.push(id);";
	        $results[]="        document.getElementById(id).src=url;";
	        $results[]="    }";
	        $results[]="}";
		}
        $results[]="</script>";
        $forms->jquery[]=$this->meta->name;
        print implode("\n",$results);
	}
}

?>