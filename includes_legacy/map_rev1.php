<?php
require_once "ExcelWriterXML/ExcelWriterXML.php";
class map_class {
	var $table;
	var $group;
    var $code;
    var $external_file;
    var $internal_file="scs.import";
    var $temporary_file="scs.tmp";
    var $headers=FALSE;
    var $line_count=0;
    var $empty_count=0;
    var $update_count=0;
    var $reject_count=0;
    var $errors=array();
    var $message;
    var $update_results=array();
    var $function_list=array();
    var $file_type_list=array(".xls"=>"Excel (xls)",".txt"=>"Tab Delimited Text (txt)",".csv"=>"Comma Separated Values (csv)");
    var $delimiter;
    var $report;
    var $fhw;
    var $fhr;
    var $bom;
    var $xls_limit=150000;
    var $xls_page=0;
    var $debug=FALSE;
    var $forms_map=array();
    var $map=array();
    function scs_version() {
    	$results=array();
        $results['12/28/2017']=array("Add BOM (Byte Order Mark) for UTF-8","Strip UTF-8 headers from imported files");
        $results['06/04/2017']="Add pseudo table scs_database, scs_text";
        $results['01/13/2017']="Add period type";
        $results['03/10/2016']=array("Allow id mapping format","Improve XLS date export");
        $results['10/30/2015']="Allow mapping to field and name";
        $results['10/06/2015']="Function import_update not required";
 		$results['09/09/2015']=array("Add utf8 option","Add clear_html","Do not export hidden fields");
 		$results['08/22/2015']="Add options to export";
 		$results['04/16/2015']=array("Add XLS Limit","Improve export_html hidden fields");
 		$results['03/12/2015']="Add float input/output";
		$results['11/04/2014']="Trim upload_value results";
		$results['10/20/2014']="Return error if no records exported";
		$results['09/15/2014']=array("Initial 2.0 release","Integrate XLS","Fields group","Based on map_rev0.php");
        $results['file']=__FILE__;
        return $results;
    }
    function __construct($table,$report,$group="",$code="") {
    global $database;
		ob_start();
		$this->table=$table;
        $this->group=$group;
        $this->code=$code;
        switch (TRUE) {
          case (!$table):
          	return;
          case ($table=="scs_text"):
          	break;
          case (!in_array($table,array("scs_database","scs_text"))):
			require_once("classes/" . $table . ".php");
		}
        if (!method_exists($database->$table,"map")) die("Table: $table<br>Function not defined: map<br>(" . __FILE__ . ")");
        $this->function_list("download","WWW => Desktop");
        $this->function_list("template","WWW Template => Desktop");
        if (method_exists($database->$table,"import_update")) {
			$this->function_list("preview","Desktop => WWW (Preview only)");
			$this->function_list("upload","Desktop => WWW (Update)");
		}
        $database->$table->map($report);
        $database->$table->map->group=$group;
        $database->$table->map->code=$code;
    	$this->report=$report;
		$database->temp=new database_temp_class();
        if (!class_exists("xls_class")) unset($this->file_type_list[".xls"]);
        if (!array_key_exists("file_type",$this->report)) $this->report['file_type']=$_POST['file_type'];
        switch ($this->report['file_type']) {
		  case ".xls":
          	break;
           case ".csv":
            $this->delimiter=",";
          	break;
           default:
        	$this->report['file_type']=".txt";
            $this->delimiter="\t";
		}
        $table=$database->{$this->table};
		$this->fields_map();
    }
    function function_list($field, $name="") {
		if ($name) {
        	$this->function_list[$field]=$name;
		  } else {
          	unset($this->function_list[$field]);
		}
    }
    function forms_map($map=array()) {
		$this->forms_map=$map;
    }
    function fields_map() {
    global $database;
        $table=$database->{$this->table};
        foreach ($table->map->fields as $map_field => $map) {
			if (is_array($map)) {
				$this->map_group[$map_field]=FALSE;
				foreach ($map as $item_field => $item) {
					$this->fields_map_item($item,$item_field,$map_field);
                }
	          } else {
	            $this->fields_map_item($map,$map_field,$map_field);
			}
        }
    }
    function fields_map_item($item,$item_field,$map_field) {
		$this->map[$item_field]=$item;
        switch (TRUE) {
          case (!$_POST['option_selected']):
          case ($item->required):
          case ($_POST["map_" . $map_field]):
            $this->map[$item_field]->export=TRUE;
            break;
          default:
            $this->map[$item_field]->export=FALSE;
        }
        if ($item->hidden) $this->map[$item_field]->export=FALSE;
    }
/*
Options:
filename, date, time, timestamp, bom (tailor output file name)
*/
    function export($query="",$options=array(),$debug=FALSE) {
    global $database, $forms;
        $table=$database->{$this->table};
		$this->debug=$debug;
		if ($this->debug) $this->report['file_type']=".txt";
 		$this->xls_page=((array_key_exists("xls_page",$options)) ? $options['xls_page'] : intval($_POST['xls_page']));
        switch (TRUE) {
          case (!array_key_exists("bom",$options)):
          	break;
          case (strcasecmp($options['bom'],"UTF-8")=="0"):
			$this->bom=pack("CCC",0xef,0xbb,0xbf);
            break;
        }
		$field_count=0;
	    foreach ($table->map->fields as $field => $map) {
        	switch (TRUE) {
              case (!$map->export):
              	break;
              case (is_array($map)):
                foreach ($map as $item) {
                    $field_count++;
                }
				break;
              default:
                $field_count++;
            }
		}
        switch (TRUE) {
          case ($query):
			break;
		  case (method_exists($table,"export_query")):
        	$query=$table->export_query();
			break;
		  default:
			$query="select * from " . $this->table;
		}
		if ( ($this->report['file_type'] == ".xls") && ($this->xls_page) ) {
			$xls_limit=ceil($this->xls_limit / $field_count);
            $query .= "\n limit " . (($this->xls_page - 1) * $xls_limit) . ", $xls_limit";
		}
		$table->query($query);
		switch (TRUE) {
          case (!$table->meta->rows):
			$forms->error('null','',"No records found in selected range");
            return;
          case ( ($this->report['file_type'] == ".xls") && (($table->meta->found_rows * $field_count) > $this->xls_limit) ):
			if ($this->table == "scs_text") {
            	$table->meta->found_rows=intval($this->xls_limit / $field_count);
              } else {
	            $message=array();
	            $message[]=
	                "(" . number_format($field_count) . " columns * " . number_format($table->meta->found_rows) . " rows) =" .
	                number_format($field_count * $table->meta->found_rows) . " excedes memory limit of " . number_format($this->xls_limit) . " cells";
	            $message[]="Please break your export into " . ceil(($table->meta->found_rows * $field_count) / $this->xls_limit) . " files or use the txt/csv format";
	            $forms->error('null','',implode("<br>",$message));
	            return;
			}
        }
		$filename=array();
		if (array_key_exists("filename",$options)) {
        	$filename[]=$options['filename'];
		  } else {
	        $filename[]="{$this->table}";
	        if ($this->group) $filename[]=strtolower($this->group);
	        if ($this->code) $filename[]=strtolower($this->code);
		}
        if (array_key_exists("date",$options)) $filename[]=date("Ymd");
        if (array_key_exists("time",$options)) $filename[]=date("Hi");
        if (array_key_exists("timestamp",$options)) $filename[]=strtotime("now");
        if ($this->xls_page) $filename[]="page" . $this->xls_page;
		$this->external_file=implode("_",$filename) . $this->report['file_type'];
	    $this->update_results[]="Table: {$this->table}";
	    if ($this->group) $this->update_results[]="Group: {$this->group}";
	    if ($this->code) $this->update_results[]="Code: {$this->code}";
	    $this->update_results[]="File: " . $this->external_file;
		$this->update_results[]="Rows: " . number_format($table->meta->found_rows);
        if ( (!ob_get_contents()) && ($this->report['file_type'] == ".xls") ) {
			$this->export_xls();
		  } else {
			$this->export_txt();
		}
	    $table->free_result();
		if (method_exists($database->log,"ipc")) $database->log->ipc("Export Table: " . $this->table,$this->update_results);
		die;
    }
    function export_xls() {
    global $database;
        $table=$database->{$this->table};
        switch (TRUE) {
          case (($this->group) && ($this->code)):
			$subject = "Group: " . $this->group . " Code: " . $this->code;
            break;
          case ($this->group):
			$subject = "Group: " . $this->group . " (All)";
            break;
		  default:
			$subject="All records";
		}
		$xls=new xls_class($this->external_file,"Export Table: " . $this->table, $subject);
        $xls->worksheet("Data",$this->map);
		if ($this->table=="scs_text") {
        	for ($i=0; $i < $table->meta->found_rows; $i++) {
	            $table->fetch($i + 1);
	            $xls->row($this->export_data($table));
	        }
		  } else {
	        while ($table->fetch = $table->fetch_array() ) {
	            $table->fetch();
	            $xls->row($this->export_data($table));
			}
	    }
		$xls->write();
    }
    function export_txt() {
    global $database;
        $table=$database->{$this->table};
        if ( (!ob_get_contents()) && (!$this->debug) ) {
	        header("Content-type: text/plain");
	        header("Content-Disposition: attachment; filename=" . $this->external_file);
	        header("Pragma: no-cache");
	        header("Expires: 0");
		}
	    $fh = fopen("php://output", "w");
        if (!$this->delimiter) $this->delimiter="\t";
        if ($this->bom) fwrite($fh,$this->bom);
        fputcsv($fh,$this->export_header(),$this->delimiter,"\"");
		if ($this->table=="scs_text") {
        	for ($i=0; $i < $table->meta->found_rows; $i++) {
	            $table->fetch($i + 1);
	            fputcsv($fh,$this->export_data($table),$this->delimiter,"\"");
	        }
		  } else {
	        while ($table->fetch = $table->fetch_array()) {
	            $table->fetch();
	            fputcsv($fh,$this->export_data($table),$this->delimiter,"\"");
	        }
		}
	    fclose($fh);
    }
	function export_header() {
    	$results=array();
        foreach ($this->map as $map) {
			if ($map->export) $results[]=$map->name;
        }
        return $results;
    }
	function export_data($table) {
		$results=array();
        if (method_exists($table,"export_data")) $table->export_data();
        foreach ($this->map as $field => $map) {
			if (!$map->export) continue;
            switch (TRUE) {
              case ($map->external):
              	$data=$table->fetch[$map->external];
                break;
              case ($map->fetch):
              	$data=$table->fetch[$field];
                break;
              default:
				$data=$table->data->$field;
			}
	        switch (TRUE) {
			  case ($map->filter_export):
        	  	$func=$map->filter_export;
                $results[$field]=$func($data);
				break;
// Numeric
	          case ($map->type == "numeric"):
	            $results[$field]=(floatval($data));
                break;
	          case ($map->type == "decimal"):
	          case ($map->type == "percent"):
	            $results[$field]=number_format($data,$map->decimal,".","");
                break;
	          case ($map->type == "boolean"):
              	if ($data) {
	                $results[$field]="Y";
				  } else {
	                $results[$field]="N";
	            }
                break;

// Date & Time  (xls)
	          case ( ($this->report['file_type'] == ".xls") && ($map->type == "date") ):
				$results[$field]=strtotime($data);
                break;
	          case ( ($this->report['file_type'] == ".xls") && ($map->type == "time") ):
	          case ( ($this->report['file_type'] == ".xls") && ($map->type == "datetime") ):
	            $results[$field]=$data;
                break;
	          case ( ($this->report['file_type'] == ".xls") && ($map->type == "period") ):
	            $results[$field]=$data . "/01";
                break;

// Date & Time
	          case ($map->type == "date"):
				$results[$field]=fn_date($data,"ymd");
                break;
	          case ($map->type == "period"):
				$results[$field]=fn_date($data,"yymm");
                break;
	          case ($map->type == "time"):
	            $results[$field]=date("H:i:s",$data);
                break;
	          case ($map->type == "datetime"):
	            $results[$field]=date("m/d/Y H:i:s",$data);
                break;
// Alphanumeric
	          case ($map->type == "text"):
	            $results[$field]=str_ireplace("\r\n","<br>",$data);
                break;
	          default:
	            $results[$field]=$data;
	        }
        }
        return $results;
    }
    function hidden_html($fields=array()) {
    global $forms;
		$results=array();
	    foreach ($forms->report as $key => $value) {
			switch (TRUE) {
              case (is_array($value)):
				foreach ($value as $code) {
              		$results[]=$forms->hidden(base64_encode($key . "\t" . $code),1);
                }
				break;
			  case ($key != "action"):
              	$results[]=$forms->hidden($key,$value,$this->forms_map[$key]);
			}
	    }
        foreach ($fields as $key => $value) {
			$results[]=$forms->hidden($key,$value,$this->forms_map[$key]);
        }
        return implode("\n",$results);
    }
    function export_html($title,$count=0) {
    global $database, $forms;
        $table=$database->{$this->table};
        $results=array();
		$results[]=$this->hidden_html();
        $results[]=$forms->hidden("option_selected",1);
        $results[]="<p class=standard><b>$title</b>";
		$results[]="<br>Select all? " . $forms->checkbox("option_checked",1,1,array("onchange"=>"fn_checked(this.checked);")) . "</p>";
    	$results[]="<table class='standard border tablesorter'>";
        $results[]="<thead>";
        $results[]="<tr>";
        $results[]="<th>Include?</th>";
        $results[]="<th>Field name</th>";
        $results[]="<th>Type</th>";
        $results[]="<th>Comment</th>";
        $results[]="</tr>";
        $results[]="</thead>";
        $results[]="<tbody>";
        $field_count=0;
        foreach ($table->map->fields as $field => $map) {
			if ( (!is_array($map)) && ($map->hidden) ) continue;
			if (is_array($map)) {
				$map_required=FALSE;
                $map_name="Group: " . $field;
                $map_type="";
				if (!$map->required) $map_comment[]="All fields in group must be imported/exported";
                $rowspan=sizeof($map) + 1;
			  } else {
				$field_count++;
              	$map_name=$map->name;
				$map_required=$map->required;
                $map_type=$map->type;
                if (!is_null($map->decimal)) $map_type .=":" . $map->decimal;
                $map_name=$map->name;
                $map_comment=$map->comment;
                $rowspan=1;
			}
        	$field_name="map_" . $field;
            if ($map_required) {
            	$background=$forms->background->darkseagreen;
			  } else {
              	$background="";
			}
        	$results[]="<tr $background>";
            $results[]="<td class=center rowspan=$rowspan>";
            if ($map_required) {
            	$results[]="&nbsp;";
			  } else {
				$results[]=$forms->checkbox($field_name,1,1);
            }
            $results[]="</td>";
            $results[]="<td>" . $map_name . "</td>";
            $results[]="<td>" . $map_type . "</td>";
            $results[]="<td>" . implode("<br>\n",$map_comment) . "</td>";
            $results[]="</tr>";
			if (is_array($map)) {
				foreach ($map as $item) {
					$field_count++;
                	$results[]="<tr $background>";
                    $results[]="<td>" . $item->name . "</td>";
                    $item_type=$item->type;
					if (!is_null($item->decimal)) $item_type .=":" . $item->decimal;
                    $results[]="<td>" . $item_type . "</td>";
                    $results[]="<td>" . implode("<br>\n",$item->comment) . "</td>";
                    $results[]="</tr>";
                }
            }
        }
        $results[]="</tbody>";
        $results[]="</table>";
	    $xls_size=($field_count * $count);
	    $xls_excess=($xls_size / $this->xls_limit);
        $buttons=array("cancel"=>"","pages"=>"","update"=>"");
		$buttons['cancel']=$forms->button("Return",array("onclick"=>"fn_action('cancel');"));
        switch (TRUE) {
          case (!$count):
			$update_text="All records will be downloaded";
			break;
          case ( ($forms->report['file_type']==".xls") && ($xls_size > $this->xls_limit) ):
			$xls_page_count=ceil($xls_excess);
			$results[]=
            	"<p>Your export contains " . number_format($count) . " records. Selecting all fields will excede the memory limitiations of this program by <b>" . number_format(100 * $xls_excess) . "%</b>." .
                "<br>It is suggested that you only upload selected pages.</p>";
			$update_text="Download page 1 of {$xls_page_count}";
			$buttons['pages']=$forms->select_number("xls_page",1,0,$xls_page_count,array("onchange"=>"fn_xls_page(this);"));
            break;
		  default:
			$update_text="Download " . number_format($count) . " records";
		}
		$buttons['update']=$forms->button($update_text,array("onclick"=>"fn_action('update');","id"=>"scs_update_button"));
        $results[]="<p>" . implode(" ",$buttons) . "</p>";
	    $results[]="<script>";
	    $results[]="function fn_checked(checked) { ";
	    $results[]="  for(i=0; i< document.scs_form.elements.length; i++) {";
	    $results[]="    field=document.scs_form.elements[i].name;";
	    $results[]="    if (field.substring(0,4)=='map_') document.getElementById(field).checked=checked;";
		$results[]="  }";
	    $results[]="}";
	    $results[]="function fn_xls_page(obj) { ";
	    $results[]="  if (obj.value==0) {";
        $results[]="    document.getElementById('scs_update_button').value='Download " . number_format($count) . " records'";
        $results[]="  } else {";
        $results[]="    document.getElementById('scs_update_button').value='Download page ' + obj.value + ' of {$xls_page_count}'";
	    $results[]="  }";
	    $results[]="}";
	    $results[]="</script>";
		print implode("\n",$results);
    }
    function template() {
    global $database;
        $table=$database->{$this->table};
        $external_file=array();
        $external_file[]=$this->table;
        if ($this->group) $external_file[]="_" . strtolower($this->group);
		$external_file[]="_template" . $this->report['file_type'];
		$this->external_file=implode("",$external_file);
        if ( (!ob_get_contents()) && ($this->report['file_type'] == ".xls") ) {
			$this->template_xls();
		  } else {
			$this->template_txt();
		}
	    die;
    }
    function template_txt() {
	    header("Content-type: text/plain");
	    header("Content-Disposition: attachment; filename=" . $this->external_file);
	    header("Pragma: no-cache");
	    header("Expires: 0");
	    $fh = fopen("php://output", "w");
	    fputcsv($fh,$this->export_header(),$this->delimiter,"\"");
	    fclose($fh);
    }
    function template_xls() {
    global $database;
        $table=$database->{$this->table};
        switch (TRUE) {
          case (($this->group) && ($this->code)):
			$subject = "Group: " . $this->group . " Code: " . $this->code;
            break;
          case ($this->group):
			$subject = "Group: " . $this->group . " (All)";
            break;
		  default:
			$subject="All records";
		}
		$xls=new xls_class($this->external_file,"Export Table: " . $this->table, $subject);
        $xls->worksheet("Data",$this->map);
		$xls->write();
    }
    function upload_open($id="upload") {
    global $database,$forms;
		$table=$database->{$this->table};
        $this->external_file=$_FILES[$id]['name'];
		$forms->message[]="Upload file: <b>" . $this->external_file . "</b>";
		switch (TRUE) {
		  case (!$this->external_file):
		  case (!intval($_FILES[$id]['size'])):
			$forms->message[]="File mising or empty";
            return;
		  case ($_FILES[$id]['error']):
			$forms->message[]="Upload error #" . $_FILES[$id]['error'];
            return;
		  case (preg_match("/.csv$/i",$_FILES[$id]['name'])):
			$this->report['file_type']=".csv";
            $this->delimiter=",";
            break;
		  case (preg_match("/.txt$/i",$_FILES[$id]['name'])):
			$this->report['file_type']=".txt";
            $this->delimiter="\t";
            break;
		  default:
			$forms->message[]="File format must be tab delimited text (.txt) or Comma separated values (.csv)";
            return;
		}
		if (file_exists($this->temporary_file)) unlink($this->temporary_file);
        move_uploaded_file($_FILES[$id]['tmp_name'],$this->temporary_file);
		$this->fhr=fopen($this->temporary_file,"r");
        return 1;
    }
    function upload_close() {
        fclose($this->fhr);
		unlink($this->temporary_file);
    }
    function upload($id="upload") {
    global $database,$forms;
		if (!$this->upload_open($id)) return;
		if (file_exists($this->internal_file)) unlink($this->internal_file);
		$this->fhw=fopen($this->internal_file,"w");
        while ($line = fgets($this->fhr,4096)) {
			$items=str_getcsv(preg_replace("/^" . pack("H*","EFBBBF") . "/", "", $line),$this->delimiter,"\"");
            fputcsv($this->fhw,$this->upload_line($items),"\t","\"");
        }
        fclose($this->fhw);
		$this->upload_close();
        switch (TRUE) {
          case (sizeof($this->errors)):
			$this->message=implode("<br>",$this->errors);
            return;
          case (!$this->update_count):
			$this->message="No records accepted for import";
            return;
          case ($this->report['function'] != "upload"):
			$this->message="Import preview -> review";
        	return 1;
          default:
			$this->message="Import update -> review";
			return 1;
		}
    }
    function upload_line($data) {
    global $database;
        $table=$database->{$this->table};
        $results=array();
	    $empty_row=TRUE;
	    foreach($data as $key => $value) {
        	$data[$key]=trim($value);
	        if (strlen($data[$key])) $empty_row=FALSE;
	    }
		$this->line_count++;
        switch (TRUE) {
          case ($empty_row):
          	$this->empty_count++;
			break;
		  case (!$this->headers):
			$results=$this->upload_header($data);
            break;
		  default:
            foreach ($this->map as $field => $map) {
            	switch (TRUE) {
                  case (!isset($map->column)):
                  	break;
                  case ($map->filter_import):
                  	$func=$map->filter_import;
                	$results[$field]=$func($data[$map->column]);
                    break;
                  default:
                	$results[$field]=$this->upload_value($data[$map->column],$map);
 				}
                if ( (isset($map->column)) && ($map->utf8) ) $results[$field]=utf8_encode($results[$field]);
            }
			switch (TRUE) {
              case (!method_exists($table,"import_verify")):
              case ($table->import_verify($results)):
              	$this->update_count++;
                break;
              default:
              	$this->reject_count++;
			}
		}
        if (!sizeof($results)) $results[]="";
		return $results;
    }
    function upload_header($data,$internal=FALSE) {
    global $database;
        $table=$database->{$this->table};
		$this->headers=TRUE;
        $xref=array();
        $groups=array();
	    foreach ($this->map as $field => $map) {
			if ($internal) {
				$xref[$field]=$field;
			  } else {
				$xref[strtolower($map->name)]=$field;
			}
	    }
        foreach ($data as $column => $name) {
			$name=strtolower($name);
            switch (TRUE) {
              case (array_key_exists($name,$xref));
				$field=$xref[$name];
              	break;
              case (in_array($name,$xref)):
              	$field=$name;
              	break;
              default:
              	$field="";
            }
            if (!strlen($field)) continue;
            switch (TRUE) {
			  case (!$this->map[$field]->import):
				break;
              case (!isset($this->map[$field]->column)):
                $this->map[$field]->column=$column;
                if ( ($this->map[$field]->group) && (!in_array($this->map[$field]->group,$groups)) ) $groups[]=$this->map[$field]->group;
                break;
               default:
                $this->map[$field]->duplicate=TRUE;
            }
        }
		$table->map->complete=TRUE;
        foreach ($this->map as $field => $map) {
			if (($map->group) && (in_array($map->group,$groups)) ) $map->required=TRUE;
			if (!isset($map->column)) $table->map->complete=FALSE;
            switch (TRUE) {
              case (isset($map->column)):
				$results[]=$field;
              	break;
              case (!$map->required):
                break;
              case (!isset($map->column)):
                $this->errors[]="Required field missing: " . $map->name;
                break;
              case ($map->duplicate):
                $this->errors[]="Duplicate field entries: " . $map->name;
                break;
            }
        }
		return $results;
    }
    function upload_value($value,$map) {
		$value=trim($value);
		switch ($map->type) {
// Numeric values
          case "decimal":
          case "percent":
        	return number_format(fn_number($value),$map->decimal,".","");
          case "boolean":
			return preg_match("/^[1,Y]/i",$value);
          case "id":
			return intval($value);
// Date & Time values
          case "date":
          	$date=fn_date($value,"mdy");
            if (!$date) {
            	return "0000/00/00";
              } else {
				return $date;
			}
          case "time":
          case "datetime":
			return fn_date($value,"timestamp");
// Alphanumeric values
          case "uppercase":
          	return strtoupper($value);
          case "lowercase":
          	return strtolower($value);
          case "text":
			return str_ireplace("<br>","\r\n",$value);
		  case "email":
	        $email=new email_class($value,FALSE);
			if ($email->error) {
            	return "";
			  } else {
				return $email->address;
			}
          default:
          	return $value;
		}
    }
    function upload_html() {
    global $database,$forms;
        $results=array();
		$results[]=$this->hidden_html(array("external_file"=>$this->external_file));
        $table=$database->{$this->table};
        $missing_count=0;
	    $results[]="<p class='standard left'>Records in table: " . number_format($this->line_count);
        $results[]="<br>Records accepted for update: " .  number_format($this->update_count);
		$results[]="<br>Empty & header records: " . number_format(($this->empty_count + 1));
        if ($this->reject_count) $results[]="<br>Records rejected: " .  number_format($this->reject_count);
        $results[]="</p>";
	    $results[]="<table class='small border'>";
	    $results[]="<tr>";
	    $results[]="<th>Field</th>";
	    $results[]="<th>Required?</th>";
	    $results[]="<th>Column#</th>";
	    $results[]="<th>Type</th>";
	    $results[]="<th>Error</th>";
	    $results[]="</tr>";
	    foreach ($this->map as $field => $map) {
			if ( ($map->hidden) && (!isset($map->column)) ) continue;
	        switch (TRUE) {
	          case (isset($this->errors[$field])):
	            $background=$forms->background->red;
                $missing_count++;
	            break;
              case (!$map->import):
	          case (isset($map->column)):
	            $background="";
	            break;
	          default:
	            $background=$forms->background->yellow;
                $missing_count++;
	        }
            if (!in_array($field,$table->map->omit_field)) {
	            $results[]="<tr $background>";
	            $results[]="<td>" . $map->name . "</td>";
				switch (TRUE) {
                  case ($map->required):
	                $text="Yes";
                    break;
				  case (!$map->import):
                  	$text="Export only";
                    break;
				  default:
					$text="No";
	            }
	            $results[]="<td class=center>$text</td>";
	            $results[]="<td class=center>";
	            if (isset($map->column)) {
	                $results[]=($map->column + 1);
	              } else {
	                $results[]="Omitted";
	            }
	            $results[]="</td>";
                $map_type=$map->type;
                if (!is_null($map->decimal)) $map_type .= ": " . $map->decimal;
	            $results[]="<td>" . $map_type . "</td>";
	            $results[]="<td>" . $this->errors[$field] . "</td>";
	            $results[]="</tr>";
			}
	    }
	    $results[]="</table>";
	    $results[]="<p class='left'>" . $forms->button("Cancel",array("onclick"=>"fn_action('cancel');"));
		switch (TRUE) {
		  case (sizeof($this->errors)):
          case (!$this->update_count):
			$text="";
            break;
	      case ($this->report['function'] != "upload"):
	        $text="Continue (Review only, no update)";
	        break;
	      case ($missing_count):
	        $text="Continue (update only)";
	        break;
	      default:
	        $text="Continue (update and add)";
		}
		if ($text) $results[]=" " . $forms->button($text,array("onclick"=>"fn_action('update');"));
	    $results[]="</p>";
		if ($missing_count) {
	        $results[]="<p class='standard center'>Legend: ";
	        $results[]="<span style='background: #ffff66; color: #000000;'>&nbsp;&nbsp;Missing, required to add&nbsp;&nbsp;</span>";
	        $results[]="&nbsp;&nbsp;<span style='background: #ff0000; color: #000000;'>&nbsp;&nbsp;Defined multiple times&nbsp;&nbsp;</span>";
	        $results[]="</p>";
		}
        print implode("\n",$results);
    }
	function import() {
    global $database;
		$table=$database->{$this->table};
        if ($this->report['function']=="update") $table->map->update=TRUE;
		$this->external_file=$_POST['external_file'];
        $table->meta->error_abort=FALSE;
        if (method_exists($table,"import_initialize")) $table->import_initialize();
	    $fh=fopen($this->internal_file,"r");
	    while ($line = fgetcsv($fh,0,"\t","\"")) {
	        $this->import_line($line);
	    }
	    fclose($fh);
        $table->meta->error_abort=TRUE;
		unlink($this->internal_file);
        if ($this->report['function'] != "upload") {
        	$this->message="Review complete";
		  } else {
			$this->message="Update complete";
		}
        if (sizeof($this->errors)) $this->message .= " / Errors detected";
	    $this->update_results[]="Table: {$this->table}";
	    if ($this->group) $this->update_results[]="Group: {$this->group}";
	    if ($this->code) $this->update_results[]="Code: {$this->code}";
	    $this->update_results[]="File: {$this->external_file}";
        $fields=array();
        foreach ($this->map as $map) {
			if (isset($map->column)) $fields[]=$map->name;
        }
		$this->update_results[]="Fields: " . implode(", ",$fields);
	    if ($this->update_count) $this->update_results[]="Updated records: " . number_format($this->update_count);
	    if ($this->empty_count) $this->update_results[]="Empty records: " . number_format($this->empty_count);
	    if (sizeof($this->errors)) $this->update_results[]="Rejected records: " . number_format(sizeof($this->errors));
		if ($this->report['function'] == "upload") {
			switch (TRUE) {
              case (method_exists($table,"import_delete")):
              	$table->import_delete();
                break;
               case ($table->map->update_timestamp):
	            $query="delete from " . $this->table . " where update_timestamp <> " . fn_escape($table->map->update_timestamp);
	            $database->temp->query($query);
	        }
			if (method_exists($database->log,"ipc")) $database->log->ipc("Import Table: " . $this->table,$this->update_results);
		}
        return $this->message;
	}
    function import_line($data) {
    global $database;
		$table=$database->{$this->table};
		$this->line_count++;
        switch (TRUE) {
          case ((sizeof($data)==1) && (strlen(!$data[0]))):
			$this->empty_count++;
            break;
          case (!$this->headers):
        	$this->upload_header($data,TRUE);
			break;
          default:
			$results=array();
            foreach ($this->map as $field => $map) {
            	if (isset($map->column)) {
                	$results[$field]=$data[$map->column];
                }
            }
            $table->map->count++;
            $results=$table->import_update($results);
			if (!sizeof($results)) {
            	$this->update_count++;
			  } else {
            	$this->errors[$this->line_count]=$results;
			}
		}
    }
    function import_html() {
    global $forms;
        $results=array();
		$results[]=$this->hidden_html(array("external_file"=>$this->external_file));
		$results[]="<p class=standard>" . implode("<br>",$this->update_results) . "</p>";
	    $results[]="<p class=standard>" . $forms->button("Return",array("onclick"=>"fn_action('');")) . "</p>";
        if (sizeof($this->errors)) {
        	$results[]="<table class='small border'>";
            $results[]="<tr>";
            $results[]="<th>Line#</th>";
            $results[]="<th>Field</th>";
            $results[]="<th>Value</th>";
            $results[]="</tr>";
            foreach ($this->errors as $line => $error) {
            	$count=0;
                foreach ($error as $field => $field_error) {
	                $results[]="<tr>";
	                if (!$count) $results[]="<td class=center rowspan=" . sizeof($error) . ">$line</td>";
                    if (array_key_exists($field,$this->map)) {
                    	$text=$this->map[$field]->name;
					  } else {
                      	$text=$field;
					}
	                $results[]="<td>$text</td>";
	                $results[]="<td>$field_error</td>";
	                $results[]="</tr>";
	                $count++;
				}
            }
            $results[]="</table>";
		}
		print implode("\n",$results);
    }
    function clear($query) {
    global $database;
		$database->temp->query($query);
        $message=number_format($database->affected_rows()) . " records deleted";
        $this->update_results[]="Table <b>" . $this->table . "</b> clear:";
        $this->update_results[]=$message;
        $this->update_results[]="Query: \n" . $query;
		if (method_exists($database->log,"scs_map")) $database->log->scs_map($this->update_results);
	    $forms->message[]=$message;
		return $message;
    }
    function clear_html($title,$count) {
    global $database, $forms;
        $table=$database->{$this->table};
        $results=array();
		$results[]=$this->hidden_html();
        if (is_array($title)) {
        	$text=$title;
		  } else {
          	$text=array();
		}
        if (!$count) $text[]="No records found";
        if (sizeof($text)) print "<p>" . implode("<br>",$text) . "</p>\n";
        $buttons=array();
		$buttons[]=$forms->button("Return",array("onclick"=>"fn_action('cancel');"));
		if ($count) $buttons[]=$forms->button("Remove " . number_format($count) . " records",array("onclick"=>"fn_action('update');"));
        $results[]="<p>" . implode(" ",$buttons) . "</p>";
		print implode("\n",$results);
	}
}
class xls_class {
	var $workbook;
    var $worksheet;
    var $map=array();
    var $row=0;
    var $wraptext=FALSE;
	function __construct($file,$title="",$subject="",$options=array() ) {
    global $database;
	    $this->workbook = new ExcelWriterXML($file);
	    $this->workbook->overwriteFile(TRUE);
	    $this->workbook->docTitle($title);
	    $this->workbook->docSubject($subject);
	    $this->workbook->docAuthor($_SESSION['user']->name);
	    $this->workbook->docCompany($database->registry->company->name);

	    $style=$this->workbook->addStyle('alphanumeric');
	    if ($this->wraptext) $style->alignWraptext();
	    $style->alignVertical('Top');

        $style=$this->workbook->addStyle("numeric");
        $style->alignVertical('Top');
        $style->alignHorizontal('Right');

	    for ($i=0; $i < 10; $i++) {
	        $items=array();
	        $items[]="#,#0";
	        if ($i) $items[]="." . substr("000000000",0,$i);
	        $style=$this->workbook->addStyle("decimal{$i}");
	        $style->numberFormat(implode("",$items));
	        $style->alignVertical('Top');
	        $style->alignHorizontal('Right');
	        $style=$this->workbook->addStyle("percent{$i}");
	        $style->numberFormat(implode("",$items) . "%");
	        $style->alignVertical('Top');
	        $style->alignHorizontal('Right');
	    }

	    $style=$this->workbook->addStyle('date');
	    $style->numberFormat("mm/dd/yyyy");
	    $style->alignVertical('Top');
	    $style->alignHorizontal('Left');

	    $style=$this->workbook->addStyle('period');
	    $style->numberFormat("mm/yyyy");
	    $style->alignVertical('Top');
	    $style->alignHorizontal('Left');

	    $style=$this->workbook->addStyle('time');
	    $style->numberFormat('hh:mm:ss');
	    $style->alignVertical('Top');
	    $style->alignHorizontal('Left');

	    $style=$this->workbook->addStyle('datetime');
	    $style->numberFormat("mm/dd/yyyy hh:mm:ss");
	    $style->alignVertical('Top');
	    $style->alignHorizontal('Left');
    }
    function worksheet($name,$map) {
        $this->map=$map;
	    $this->worksheet=$this->workbook->addSheet($name);
		$count=0;
        foreach ($this->map as $field => $item) {
			if ($item->export) {
	            $count++;
                switch (TRUE) {
                  case ($item->width < strlen($item->name)):
					$width=strlen($item->name);
                    break;
                  case ($item->width > 100):
                  	$width=100;
                    break;
                  default:
                  	$width=$item->width;
				}
	            $this->worksheet->columnWidth($count,($width * 6));
			}
        }
        $this->row=0;
		$data=array();
        foreach ($this->map as $field => $item) {
			if ($item->export) $data[$field]=$item->name;
        }
        $this->row($data,"db_header",$format="string");
    }
    function row($data,$style_override="") {
//float, numeric, decimal:n percent:n date datetime timestamp uppercase lowercase boolean crlf map
		$this->row++;
		$count=0;
        foreach ($this->map as $field => $item) {
			if ($item->export) {
	            $count++;
                $text=$data[$field];
                if (is_array($text)) $text=implode("\n",$text);
                switch (TRUE) {
                  case ($style_override):
                  	$style=$style_override;
					break;
				  case ($item->type=="numeric"):
                  	$style="numeric";
                    $format="numeric";
                  	break;
                  case ($item->type=="decimal"):
                  	$style="decimal" . $item->decimal;
                    $format="numeric";
                  	break;
                  case ($item->type=="percent"):
                  	$style="percent" . $item->decimal;
                    $format="numeric";
                  	break;
				  case ($item->type=="date"):
                  	$style="date";
                    $format="date";
                    if ($text) {
						$text=date("Y-m-d",strtotime($text)) . "T00:00:00.000";
					  } else {
						$text="";
					}
                    break;
				  case ($item->type=="period"):
                  	$style="period";
                    $format="date";
                    if ($text) {
						$text=date("Y-m-d",strtotime($text)) . "T00:00:00.000";
					  } else {
						$text="";
					}
                    break;
				  case ($item->type=="time"):
                  	$style="time";
                    $format="date";
                    if ($text) {
						$text=date("Y-m-d",$text) . "T" . date("H:i:s",$text) . ".000";
					  } else {
						$text="";
					}
                    break;
				  case ($item->type=="datetime"):
                  	$style="datetime";
                    $format="date";
                    if ($text) {
						$text=date("Y-m-d",$text) . "T" . date("H:i:s",$text) . ".000";
					  } else {
						$text="";
					}
                    break;
				  case ($item->type=="boolean"):
                  	$style="alphanumeric";
					if (in_array($text,array("1","Y","y"))) {
						$text="Y";
					  } else {
                      	$text="N";
                    }
                    $format="string";
                    break;
                  default:
                  	$style="alphanumeric";
                    $format="string";
                }
                switch ($format) {
				  case "numeric":
					$this->worksheet->writeNumber($this->row,$count,$text,$style);
                    break;
				  case "date":
					$this->worksheet->writeDateTime($this->row,$count,$text,$style);
                    break;
				  default:
					$this->worksheet->writeString($this->row,$count,$text,$style);
				}
			}
        }
    }
    function write() {
	    $this->workbook->sendHeaders();
	    $this->workbook->writeData();
    }
}
class table_map_class {
	var $fields=array();
    var $omit_field=array();
    var $options=array();
    var $constant;
    var $code;
    var $count=0;
    var $update_timestamp;
    function __construct($options) {
		$this->options=$options;
        $this->constant=new stdClass();
    }
    function item($field,$options=array() ) {
	    if ( (array_key_exists('omit',$options)) && ($options['omit']) ) return;
	    if (!array_key_exists('name',$options)) $options['name']=$field;
	    if (!array_key_exists('type',$options)) $options['type']="alphanumeric";
	    if (!array_key_exists('width',$options)) $options['width']=20;
	    if (!array_key_exists('comment',$options)) $options['comment']="";
	    if (!array_key_exists('group',$options)) $options['group']="";
	    if (!array_key_exists('required',$options)) $options['required']=FALSE;
	    if (!array_key_exists('export',$options)) $options['export']=TRUE;
	    if (!array_key_exists('import',$options)) $options['import']=TRUE;
	    if (!array_key_exists('hidden',$options)) $options['hidden']=FALSE;
	    if (!array_key_exists('filter_import',$options)) $options['filter_import']="";
	    if (!array_key_exists('filter_export',$options)) $options['filter_export']="";
        if ( ($options['group']) && (!array_key_exists($options['group'],$this->fields)) ) $this->fields[ $options['group'] ]=array();
	    switch (TRUE) {
	      case (!strlen($field)):
          case ( (!$options['group']) && (array_key_exists($field,$this->fields)) ):
          case ( ($options['group']) && (array_key_exists($field,$this->fields[ $options['group'] ])) ):
	        break;
          case ($options['group']):
			$this->fields[ $options['group'] ][$field]=new table_map_item_class($options);
            break;
          default:
			$this->fields[$field]=new table_map_item_class($options);
            break;
	    }
    }
}
class table_map_item_class {
    var $name;
    var $comment=array();
    var $type;
	var $decimal;
    var $format;
    var $required;
    var $width;
    var $group;
    var $export;
    var $import;
    var $utf8;
    var $filter_import;
    var $filter_export;
    var $hidden=FALSE;
    var $update=FALSE;
	function __construct($options) {
		$type_list=array();
		// Alphanumeric
		$type_list["alphanumeric"]="Alphanumeric text";
		$type_list["lowercase"]="Alphanumeric lower case text";
		$type_list["email"]="Email address";
		$type_list["uppercase"]="Alphanumeric upper case text";
		$type_list["text"]="Multiple line alphanumeric text";
		// Date / Time
		$type_list["date"]="Date (MM/DD/YYYY)";
		$type_list["period"]="Period (MM/YYYY)";
		$type_list["time"]="Time (HH:MM:SS)";
		$type_list["datetime"]="MM/DD/YYYY HH:MM:SS";
        // Numeric
		$type_list["numeric"]="Number with floating decimal";
		$type_list["decimal"]="Number/Decimal places:  ";
		$type_list["id"]="Unique record ID (integer)";
		$type_list["percent"]="Percent/Decimal places:  ";
		$type_list["boolean"]="True: Y, 1; False: N, 0";
		//  Deprecated
        $deprecated_list=array();
        $deprecated_list["integer"]="numeric";
        $deprecated_list["float"]="numeric";
        $deprecated_list["ymd"]="date";
        $deprecated_list["crlf"]="text";

		$this->name=$options['name'];
		list ($this->type,$this->decimal)=explode(":",$options['type']);
		switch (TRUE) {
		  case (array_key_exists($this->type,$type_list)):
			break;
		  case (array_key_exists($this->type,$deprecated_list)):
			$this->type=$deprecated_list[$this->type];
			break;
		  default:
			die("Format type: {$this->type} not supported (" . __FILE__ . ")");
		}
        if (in_array($this->type,array("decimal","percent"))) $type_list[$this->type] .= $this->decimal;
		$this->width=$options['width'];
        $this->required=$options['required'];
        $this->hidden=$options['hidden'];
		$this->utf8=$options['utf8'];
        $this->filter_import=$options['filter_import'];
        if ( ($this->filter_import) && (!function_exists($this->filter_import)) ) die("Missing filter_import: {$this->filter_import}  (" . __FILE__ . ")");
        $this->filter_export=$options['filter_export'];
        if ( ($this->filter_export) && (!function_exists($this->filter_export)) ) die("Missing filter_export: {$this->filter_export}  (" . __FILE__ . ")");
        $this->export=$options['export'];
        $this->import=$options['import'];
        $this->group=$options['group'];
        if ( (array_key_exists("fetch",$options)) ? $this->fetch=$options['fetch'] : $this->fetch=FALSE);
        if ($options['comment']) $this->comment[]=$options['comment'];
        $this->comment[]=$type_list[$this->type];
		if ($this->required) $this->comment[]="Mandatory field included in all imports/exports";
    }
}

$database->scs_database=new scs_database_class();
class scs_database_class extends database_class {
	var $fields=array();
    function __construct() {
    	$this->data=array();
        $this->fetch=array();
    	$this->meta=new database_meta_class();
    } 
	function field($field,$type="alphanumeric") {
		if (!array_key_exists($field,$this->fields)) $this->fields[$field]=$type;
    }
    function map($options) {
        $this->map=new table_map_class($options);
        foreach ($this->fields as $field => $type) {
 			$this->map->item($field,array("type"=>$type,"fetch"=>TRUE));
        }
    }
    function fetch($fetch=FALSE) {
        if ($fetch) $this->fetch=$this->fetch_array();
	}
}
$database->scs_text=new scs_text_class();
class scs_text_class extends database_class {
    var $encoding="ascii";
    var $delimiter;
	var $contents=array();
	var $fields=array();
    function __construct() {
        $this->fetch=array();
    	$this->meta=new database_meta_class();
    }
    function map($options) {
		if (strlen($options['scs_text_file'])) $this->contents=@file($options['scs_text_file']);
        if (sizeof($this->contents) < 2) {
        	$this->contents=array();
            return;
        }
        switch (TRUE) {
          case ($options['scs_text_delimiter']):
          	$this->delimiter=$options['scs_text_delimiter'];
            break;
          case (preg_match("/" . preg_quote(".csv","/") . "$/i",$options['scs_text_file'])):
          	$this->delimiter=",";
            break;
          default:
          	$this->delimiter="\t";
		}
        $data=$this->read(0);
        $this->map=new table_map_class($options);
        foreach ($data as $key => $value) {
        	if ( (!strlen($value)) || (in_array($value,$this->fields)) ) continue;
			$this->fields[$key]=$value;
            $this->map->item($value,array("type"=>"alphanumeric","fetch"=>TRUE));
        }
    }
    function read($item) {
		$line=trim($this->contents[$item]);
        switch (TRUE) {
          case ($item):
          	break;
          case preg_match("/^(\xef\xbb\xbf)(.*)/",$line):
          	$this->encoding="utf-8";
            $line=substr($line,3);
            break;
		}
		return str_getcsv($line,$this->delimiter,"\"");
    }
    function query($query="", $found_rows = false) {
        $this->meta->rows=(sizeof($this->contents) - 1);
        $this->meta->found_rows=$this->meta->rows;
    }
    function xfetch($item) {
		if (!isset($this->ix)) {
        	$this->ix=0;
            $this->email=array();
		}
		$eof=FALSE;
        while (!$eof) {
        	$this->ix++;
			$data=$this->read($this->ix);
            if (!in_array($data[5],$this->email)) {
            	$this->email[]=$data[5];
				break;
			}
        }
		$this->fetch=array();
        foreach ($this->fields as $key => $field) {
        	$this->fetch[$field]=trim($data[$key]);
        }
	}
    function fetch($item) {
		$data=$this->read($item);
		$this->fetch=array();
        foreach ($this->fields as $key => $field) {
        	$this->fetch[$field]=trim($data[$key]);
        }
	}
    function free_result() {
    }
}
?>