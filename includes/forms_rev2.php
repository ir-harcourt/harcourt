<?php
/*
COPYRIGHT NOTICE:
Copyright 1981 - 2022 Suburban Computer Services, Inc All Rights Reserved.

This program is furnished under a license restricing its use solely for the operation
of a designated computer for a particular  purpose, and may  not be copied, reproduced
disclosed,  or otherwise  used without the prior written, consent of Suburban Computer
Services, Inc.  Title to and ownership of the program shall at all times remain the
property of Suburban Computer Services.
*/
$forms=new forms_class();
class forms_class {
    var $message=array();
	var $report=array();
	var $error=array();
    var $error_text=array();
    var $error_class;
    var $registry_error=array();
	var $event_list=array("onfocus","onfocusout","onchange","onclick","onmouseover","onmouseout","onload","onblur","onsubmit");
    var $name_list=array("name","id","class","style");
    var $boolean_list=array("checked","selected","disabled","readonly");
    var $value_list=array("value","height","width","size","rows","cols","maxlength","scrolling","title","align","border","target","pattern","accept","max-height","max-width");
    var $manual_list=array("manual");
    var $data_list=array("data");
    var $html5_list=array("placeholder");
    var $depricated_list=array("alt"=>"title");
    var $background;
    var $foreground;
    var $date_ok;
    var $scs_tof=FALSE;
    var $open_default=array();
    var $headers=array();
    var $jquery=array();
    var $fields=array();
    var $title_constant;
    var $cron=0;
    var $debug=FALSE;
    var $constant;
    var $html;
    var $cms;
    function scs_version() {
        $results['11/04/2024']="Improve text class";
    	$results['01/08/2024']="Add checkbox_delete";
    	$results['08/06/2023']="Add max-width, max-weight";
    	$results['03/29/2023']="Add error_style, motd";
    	$results['03/02/2023']="Add file:accept";
    	$results['08/02/2022']="Add data fields";
    	$results['09/22/2021']="Add error_class";
    	$results['09/21/2020']=array("Add function headers","Replace frameBorder with border-width");
    	$results['06/22/2020']="Add message_menu";
    	$results['04/25/2020']="Correct radio compare bug";
    	$results['07/18/2019']="Add cron_task";
        $results['03/05/2019']="Improve img function";
        $results['01/14/2018']="Add initial script";
        $results['12/20/2018']="Add version";
        $results['11/30/2018']="Add hidden_fields";
        $results['03/20/2018']="Add fields";
        $results['02/16/2018']=array("Initial release (rev 2)","Based on rev1:12/25/2017");
        $results['file']=__FILE__;
        return $results;
    }
    function __construct() {
    global $database;
//http://www.w3schools.com/html/html_colornames.asp
		$version=preg_split("/\./",phpversion());
        $this->version=$version[0] + ( .1 * $version[1] );
		$this->background=new stdClass();
		$this->foreground=new stdClass();
        $this->border=new stdClass();
		$this->constant=new stdClass();
		$this->cms=new stdClass();
        $this->cms=new cms_meta_class();
		$color_list=array(
			"aliceblue"=>"#f0f8ff","antiquewhite"=>"#faebd7","aqua"=>"#00ffff","aquamarine"=>"#7fffd4","azure"=>"#f0ffff",
            "beige"=>"#f5f5dc","bisque"=>"#ffe4c4","black"=>"#000000","blanchedalmond"=>"#ffebcd","blue"=>"#0000ff",
            "blueviolet"=>"#8a2be2","brown"=>"#a52a2a","burlywood"=>"#deb887","cadetblue"=>"#5f9ea0","chartreuse"=>"#7fff00",
            "chocolate"=>"#d2691e","coral"=>"#ff7f50","cornflowerblue"=>"#6495ed","cornsilk"=>"#fff8dc","crimson"=>"#dc143c",
            "cyan"=>"#00ffff","darkblue"=>"#00008b","darkcyan"=>"#008b8b","darkgoldenrod"=>"#b8860b","darkgray"=>"#a9a9a9",
            "darkgrey"=>"#a9a9a9","darkgreen"=>"#006400","darkkhaki"=>"#bdb76b","darkmagenta"=>"#8b008b","darkolivegreen"=>"#556b2f",
            "darkorange"=>"#ff8c00","darkorchid"=>"#9932cc","darkred"=>"#8b0000","darksalmon"=>"#e9967a","darkseagreen"=>"#8fbc8f",
            "darkslateblue"=>"#483d8b","darkslategray"=>"#2f4f4f","darkslategrey"=>"#2f4f4f","darkturquoise"=>"#00ced1","darkviolet"=>"#9400d3",
            "deeppink"=>"#ff1493","deepskyblue"=>"#00bfff","dimgray"=>"#696969","dimgrey"=>"#696969","dodgerblue"=>"#1e90ff",
            "firebrick"=>"#b22222","floralwhite"=>"#fffaf0","forestgreen"=>"#228b22","fuchsia"=>"#ff00ff","gainsboro"=>"#dcdcdc",
            "ghostwhite"=>"#f8f8ff","gold"=>"#ffd700","goldenrod"=>"#daa520","gray"=>"#808080","grey"=>"#808080","green"=>"#008000",
            "greenyellow"=>"#adff2f","honeydew"=>"#f0fff0","hotpink"=>"#ff69b4","indianred "=>"#cd5c5c","indigo "=>"#4b0082",
            "ivory"=>"#fffff0","khaki"=>"#f0e68c","lavender"=>"#e6e6fa","lavenderblush"=>"#fff0f5","lawngreen"=>"#7cfc00",
            "lemonchiffon"=>"#fffacd","lightblue"=>"#add8e6","lightcoral"=>"#f08080","lightcyan"=>"#e0ffff","lightgoldenrodyellow"=>"#fafad2",
            "lightgray"=>"#d3d3d3","lightgrey"=>"#d3d3d3","lightgreen"=>"#90ee90","lightpink"=>"#ffb6c1","lightsalmon"=>"#ffa07a",
            "lightseagreen"=>"#20b2aa","lightskyblue"=>"#87cefa","lightslategray"=>"#778899","lightslategrey"=>"#778899",
            "lightsteelblue"=>"#b0c4de","lightyellow"=>"#ffffe0","lime"=>"#00ff00","limegreen"=>"#32cd32","linen"=>"#faf0e6",
            "magenta"=>"#ff00ff","maroon"=>"#800000","mediumaquamarine"=>"#66cdaa","mediumblue"=>"#0000cd","mediumorchid"=>"#ba55d3",
            "mediumpurple"=>"#9370db","mediumseagreen"=>"#3cb371","mediumslateblue"=>"#7b68ee","mediumspringgreen"=>"#00fa9a",
            "mediumturquoise"=>"#48d1cc","mediumvioletred"=>"#c71585","midnightblue"=>"#191970","mintcream"=>"#f5fffa",
            "mistyrose"=>"#ffe4e1","moccasin"=>"#ffe4b5","navajowhite"=>"#ffdead","navy"=>"#000080","oldlace"=>"#fdf5e6","olive"=>"#808000",
            "olivedrab"=>"#6b8e23","orange"=>"#ffa500","orangered"=>"#ff4500","orchid"=>"#da70d6","palegoldenrod"=>"#eee8aa",
            "palegreen"=>"#98fb98","paleturquoise"=>"#afeeee","palevioletred"=>"#db7093","papayawhip"=>"#ffefd5","peachpuff"=>"#ffdab9",
            "peru"=>"#cd853f","pink"=>"#ffc0cb","plum"=>"#dda0dd","powderblue"=>"#b0e0e6","purple"=>"#800080","red"=>"#ff0000",
            "rosybrown"=>"#bc8f8f","royalblue"=>"#4169e1","saddlebrown"=>"#8b4513","salmon"=>"#fa8072","sandybrown"=>"#f4a460",
            "seagreen"=>"#2e8b57","seashell"=>"#fff5ee","sienna"=>"#a0522d","silver"=>"#c0c0c0","skyblue"=>"#87ceeb","slateblue"=>"#6a5acd",
            "slategray"=>"#708090","slategrey"=>"#708090","snow"=>"#fffafa","springgreen"=>"#00ff7f","steelblue"=>"#4682b4","tan"=>"#d2b48c",
            "teal"=>"#008080","thistle"=>"#d8bfd8","tomato"=>"#ff6347","turquoise"=>"#40e0d0","violet"=>"#ee82ee","wheat"=>"#f5deb3",
            "white"=>"#ffffff","whitesmoke"=>"#f5f5f5","yellow"=>"#ffff00","yellowgreen"=>"#9acd32");

		$color_list['greenbar']="#8fbc8f"; // depricated, use darkseagreen
	    foreach ($color_list as $color_name => $color_hex) {
			$this->background->{$color_name}="style='background-color: $color_hex;'";
			$this->foreground->{$color_name}="style='color: $color_hex;'";
			$this->border->{$color_name}="style='border-color: $color_hex;'";
	    }
        $this->month=array("01"=>"Jaunary","02"=>"February","03"=>"March","04"=>"April","05"=>"May","06"=>"June","07"=>"July","08"=>"August","09"=>"September","10"=>"October","11"=>"November","12"=>"December");
        $this->error_style="style='border: 1px solid red !important;'";
        $this->html=new forms_html_class();
	}
	function message($debug=array()) {
		if ($this->scs_tof) print fn_name("scs_tof");
    	if (is_array($debug)) {
	        foreach ($debug as $key => $text) {
	            $this->message[]="<b>Key:</b> $key <b>Text:</b> $text";
	        }
    	}
	    if (sizeof($this->error)) $this->message[]="Correct " . sizeof($this->error) . " error(s)";
        if (!sizeof($this->message)) return;
		print "<p class='message left'>";
        $first_pass=TRUE;
	    foreach ($this->message as $text) {
        	if ($first_pass) {
              	print $this->title_constant . $text;
                $first_pass=FALSE;
                if (sizeof($this->message) > 1) print "<span class=message2>";
			  } else {
             	print "<br>$text";
            }
        }
		if (sizeof($this->message) > 1) print "</span>";
		print "</p>";
        if ($this->message_menu) print $this->message_menu;
	}
    function motd() {
    global $database;
    	if (isset($database->registry->motd->text)) {
        	$text=(strlen($database->registry->motd->style)) ? "style='" . str_replace("\n", " ",$database->registry->motd->style) . "'" : "id=scscpq_motd";
        	print "<div {$text}>" . $database->registry->motd->text . "</div>";
		}
    }
    function error($field,$text="",$message="",$registry_module="") {
		if (!$field) return;
//		$this->error[$field]=$this->background->red;
		$this->error[$field]=$this->error_style;
        if ($text) $this->error_text[$field]=$text;
        if ($message) $this->message[]=$message;
        if ($registry_module) $this->registry_error[$registry_module]=TRUE;
    }
    function compare($compare,$text,$results="selected") {
		if ($this->debug) print "<!-- compare:{$compare} text:{$text} " . (!strcmp($compare,$text) ? " {$results}" : "") . " -->";
		return (!strcmp($compare,$text) ? " {$results}" : "");
    }
    private function escape($text,$type="",$quotes=FALSE) {
		list($type_code,$type_option)=( (strpos($type,":")) ? explode(":",$type) : array($type,"") );
	    $type_code=strtolower($type_code);
	    switch (TRUE) {
	      case ($type_code=="date"):
	        if (!$type_option) $type_option="ymd";
	        $value=fn_date($text,$type_option);
	        break;
          case ($type_code=="period"):
	        if (!$type_option) $type_option="yyyymm";
	        $value=fn_period($text,$type_option);
            break;
	      case ((in_array($type_code,array("float","decimal"))) && (!floatval($text))):
	        $value="";
	        break;
	      case ($type_code=="float"):
	        $value=floatval($text);
	        break;
	      case ( ($type_code=="decimal") && (floatval($text)) ):
	        $value=number_format($text,intval($type_option));
	        break;
	      case ( ($type_code=="percent") && (floatval($text)) ):
	        $value=number_format((100 * $text),intval($type_option));
	        break;
	      default:
	        $value=htmlspecialchars($text, ENT_QUOTES,"ISO8859-1");
	        break;
	    }
	    if ($quotes) {
	        return "'$value'";
	      } else {
	        return $value;
	    }
	}
    private function html5($html5,$ignore=array()) {
		$results=array();
        if (!in_array("data",$ignore)) {
			foreach ($html5->data as $key => $value) {
				$results[]="data-{$key}='$value'";
            }
		}
		if (sizeof($results)) return " " . implode(" ",$results);
    }
    function options($field,$options) {
		if (!is_array($options)) $options=array();
		if ($field) {
        	$options['name']=$field;
			if (!isset($options['id'])) $options['id']=$field;
		}
	    foreach ($options as $key=> $text) {
			if ((array_key_exists($key,$this->depricated_list)) &&
				(!array_key_exists($this->depricated_list[$key],$options)))
				$options[$this->depricated_list[$key]]=$text;
        }
		$results=array("name"=>"","src"=>"","value"=>"","event"=>"","boolean"=>"","html5"=>"","manual"=>"","data"=>"");
        if ( (array_key_exists($field,$this->error)) && (strlen($this->error_class)) ) {
        	if (array_key_exists("class",$options)) {
            	$options['class'] .= " " . $this->error_class;
			  } else {
            	$options['class']=$this->error_class;
			}
        }
	    foreach ($options as $key=> $text) {
			switch (TRUE) {
              case ( (is_string($text)) && (!strlen($text)) ):
              case ( (is_array($text)) && (!sizeof($text)) ):
			  case (array_key_exists($key,$this->depricated_list)):
              	break;
			  case (in_array($key,$this->data_list,TRUE)):
              	if (is_array($text)) {
                    foreach ($text as $text_key => $text_value) {
                    	$results['data'] .= " {$text_key}='{$text_value}'";
                    }
				  } else {
                	$results['data']=$text;
				}
            	break;
			  case (in_array($key,$this->manual_list,TRUE)):
				$results['manual'] .= " $text";
            	break;
			  case (in_array($key,$this->event_list,TRUE)):
	            $results['event'] .= " $key=\"javascript:$text\"";
	            break;
			  case (in_array($key,$this->name_list,TRUE)):
              	if (is_array($text)) $text=implode(" ",$text);
              	$results['name'] .= " $key='$text'";
                break;
			  case (in_array($key,$this->boolean_list,TRUE)):
              	$results['boolean'] .= " $key";
                break;
			  case (in_array($key,$this->html5_list,TRUE)):
                if ($this->html->html5) $results['html5'] .= " $key='$text'";
                break;
			  case ($key==="src"):
              	$results['src'] .= " $key='$text'";
                break;
			  case (in_array($key,$this->value_list,TRUE)):
              	$results['value'] .= " $key='$text'";
                break;
              default:
				scs_function_debug(__FILE__,__METHOD__,func_num_args(),func_get_args());
				print "Invalid forms key: $key value $text not found";
                die;
			}
	    }
        if ( (array_key_exists($field,$this->error)) && (!strlen($this->error_class)) ) $results['error']=" {$this->error[$field]}";
		return implode("",$results);
    }
    private function fields($field,$function,$value="") {
		switch (TRUE) {
          case (!strlen($field)):
          	return;
          case (array_key_exists($field,$this->fields)):
          	$this->fields[$field]->multiple=TRUE;
          	break;
		  default:
          	$item=new stdClass();
            $item->function=$function;
            $item->value=$value;
          	$this->fields[$field]=$item;
		}
    }
    function field($field) {
    	return array_key_exists($field,$this->fields);
    }
    function hidden($field,$text="",$text_type="",$classname="") {
		if ($this->debug) scs_function_debug(__FILE__,__METHOD__,func_num_args(),func_get_args());
        $this->fields($field,__FUNCTION__,$text);
		$options=array();
        $options['name']=$field;
        $options['value']=$this->escape($text,$text_type);
        $options['class']=$classname;
		return "<input type=hidden " . trim($this->options($field,$options)) . ">\n";
    }
    function hidden_fields($data, $options=array()) {
		if ($this->debug) scs_function_debug(__FILE__,__METHOD__,func_num_args(),func_get_args());
        $results=array();
        foreach ($data as $field => $value) {
            switch (TRUE) {
              case ($this->field($field)):
              case (is_object($value)):
                break;
              case (is_array($value)):
                $results[]=$this->hidden($field, implode("\n", $value), $text_type, $options['class']);
                break;
              default:
                $results[]=$this->hidden($field, $value, $text_type, $options['class']);
            }
		}
        if (sizeof($results)) return implode("",$results);
    }
    function html5_period($field, $text, $options=array()) {
		if ($this->debug) scs_function_debug(__FILE__,__METHOD__,func_num_args(),func_get_args());
        $this->fields($field,__FUNCTION__,$text);
        return $this->text($field, str_replace("/", "-", $text), 7, 7, "month", $options);
    }
    function text($field, $text, $size, $maxlength="", $type="", $options=array()) {
        global $database;
		if ($this->debug) scs_function_debug(__FILE__,__METHOD__,func_num_args(),func_get_args());
        $this->fields($field,__FUNCTION__,$text);
		switch (TRUE) {
          case (!isset($database->registry->www->jquery)):
          	break;
		  case (in_array($type,array("date","date:ymd"))):
            $classname="datepicker";
          	break;
        }
        switch (TRUE) {
          case (!strlen($classname)):
            break;
          case (!array_key_exists('class',$options)):
            $options['class']=$classname;
            break;
          default:
            $class=(is_array($options['class'])) ? $options['class'] : array($options['class']);
            $class[]=$classname;
            $options['class']=$class;
        }
        $form_type=(in_array($type,array("password","email","number","url","tel","month"))) ? $type : "text";
        $options['size']=$size;
        if ($maxlength) $options['maxlength']=$maxlength;
		$options['value']=$this->escape($text,$type);
		return "<input type=$form_type " . trim($this->options($field,$options)) . ">\n";
    }
    function textarea($field,$data,$rows,$cols,$options=array()) {
		if ($this->debug) scs_function_debug(__FILE__,__METHOD__,func_num_args(),func_get_args());
        $this->fields($field,__FUNCTION__,$data);
        if ($cols) {
        	$options['cols']=$cols;
		  } else {
        	$options['cols']=60;
		}
		if (is_array($data)) {
            $min_rows=sizeof($data);
			$text=implode("\n",$data);
		  } else {
			$min_rows=(strlen($data) / $cols);
            $text=$data;
		}
		$options['rows']=$min_rows + 3;
		switch (TRUE) {
		  case (!$rows):
			break;
          case ($min_rows < $rows):
			$options['rows']=$rows;
          	break;
          case (($min_rows + 3) > 30):
			$options['rows']=30;
          	break;
		}
		return "<textarea" . $this->options($field,$options) . ">" . ( ($this->version >= 5.4) ? htmlspecialchars($text,ENT_HTML401) : $text) . "</textarea>\n";
    }
    function file($field,$size=40,$options=array()) {
		if ($this->debug) scs_function_debug(__FILE__,__METHOD__,func_num_args(),func_get_args());
        $this->fields($field,__FUNCTION__);
		$options['size']=$size;
/*
"image/*"
"application/pdf"
"application/vnd.ms-excel"
"text/plain"
"*.txt"
"*.csv"
"image/jpg,image/png,image/jpeg,image/gif'
*/
		return "<input type=file " . trim($this->options($field,$options)) . ">\n";
    }
    function select($field,$data,$compare,$blank=TRUE,$forms_options=array(),$remap=1) {
		if ($this->debug) scs_function_debug(__FILE__,__METHOD__,func_num_args(),func_get_args());
        $this->fields($field,__FUNCTION__,$data);
		if (!is_array($data)) return;
        $results=array();
		$results[]="<select" . $this->options($field,$forms_options) . ">";
        switch (TRUE) {
          case ($blank==1):
			$results[]="<option></option>";
            break;
          case ($blank):
			$results[]="<option value=''>$blank</option>";
            break;
		}
        $key_value=TRUE;
        for ($i=0; $i < sizeof($data); $i++) {
        	if (!isset($data[$i])) {
            	$key_value=FALSE;
                break;
            }
        }
		foreach ($data as $key => $item) {
	        if (is_object($item)) {
				$text=$item->value;
                $html5=$item;
			  } else {
				$text=$item;
                $html5=new forms_html5_class();
	        }
			switch (TRUE) {
              case (($key_value) && ($remap == 1)):
              case ($remap == "list"):
				$key=$text;
                break;
			}
			$results[]="<option value=" . $this->escape($key,"",TRUE) . $this->html5($html5,array("value")) . $this->compare($key, $compare) . ">$text</option>";
	    }
	    $results[]="</select>";
        $results[]="";
        return implode("\n",$results);
    }
    function optgroup($field,$data,$compare,$blank=TRUE,$options=array(),$optgroup_options=array()) {
		if ($this->debug) scs_function_debug(__FILE__,__METHOD__,func_num_args(),func_get_args());
        $this->fields($field,__FUNCTION__,$data);
		if (!is_array($data)) return;
        $results=array();
        $results[]="<select" . $this->options($field,$options) . ">";
        if ($blank) {
			if (strlen($blank) > 1) {
	            $results[]="<option>$blank</option>";
			  } else {
	            $results[]="<option></option>";
            }
		}
		foreach ($data as $group_name => $group) {
        	if ((!is_array($group)) || (!sizeof($group))) continue;
			$results[]="<optgroup label='" . htmlentities($group_name) . "'>";
	        foreach ($group as $key => $text) {
	            $results[]="<option value=" . $this->escape($key,"",TRUE) . $this->compare($key, $compare) . ">$text</option>";
	        }
			$results[]="</optgroup>";
        }
	    $results[]="</select>";
        return implode("\n",$results);
    }
	function select_number($field,$compare,$first,$last,$options=array()) {
		if ($this->debug) scs_function_debug(__FILE__,__METHOD__,func_num_args(),func_get_args());
        $this->fields($field,__FUNCTION__,$compare);
    	if ($first > $last) return;
	    $data=range($first,$last);
	    return $this->select($field,$data,$compare,FALSE,$options);
	}
    function checkbox($field,$text,$compare,$options=array()) {
		if ($this->debug) scs_function_debug(__FILE__,__METHOD__,func_num_args(),func_get_args());
        $this->fields($field,__FUNCTION__,$text);
		if (!isset($options['checked'])) $options['checked']=$this->compare($compare,$text,"checked");
        $options['value']=$this->escape($text);
		$text="<input type=checkbox " . trim($this->options($field,$options)) . ">";
        if (array_key_exists($field,$this->error)) {
			return $this->span($text,array("style"=>"background-color: #ff0000"));
		  } else {
          	return $text;
        }
    }
    function checkbox_delete($id, $name, $options=array(), $script="fn_action") {
        $options['onclick']="{$script}('delete'," . fn_escape($id) . "," . fn_escape($name) . ");";
        return $this->checkbox("delete{$id}", 1, 0, $options);
    }
    function radio($field,$text,$compare,$options=array()) {
		if ($this->debug) scs_function_debug(__FILE__,__METHOD__,func_num_args(),func_get_args());
        $this->fields($field,__FUNCTION__,$text);
        if (!array_key_exists("id",$options)) $options['id']="{$field}_{$text}";
		return "<input type=radio value='" . $this->escape($text) . "' " . trim($this->options($field,$options) . $this->compare($compare,$text,"checked")) . ">\n";
    }
    function button($text,$options=array()) {
		if ($this->debug) scs_function_debug(__FILE__,__METHOD__,func_num_args(),func_get_args());
        if (!array_key_exists("class",$options))  $options['class']="nav_button";
        $options['value']=$this->escape($text);
		return "<input type=button " . trim($this->options("",$options)) . ">\n";
    }
    function submit($text,$options=array()) {
		if ($this->debug) scs_function_debug(__FILE__,__METHOD__,func_num_args(),func_get_args());
        if (!array_key_exists("class",$options)) $options['class']="nav_button";
        $options['value']=$this->escape($text);
		return "<input type=submit " . trim($this->options("",$options)) . ">\n";
    }
    function iframe($field,$src,$options=array(),$resize=TRUE) {
		if ($this->debug) scs_function_debug(__FILE__,__METHOD__,func_num_args(),func_get_args());
        if ( ($resize) || (array_key_exists("resize",$options)) ) {
			$options['onload']="this.height=(this.contentWindow.document.body.scrollHeight + 30);";
            $options['height']="0px";
			$options['scrolling']="no";
        }
		unset($options["resize"]);
        if (array_key_exists("style",$options)) {
			$styles=preg_split("/;/",$options['style']);
            foreach ($styles as $style) {
				list($code,$value)=preg_split("/:/",$style);
				$code=strtolower(trim($code));
                $value=trim($value);
                if (substr($value,-1) == ";") $value=substr($value,0,-1);
                if ( ($code) && (strlen($value)) && (!array_key_exists($code,$options)) ) $options[$code]=$value;
            }
            unset($options['style']);
        }

        if (!array_key_exists('src',$options)) $options['src']=$src;
        if (!array_key_exists('height',$options)) $options['height']="100px";
        if (!array_key_exists('width',$options)) $options['width']="100%";
        if (!array_key_exists('border-width',$options)) $options['border-width']="0px";
        if (!array_key_exists('padding',$options)) $options['padding']="0px";
        if (!array_key_exists('scrolling',$options)) $options['scrolling']="auto";
        $forms_options=array();
        $style_options=array();
        foreach ($options as $key => $value) {
        	switch (TRUE) {
              case (preg_match("/frameborder/i",$key)):
                break;
              case (in_array($key,array("height","width","border-width","padding","scrolling"))):
				$style_options[]="{$key}: $value;";
				break;
              default:
              	$forms_options[$key]=$value;
            }
        }
        if (sizeof($style_options)) $forms_options['style']=implode(" ",$style_options);
		$url=parse_url($options['src']);
        if ( ($url['host']) && ($url['host'] != $_SERVER['HTTP_HOST']) ) $resize=FALSE;
		return "<iframe " . $this->options($field,$forms_options) . ">IFrame support required to view content</iframe>\n";
    }
    function img($src, $options=array(), $img_options=array()) {
		if ($this->debug) scs_function_debug(__FILE__,__METHOD__,func_num_args(),func_get_args());
        if (!is_array($options)) $options=array();
        if (!is_array($img_options)) $img_options=array();
		switch (TRUE) {
		  case (!$src):
          case (preg_match("/^[http|https]/i",$src)):
			break;
		  default:
			if (substr($src,0,1) != "/") $src="/" . $src;
            if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $src)) return;
		}

        $url_options=array();
        foreach ($img_options as $key => $value) {
			switch (TRUE) {
              case (!strlen($value)):
              case ($key=="resize"):
				break;
			  case ($key=="content"):
				$url_options['code']=$value;
                break;
			  default:
				$url_options[$key]=$value;
			}
        }
		if (!array_key_exists("code",$url_options)) $url_options['code']=$src;
        foreach ($options as $key => $value) {
			if ( (in_array($key,array("width","height"))) && (strlen($value)) ) $url_options[$key]=$value;
        }
		switch (TRUE) {
		  case (array_key_exists('content_id',$img_options)):
 			$options['src']=fn_url("/scs_file.php",$img_options);
            break;
		  case (array_key_exists('content',$img_options)):
 			$options['src']=$this->img_url($url_options,TRUE);
            break;
		  case ( (!preg_match("/^[http|https]/i",$src)) && (array_key_exists('resize',$img_options)) ):
 			$options['src']=$this->img_url($url_options);
            break;
		  default:
			$options['src']=$src;
		}
        if (!$options['src']) return;
        if (!isset($options['border'])) $options['border']=0;
		if (array_key_exists("submit",$img_options)) {
			return "<input type=image " . trim($this->options($field,$options)) . ">";
		  } else {
			return "<img " . trim($this->options($field,$options)) . ">";
		}
    }
    function img_url($img_options,$content=FALSE) {
		switch (TRUE) {
          case  (!$img_options['code']): // added 09/16/2015
			return;
		  case ($content):
			return fn_url("/scs_content.php",$img_options);
          case (preg_match("/\$http:/i",$img_options['code'])):
			return $img_options['code'];
		  default:
			return fn_url("/scs_file.php",$img_options);
		}
    }
    function font($color,$text1,$text2="") {
		return "<font color=$color>$text1</font>$text2";
    }
    function caption($caption="",$options=array()) {
		switch (TRUE) {
		  case (is_array($caption)):
			$text=implode("<br>",$caption);
            break;
		  case (!strlen($caption)):
			$text="&nbsp;";
            break;
		  default:
			$text=trim($caption);
		}
		return "<caption " . trim($this->options("",$options)) . ">$text</caption>\n";
    }
    function span($text,$options=array()) {
		return "<span " . trim($this->options("",$options)) . ">" . $this->div_span($text) . "</span>\n";
    }
    function div($text,$options=array()) {
		return "<div " . trim($this->options("",$options)) . ">" . $this->div_span($text) . "</div>\n";
    }
    private function div_span($text) {
		if (is_array($text)) {
        	return implode("\n",$text);
		  } else {
			return trim($text);
		}
    }
    function open($forms_options=array(),$options=array()) {
		if ($this->debug) scs_function_debug(__FILE__,__METHOD__,func_num_args(),func_get_args());
        $this->fields=array();
		if (!is_array($forms_options)) $forms_options=array();
        if (sizeof($this->open_default)) {
        	foreach ($this->open_default as $default_name => $default_value) {
				if (!isset($forms_options[$default_name])) $forms_options[$default_name]=$default_value;
			}
		}
        if (($this->scs_tof) && (!isset($forms_options['bookmark']))) $forms_options['bookmark']="scs_tof";
		if (!isset($forms_options['name'])) $forms_options['name']="scs_form";
		if (!isset($forms_options['method'])) $forms_options['method']="post";
		if (!isset($forms_options['url'])) $forms_options['url']=$_SERVER['PHP_SELF'];
		if (!isset($forms_options['autocomplete'])) $forms_options['autocomplete']=TRUE;
        if (preg_match("/0|off/i",$forms_options['autocomplete'])) {
			$forms_options['autocomplete']="off";
		  } else {
			$forms_options['autocomplete']="on";
		}
		if (array_key_exists("bookmark",$forms_options)) $forms_options['url'] .= "#" . $forms_options['bookmark'];
		$results=array();
		$results[]=trim($this->options($forms_options['name'],$options));
	    $results[]="method=" . $forms_options['method'];
	    $results[]="action='" . $forms_options['url'] . "'";
		if (array_key_exists("target",$forms_options)) $results[]="target=" . $this->escape($forms_options['target']);
        $results[]="accept-charset='" . $this->html->charset . "'";
		if (array_key_exists("data",$forms_options)) $results[]="enctype='multipart/form-data'";
	    $results[]="autocomplete=" . $forms_options['autocomplete'];
		return "<form " . implode(" ",$results) . ">\n";
	}
    function close() {
        $this->fields=array();
		return "</form>";
    }
    function title($title, $version="") {
        global $database;
        if (strlen($this->html->title)) return;
        $text=array();
        if (strlen($database->registry->www->title_prefix)) $text[]=$database->registry->www->title_prefix;
        $text[]=$title;
		$this->html->meta("title",implode(" ", $text));
        $text=array($title);
        if (strlen($version)) $text[]="({$version})";
        $this->message[0]=implode(" ", $text);
    }
    function dow() {
		return array("Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday");
    }
/* Compressors
http://javascriptcompressor.com/
http://yui.github.io/yuicompressor/
*/
    function headers($field, $content) {
		$this->headers[$field]=$content;
    }
    function html_open($options=array()) {
    global $database;
		if ( (isset($database->registry->cms->frame)) &&  (in_array($database->registry->cms->frame,array("alpha","numeric"))) ) $this->cms->meta();
        $this->html->open=TRUE;

        switch (TRUE) {
		  case (!isset($database->registry->www->http_cache)):
          	break;
		  case ($database->registry->www->http_cache == "private"):
          	$this->headers("Cache-Control","private, max-age=" . (86400 * $database->registry->www->http_cache_days)); // 1 day
            break;
		  case ($database->registry->www->http_cache == "public"):
          	$this->headers("Cache-Control","max-age=" . (86400 * $database->registry->www->http_cache_days)); // 1 day
            break;
        }
        if ($database->registry->www->http_cache_days) {
        	$this->headers("Expires",date("r",strtotime("+" . $database->registry->www->http_cache_days . " days")));
        	$this->headers("Pragma","cache");
        }
		if (preg_match("/(trident|msie)/i",$_SERVER['HTTP_USER_AGENT'])) $this->headers("P3P",'CP="CAO PSA OUR"');
        foreach ($this->headers as $field => $content) {
			header("{$field}: $content");
        }
/*
        $headers=array();
        switch (TRUE) {
		  case (!isset($database->registry->www->http_cache)):
          	break;
		  case ($database->registry->www->http_cache == "private"):
	        $headers[]=header("Cache-Control: private, max-age=" . (86400 * $database->registry->www->http_cache_days)); // 1 day
            break;
		  case ($database->registry->www->http_cache == "public"):
	        $headers[]="Cache-Control: max-age=" . (86400 * $database->registry->www->http_cache_days); // 1 day
            break;
        }
        if ($database->registry->www->http_cache_days) {
	        $headers[]="Expires: " . date("r",strtotime("+" . $database->registry->www->http_cache_days . " days"));
	        $headers[]="Pragma: cache";
        }
		if (preg_match("/(trident|msie)/i",$_SERVER['HTTP_USER_AGENT'])) $headers[]='P3P: CP="CAO PSA OUR"';
        foreach ($headers as $header) {
			header($header);
        }
*/
        $data=array("html");
        foreach ($options as $key => $value) {
        	$data[]="{$key}='$value'";
        }
		if ($this->html->html5) {
			print "<!DOCTYPE html>\n";
//			print "<html lang='en-US'>\n";  recommended
		  } else {
			print "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">\n";
		}
        print "<" . implode(" ",$data) . ">\n";
	    print "<head>\n";
		if (!$this->html->title) $this->html->meta("title","Untitled Page");
        if (!$this->html->description) $this->html->meta("description",$this->html->title);
		if ($this->html->html5) {
        	$this->html->meta("charset",$this->html->charset);
          } else {
        	$this->html->meta("content-type","text/html; charset=" . $this->html->charset);
		}
        if ($this->html->icon) $this->html->meta("icon",$this->html->icon);
		if (sizeof($this->html->meta)) print implode("\n",$this->html->meta) . "\n";
		if (sizeof($this->html->css)) print implode("\n",$this->html->css) . "\n";
		if (sizeof($this->html->initial)) print implode("\n",$this->html->initial) . "\n";
		if (sizeof($this->html->js)) print implode("\n",$this->html->js) . "\n";
		if (sizeof($this->html->async)) print implode("\n",$this->html->async) . "\n";
		if (sizeof($this->html->javascript)) print implode("\n",$this->html->javascript) . "\n";
	    print "</head>\n";
	    print "<body";
        if (array_key_exists("body",$options)) print " " . $options['body'];
        print ">\n";
		if (isset($database->registry->www->js_body)) $this->html_js($database->registry->www->js_body);
    }
    function html_close() {
	global $database;
	    $database->disconnect();
        if ( (isset($_SESSION['cmsdebug'])) && (sizeof($this->cms->content)) ) {
        	foreach ($this->cms->content as $frame => $item) {
				if (!$item->displayed) {
            		print "<div>&nbsp;</div>\n";
					print "<div " . $this->background->yellow . ">Frame not used: <b>$frame</b></div>\n";
                }
            }
        }
		if (isset($database->registry->www->js_footer)) $this->html_js($database->registry->www->js_footer);
	    print "</body>\n";
	    print "</html>\n";
    }
    function html_js($scripts) {
	    foreach ($scripts as $item) {
	        $url=$_SERVER['DOCUMENT_ROOT'] . $item;
	        if (file_exists($url)) {
    			print "\n";
            	require_once($url);
    			print "\n";
			}
	    }
    }
    function cms_content($frame="") {
		return $this->cms->content($frame);
	}
    function html_load() {
    global $database;
		$options=array();
        $options['html5']=TRUE;
        $options['charset']="utf-8";
    	switch (TRUE) {
          case (!func_num_args()):
			break;
          case (is_array(func_get_arg(0))):
			foreach (func_get_arg(0) as $key => $value) {
            	if (array_key_exists($key,$options)) $options[$key]=$value;
            }
            break;
          default:
          	$options['html5']=func_get_arg(0);
        }
		$this->html->html5=$options['html5'];
		$this->html->charset=$options['charset'];
		if ( (isset($database->registry->cms->frame)) && (in_array($database->registry->cms->frame,array("alpha","numeric"))) ) $this->cms->load();
		$this->html->script("css",$database->registry->www->css);
		$this->html->script("js",$database->registry->www->js);
        if (isset($database->registry->www->meta)) {
        	foreach ($database->registry->www->meta as $key => $value) {
				$this->html->meta("registry:{$key}",$value,TRUE);
            }
		}
        if (isset($database->registry->www->icon)) $this->html->icon=$database->registry->www->icon;
    }
	function cron() {
	global $database;
	    if (is_array($database->registry->cron->url)) {
	        $matches=array();
	        foreach ($database->registry->cron->url as $item) {
	            $matches[]=preg_quote($item,"/");
	        }
	    }
	    switch (TRUE) {
          case (!array_key_exists("api_key",$_REQUEST)):
	      case (!property_exists($database->user->data,"api_key")):
	      case (!property_exists($database->registry,"cron")):
	      case (!$database->registry->cron->allowed):
	      case (!is_array($database->registry->cron->url)):
	      case (!preg_match("/" . implode("|",$matches) . "/i",$_SERVER['PHP_SELF'])):
	        unset($_REQUEST['api_key']);
            return;
	    }
	    $database->user->read($_REQUEST['api_key'],"api_key");
	    if (!$database->user->meta->rows) die("User access denied");
	    $this->cron=$database->user->data->id;
        $_SESSION['user']=$database->user->data;
	    return TRUE;
	}

/* The following are depricated functions and will be removed from the next release */
    function html_meta($text,$field="description") {
    	$this->html->meta($field,$text);
    }
    function html_load_script($type,$data=array(),$version="") {
		$this->html->script($type,$data,$version);
    }
/* End of depricated list */
}
class forms_html_class {
	var $open=FALSE;
	var $title="";
	var $description="";
    var $html5=TRUE;
    var $charset="utf-8";
	var $icon="";
	var $meta=array();
	var $css=array();
	var $js=array();
    var $initial=array();
    var $async=array();
	var $javascript=array();
    function __construct() {
		$this->meta["title"]="";
		$this->meta["description"]="";
    }
    function meta($field,$text,$raw=FALSE) {
		if (is_array($text)) {
        	$text=implode(" ",$text);
		  } else {
			$text=trim($text);
		}
		switch (TRUE) {
          case (!$field):
          	break;
          case (!strlen($text)):
			unset($this->meta[$field]);
            break;
          case ($field == "title"):
			$this->meta[$field]="<title>" . htmlspecialchars($text) . "</title>";
			break;
          case ($field == "icon"):
			$this->meta[$field]="<link rel='icon' href='" . $text . "' type='image/x-icon'>";
			break;
          case ($field == "charset"):
			$this->meta[$field]="<meta charset=\"" . $text . "\">";
          	break;
          case ($raw):
			$this->meta[$field]=$text;
            break;
          default:
			$this->meta[$field]="<meta name=\"" . $field . "\" content=\"" . htmlspecialchars(trim($text)) . "\">";
		}
        if (in_array($field,array("title","description"))) $this->{$field}=$text;
    }
    function script($type) {
    global $database;
		switch (TRUE) {
          case (func_num_args() < 2):
            return;
          case (is_array(func_get_arg(1))):
			$data=func_get_arg(1);
			break;
		  default:
			$data=array(func_get_arg(1));
        }
        if (in_array($type,array("javascript","initial"))) {
		    $content=array();
            if (preg_match("/^" . preg_quote("<script") . "/i",$data[0])) {
        		$this->{$type}[]=implode("\n",$data);
              } else {
        		$this->{$type}[]="<script>" . implode("\n",$data) . "</script>";
            }
            return;
		}
		foreach ($data as $script) {
			$script=trim($script);
            switch (TRUE) {
			  case (!in_array($type,array("css","js","async"))):
              case (!$script):
              case (array_key_exists($script,$this->{$type})):
              	break;
			  case ($type=="js"):
				$content=array();
                $content[]="type='text/javascript'";
                $content[]="src='" . trim($script) . "'";
                if ( (isset($database->registry->www->js_async)) && (preg_match("/^(http|https|ftp):/i",$script)) ) $content[]="async";
				$this->js[$script]="<script " . implode(" ",$content) . "></script>";
                break;
              case ($type=="async"):
				$content=array();
                $content[]="async";
                $content[]="src='" . trim($script) . "'";
				$this->async[$script]="<script " . implode(" ",$content) . "></script>";
              	break;
              case ( (isset($database->registry->www->css_embed)) && (!preg_match("/^(http|https|ftp):/i",$script)) ):
				if (file_exists($_SERVER['DOCUMENT_ROOT'] . $script)) {
	                $text=array();
	                $text[]="<style>";
	                $text[]=trim(file_get_contents($_SERVER['DOCUMENT_ROOT'] . $script));
	                $text[]="</style>";
	                $this->css[$script]=implode("\n",$text);
                }
                break;
			  default:
				$this->css[$script]="<link rel='stylesheet' type='text/css' href='{$script}'>";
			}
        }
	}
}
class forms_html5_class {
	var $value='';
    var $data=array();
    function __construct($data=array()) {
		foreach ($data as $code => $data) {
			$this->set($code,$data);
        }
    }
    function set($code,$data) {
        switch (TRUE) {
          case (!isset($this->{$code})):
			break;
          case ( (!is_array($this->{$code})) &&  (!is_array($data)) ):
          case ( (is_array($this->{$code})) &&  (is_array($data)) ):
			$this->{$code}=$data;
            break;
        }
    }
    function set_item($code,$key,$data) {
		switch (TRUE) {
          case (!isset($this->{$code})):
          case (!$code):
          case (!$key):
			break;
		  case (!strlen($data)):
			unset($this->{$code}[$key]);
            break;
		  default:
			$this->{$code}[$key]=$data;
		}
    }
}
class cms_meta_class {
    var $content=array();
    var $meta=array();
    var $meta_fields=array("title","description");
	function load() {
    global $database, $forms;
	require_once "classes/cmspage.php";
	require_once "classes/cmscontent.php";
		if (!$database->cmspage->load($_SERVER['PHP_SELF'])) return;
        $this->content=$database->cmscontent->load();
        foreach ($this->content as $page) {
	        $forms->html->script("css",$page->css);
        }
    }
    function meta_load($field,$text,$options=array()) {
		if (in_array($field,$this->meta_fields)) {
			$content=new stdClass();
            if (is_array($text)) {
				$content->text=implode(" ",$text);
			  } else {
				$content->text=$text;
			}
            $content->options=$options;
			$this->meta[$field]=$content;
        }
    }
    function meta() {
	global $database, $forms;
		foreach ($this->meta_fields as $code) {
			switch (TRUE) {
			  case (!array_key_exists($code,$this->meta)):
			  case (!$this->meta[$code]->text):
				$field="meta_{$code}";
				$text=$database->cmspage->data->$field;
                break;
			  default:
				$text=$this->meta[$code]->text;
            }
			if ( ($text) && (!$forms->html->meta[$code]) ) $forms->html->meta($code,$text);
        }
    }
    function content($frame="") {
		$results=array();
    	switch (TRUE) {
          case (!sizeof($this->content)):
          	break;
          case (!$frame):
			foreach ($this->content as $frame => $item) {
				$results[]=trim($this->output($frame));
			}
            break;
          case (!isset($this->content[$frame])):
          	break;
          default:
          	$results[]=trim($this->output($frame));
        }
		return implode("\n",$results);
    }
	function output($frame) {
		if ( (!isset($this->content[$frame])) || ($this->content[$frame]->displayed) ) return;
        $results=array();
        if  (isset($_SESSION['cmsdebug'])) {
            $meta=array();
        	switch (TRUE) {
              case ($this->content[$frame]->status != "Active"):
            	$bg=$this->background->silver;
                break;
			  default:
              	$bg=$this->background->darkseagreen;
            }
	        $results[]="<div id='cms_begin_" . $frame . "' $bg>";
            $results[]="<b>Begin CMS content</b>";
            $meta["Frame"]=$this->content[$frame]->frame;
            $meta["Record ID"]=$this->content[$frame]->id;
            if ($this->content[$frame]->title) $meta["Title"]=$this->content[$frame]->title;
            if ($this->content[$frame]->from_date) $meta["Start date"]=date("l F j, Y",strtotime($this->content[$frame]->from_date));
            if ($this->content[$frame]->thru_date) $meta["End date"]=date("l F j, Y",strtotime($this->content[$frame]->thru_date));
            $meta["Revised"]=date("l F j, Y g:i A",$this->content[$frame]->revised);
            foreach ($meta as $key => $value) {
            	$results[]="<br>$key: $value";
            }
	        $results[]="</div>";
		  } else {
			$results[]="<!-- Begin frame: $frame -->";
        }
        $results[]="<div id='cms_" . $frame . "'>";
        $results[]=$this->content[$frame]->html;
        $results[]="</div>";
        if (isset($_SESSION['cmsdebug'])) {
	        $results[]="<div id='cmsdate_end_" . $frame . "' $bg>";
            $results[]="<b>End CMS content</b>";
	        $results[]="</div>";
            $results[]="<div>&nbsp;</div>";
		  } else {
			$results[]="<!-- End frame: $frame -->";
        }
        $this->content[$frame]->displayed=TRUE;
        return implode("\n",$results);
	}
    function spoof() {
        if ($_SESSION['cmsdate']) return "<br><br>CMS spoofing date " . fn_href(fn_date($_SESSION['cmsdate'],"ymd"),"/scs_cmsspoof.php");
    }
}
?>