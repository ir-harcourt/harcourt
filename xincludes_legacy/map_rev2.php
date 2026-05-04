<?php
require_once "vendor/mk-j/php_xlsxwriter/xlsxwriter.class.php";
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
    var $file_type_list=array(".xlsx"=>"Excel (xlsx)",".txt"=>"Tab Delimited Text (txt)",".csv"=>"Comma Separated Values (csv)");
    var $delimiter;
    var $report;
    var $fhw;
    var $fhr;
    var $bom;
    var $xls_rows=1000000;
    var $xls_page=0;
    var $debug=FALSE;
    var $forms_map=array();
    var $map=array();
    function scs_version() {
    	$results=array();
        $results['01/30/2018']=array("Initial release","Based on rev1 12/28/2017");
        $results['file']=__FILE__;
        return $results;
    }
    function __construct($table,$report,$group="",$code="") {
    global $database;
		ob_start();
		$this->table=$table;
        $this->group=$group;
        $this->code=$code;
        switch ($table) {
          case "":
          	return;
          case "scs_text":
			$database->scs_text=new scs_text_class();
            break;
          case "scs_database":
			$database->scs_database=new scs_database_class();
            break;
          default:
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
        if (!class_exists("xlswriter_class")) unset($this->file_type_list[".xlsx"]);
        if (!array_key_exists("file_type",$this->report)) $this->report['file_type']=$_POST['file_type'];
        switch ($this->report['file_type']) {
		  case ".xlsx":
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
		set_time_limit(60);
        $table=$database->{$this->table};
        if (!sizeof($this->map)) $this->fields_map();
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
        if (!is_array($query)) $query=array($query);
        switch (TRUE) {
          case (!$this->report['file_type'] == ".xlsx"):
			break;
          case ($this->xls_page):
          	$query[]="limit " . (($this->xls_page - 1) * $this->xls_rows) . ", " . $this->xls_rows;
            break;
          default:
			$query[]="limit " . $this->xls_rows;
		}
		$table->query($query);
		switch (TRUE) {
          case (!$table->meta->rows):
			$forms->error('null','',"No records found in selected range");
            return;
          case ( ($this->report['file_type'] == ".xlsx") && ($table->meta->found_rows > $this->xls_rows) ):
			if ($this->table != "scs_text") {
	            $message=array();
                $message[]="Number of rows found (" . number_format($table->meta->found_rows) . ") excedes Excel capacity of " . number_format($this->xls_rows) . " rows";
	            $message[]="Please break your export into " . ceil($table->meta->found_rows / $this->xls_rows) . " files or use the txt/csv format";
	            $forms->error('null','',implode("<br>",$message));
	            return;
			}
        }
		$filename=array();
		if (array_key_exists("filename",$options)) {
        	$filename[]=$options['filename'];
		  } else {
	        $filename[]="{$this->table}";
	        if ($this->group) $filename[]=strtolower(str_replace(array(" ",",","/","\\","|","&","'","\""),"_",$this->group));
	        if ($this->code) $filename[]=strtolower(str_replace(array(" ",",","/","\\","|","&","'","\""),"_",$this->code));
		}
        if (array_key_exists("date",$options)) $filename[]=date("Ymd");
        if (array_key_exists("time",$options)) $filename[]=date("Hi");
        if (array_key_exists("timestamp",$options)) $filename[]=strtotime("now");
        if ($this->xls_page) $filename[]="page" . $this->xls_page;
		$this->external_file=(strlen($this->report['external_file']) ? $this->report['external_file'] : implode("_",$filename) . $this->report['file_type']);
	    $this->update_results[]="Table: {$this->table}";
	    if ($this->group) $this->update_results[]="Group: {$this->group}";
	    if ($this->code) $this->update_results[]="Code: {$this->code}";
	    $this->update_results[]="File: " . $this->external_file;
		$this->update_results[]="Rows: " . number_format($table->meta->found_rows);
        if ( (!ob_get_contents()) && ($this->report['file_type'] == ".xlsx") ) {
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
		$xls=new xlswriter_class($this->external_file,"Export Table: " . $this->table, $subject);
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
	          case ($map->type == "percent"):
	            $results[$field]=number_format($data,($map->decimal + 2),".","");
                break;
	          case ($map->type == "decimal"):
	            $results[$field]=number_format($data,$map->decimal,".","");
                break;
	          case ($map->type == "boolean"):
              	if ($data) {
	                $results[$field]="Y";
				  } else {
	                $results[$field]="N";
	            }
                break;
// XLS Date & Time
              case ( ($this->report['file_type']==".xlsx") && ($map->type == "date") ):
              case ( ($this->report['file_type']==".xlsx") && ($map->type == "period") ):
				switch (TRUE) {
                  case (!$data):
                    $results[$field]="";
                    break;
                  case ($map->timestamp):
	                $results[$field]=date("Y-m-d",$data);
	                break;
                  default:
                  	$results[$field]=preg_replace("/\//","-",$data);
                }
              	break;
              case ( ($this->report['file_type']==".xlsx") && ($map->type == "time") ):
              case ( ($this->report['file_type']==".xlsx") && ($map->type == "timestamp") ):
				$results[$field]=( ($data) ? date("Y-m-d g:i:s A",$data) : "");
              	break;
// Date & Time
	          case ($map->type == "date"):
				switch (TRUE) {
                  case (!$data):
                    $results[$field]="";
                    break;
                  case ($map->timestamp):
	                $results[$field]=date("m/d/Y",$data);
	                break;
                  default:
                  	$results[$field]=fn_date($data,"ymd");
                }
              	break;
	          case ($map->type == "period"):
				switch (TRUE) {
                  case (!$data):
                    $results[$field]="";
                    break;
                  case ($map->timestamp):
	                $results[$field]=date("m/Y",$data);
	                break;
                  default:
                  	$results[$field]=fn_date($data,"yymm");
                }
              	break;
	          case ($map->type == "time"):
	            $results[$field]=date("H:i:s",$data);
                break;
	          case ($map->type == "timestamp"):
	            $results[$field]=date("m/d/Y H:i:s",$data);
                break;
// Alphanumeric
              case ( ($this->report['file_type'] != ".xlsx") && ($map->type == "text") ):
	            $results[$field]=str_ireplace("\r\n","<br>",$data);
                break;
// Special
			  case ($map->type == "ipv4"):
                $results[$field]=long2ip($data);
                break;
			  case ($map->type == "ipv6"):
                $results[$field]=inet_ntop($data);
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
                    $results[]="<td>" . $item->type_name . "</td>";
                    $results[]="<td>" . implode("<br>\n",$item->comment) . "</td>";
                    $results[]="</tr>";
                }
            }
        }
        $results[]="</tbody>";
        $results[]="</table>";
        $buttons=array("cancel"=>"","pages"=>"","update"=>"");
		$buttons['cancel']=$forms->button("Return",array("onclick"=>"fn_action('cancel');"));
        switch (TRUE) {
          case (!$count):
			$update_text="All records will be downloaded";
			break;
          case ( ($forms->report['file_type']==".xlsx") && ($count > $this->xls_rows) ):
			$xls_page_count=ceil($count / $this->xls_rows);
			$results[]="<p>Your export contains " . number_format($count) . " records. Selecting all rows will excede the capacity of Excel."  .
			$results[]="<br>It is suggested that you only upload selected pages.</p>";
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
        if ( (!ob_get_contents()) && ($this->report['file_type'] == ".xlsx") ) {
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
		$xls=new xlswriter_class($this->external_file,"Export Table: " . $this->table, $subject);
        $xls->worksheet("Data",$this->map);
		$xls->write();
    }
    protected function upload_open($id="upload",$xlsx_worksheet=1) {
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
		  case (preg_match("/.xlsx$/i",$_FILES[$id]['name'])):
			$this->report['file_type']=".xlsx";
            $this->delimiter="\t";
            break;
		  default:
			$forms->message[]="File format must be tab delimited text (.txt) or Comma separated values (.csv)";
            return;
		}
        $_SESSION['upload_file']=$this->external_file;
		if (file_exists($this->temporary_file)) unlink($this->temporary_file);
        move_uploaded_file($_FILES[$id]['tmp_name'],$this->temporary_file);
        if ($this->report['file_type'] == ".xlsx") {
			$this->xlsx_worksheet=$xlsx_worksheet;
	        try {
	            $this->xlsx=new xlsreader_class($this->temporary_file);
	          } catch (Exception $e) {
	            $forms->message[]=$this->external_file . " not XLXS format";
                return;
			}
            $sheet_names=$this->xlsx->getSheetNames();
            if (  (!array_key_exists($this->xlsx_worksheet,$sheet_names)) && (!in_array($this->xlsx_worksheet,$sheet_names)) ) {
            	$forms->message[]="Worksheet " . $this->xlsx_worksheet  . " not found in " . $this->external_file;
                return;
            }
		  } else {
			$this->fhr=fopen($this->temporary_file,"r");
		}
        return 1;
    }
    protected function upload_close() {
        if ($this->report['file_type'] != ".xlsx") {
	        fclose($this->fhr);
	        unlink($this->temporary_file);
		}
    }
    function upload($id="upload",$options=array()) {
    global $database,$forms;
        $table=$database->{$this->table};
    	$upload_update=(( ($options['update']) && method_exists($table,"upload_update") ) ? TRUE : FALSE);
		if (!$this->upload_open($id)) return;
		if (file_exists($this->internal_file)) unlink($this->internal_file);
		if (!$upload_update) $this->fhw=fopen($this->internal_file,"w");
        if ($this->report['file_type'] == ".xlsx") {
			$data=$this->xlsx->getSheetData($this->xlsx_worksheet);
            foreach ($data as  $items) {
                if ($upload_update) {
                    $table->upload_update($this->upload_line($items));
                  } else {
	            	fputcsv($this->fhw,$this->upload_line($items),"\t","\"");
				}
            }
          } else {
			while ($line = fgets($this->fhr,4096)) {
	            $items=str_getcsv(preg_replace("/^" . pack("H*","EFBBBF") . "/", "", $line),$this->delimiter,"\"");
                if ($upload_update) {
                    $table->upload_update( $this->upload_line($items) );
				  } else {
	            	fputcsv($this->fhw,$this->upload_line($items),"\t","\"");
				}
        	}
		}
		if (!$upload_update) fclose($this->fhw);
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
    protected function upload_line($data) {
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
    protected function upload_header($data,$internal=FALSE) {
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
    protected function upload_value($value,$map) {
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
          case "timestamp":
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
	            $results[]="<td>" . $map->type_name . "</td>";
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
    var $type_name;
	var $decimal;
    var $format;
    var $required;
    var $width;
    var $group;
    var $export;
    var $import;
    var $utf8;
    var $wrap;
    var $xls_format;
    var $timestamp;
    var $filter_import;
    var $filter_export;
    var $hidden=FALSE;
    var $update=FALSE;
    var $header=FALSE;
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
		$type_list["time"]="Time (HH:MM)";
		$type_list["timestamp"]="Timestamp (MM/DD/YYYY HH:MM:SS)";
        // Numeric
		$type_list["numeric"]="Number with floating decimal";
		$type_list["decimal"]="Number/Decimal places:  ";
		$type_list["id"]="Unique record ID (integer)";
		$type_list["percent"]="Percent/Decimal places:  ";
		$type_list["boolean"]="True: Y, 1; False: N, 0";
        // Special
		$type_list["ipv4"]="IPV4 address";
		$type_list["ipv6"]="IPV6 address";

        // Deprecated list
        $deprecated_list=array("datetime"=>"timestamp");

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
        $this->type_name=( (array_key_exists("type_name",$options)) ? $options['type_name'] : $type_list[$this->type]);
		$this->width=$options['width'];
        $this->required=$options['required'];
        $this->hidden=$options['hidden'];
		$this->utf8=$options['utf8'];
        $this->timestamp=(array_key_exists("timestamp",$options)) ? $options['timestamp'] : FALSE;
        $this->wrap=(array_key_exists("wrap",$options)) ? $options['wrap'] : FALSE;
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
        switch ($this->type) {
          case "date":
			$this->xls_format="MM/DD/YYYY";
            break;
          case "period":
			$this->xls_format="MM/YYYY";
            break;
          case "time":
			$this->xls_format="HH:MM";
            break;
          case "timestamp":
			$this->xls_format="MM/DD/YYYY HH:MM:SS";
            break;
          case "id":
			$this->xls_format="integer";
            break;
          case "numeric":
			$this->xls_format="GENERAL";
            break;
          case "decimal":
			$this->xls_format=number_format(0,$this->decimal);
            break;
          case "percent":
			$this->xls_format=number_format(0,$this->decimal) . "%";
            break;
          default:
			$this->xls_format="@";
		}
    }
}
class xlswriter_class {
	var $workbook;
    var $worksheet;
    var $file;
    var $map=array();
    var $row_options=array();
	function __construct($file,$title="",$subject="",$options=array(),$row_options=array() ) {
    global $database;
		$this->file=$file;
	    $this->workbook=new XLSXWriter();
	    $this->workbook->setTitle($title);
	    $this->workbook->setSubject($subject);
	    $this->workbook->setAuthor($_SESSION['user']->name);
	    $this->workbook->setCompany($database->registry->company->name);
	    $this->workbook->setKeywords($options['keywords']);
	    $this->workbook->setDescription($options['description']);
    }
    function worksheet($worksheet,$map) {
    	$this->worksheet=$worksheet;
		$this->map=$map;
		$header=array();
        $widths=array();
        $cell_style=array();
        foreach ($this->map as $field => $item) {
        	if ($item->export) {
            	$header[$item->name]=$item->xls_format;
                $widths[]=$item->width;
			}
		}
        $options=array();
        $options['widths']=$widths;
	    $this->row_options['valign']="top";
		$this->row_options['wrap_text']=TRUE;
		$this->workbook->writeSheetHeader($this->worksheet, $header, $options);
    }
    function row($data,$style_override="") {
		$this->workbook->writeSheetRow($this->worksheet, $data, $this->row_options);
    }
    function write() {
	    header("Content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
	    header("Content-Disposition: attachment; filename=" . $this->file);
	    header("Content-Transfer-Encoding: binary");
	    header("Pragma: no-cache");
	    header("Expires: 0");
	    $this->workbook->writeToStdOut();
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
    function map($options) {
        $this->map=new table_map_class($options);
	}
    function fetch($fetch=FALSE) {
        if ($fetch) $this->fetch=$this->fetch_array();
        if (isset($this->export)) $this->data=$this->export->data($this->fetch);
	}
}
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

class xlsreader_class {
	protected $sheets = array();
	protected $sharedstrings = array();
	protected $sheetInfo;
	protected $zip;
	public $config = array(
		'removeTrailingRows' => true
	);

	// XML schemas
	const SCHEMA_OFFICEDOCUMENT  =  'http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument';
	const SCHEMA_RELATIONSHIP  =  'http://schemas.openxmlformats.org/package/2006/relationships';
	const SCHEMA_OFFICEDOCUMENT_RELATIONSHIP = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
	const SCHEMA_SHAREDSTRINGS =  'http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings';
	const SCHEMA_WORKSHEETRELATION =  'http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet';

	public function __construct($filePath, $config = array()) {
		$this->config = array_merge($this->config, $config);
		$this->zip = new ZipArchive();
		$status = $this->zip->open($filePath);
		if($status === true) {
			$this->parse();
		} else {
			throw new Exception("Failed to open $filePath with zip error code: $status");
		}
	}

	// get a file from the zip
	protected function getEntryData($name) {
		$data = $this->zip->getFromName($name);
		if($data === false) {
			throw new Exception("File $name does not exist in the Excel file");
		} else {
			return $data;
		}
	}

	// extract the shared string and the list of sheets
	protected function parse() {
		$sheets = array();
		$relationshipsXML = simplexml_load_string($this->getEntryData("_rels/.rels"));
		foreach($relationshipsXML->Relationship as $rel) {
			if($rel['Type'] == self::SCHEMA_OFFICEDOCUMENT) {
				$workbookDir = dirname($rel['Target']) . '/';
				$workbookXML = simplexml_load_string($this->getEntryData($rel['Target']));
				foreach($workbookXML->sheets->sheet as $sheet) {
					$r = $sheet->attributes('r', true);
					$sheets[(string)$r->id] = array(
						'sheetId' => (int)$sheet['sheetId'],
						'name' => (string)$sheet['name']
					);

				}
				$workbookRelationsXML = simplexml_load_string($this->getEntryData($workbookDir . '_rels/' . basename($rel['Target']) . '.rels'));
				foreach($workbookRelationsXML->Relationship as $wrel) {
					switch($wrel['Type']) {
						case self::SCHEMA_WORKSHEETRELATION:
							$sheets[(string)$wrel['Id']]['path'] = $workbookDir . (string)$wrel['Target'];
							break;
						case self::SCHEMA_SHAREDSTRINGS:
							$sharedStringsXML = simplexml_load_string($this->getEntryData($workbookDir . (string)$wrel['Target']));
							foreach($sharedStringsXML->si as $val) {
								if(isset($val->t)) {
									$this->sharedStrings[] = (string)$val->t;
								} elseif(isset($val->r)) {
									$this->sharedStrings[] = xlsreader_worksheet::parseRichText($val);
								}
							}
							break;
					}
				}
			}
		}
		$this->sheetInfo = array();
		foreach($sheets as $rid=>$info) {
			$this->sheetInfo[$info['name']] = array(
				'sheetId' => $info['sheetId'],
				'rid' => $rid,
				'path' => $info['path']
			);
		}
	}

	// returns an array of sheet names, indexed by sheetId
	public function getSheetNames() {
		$res = array();
		foreach($this->sheetInfo as $sheetName=>$info) {
			$res[$info['sheetId']] = $sheetName;
		}
		return $res;
	}

	public function getSheetCount() {
		return count($this->sheetInfo);
	}

	// instantiates a sheet object (if needed) and returns an array of its data
	public function getSheetData($sheetNameOrId) {
		$sheet = $this->getSheet($sheetNameOrId);
		return $sheet->getData();
	}

	// instantiates a sheet object (if needed) and returns the sheet object
	public function getSheet($sheet) {
		if(is_numeric($sheet)) {
			$sheet = $this->getSheetNameById($sheet);
		} elseif(!is_string($sheet)) {
			throw new Exception("Sheet must be a string or a sheet Id");
		}
		if(!array_key_exists($sheet, $this->sheets)) {
			$this->sheets[$sheet] = new xlsreader_worksheet($this->getSheetXML($sheet), $sheet, $this);

		}
		return $this->sheets[$sheet];
	}

	public function getSheetNameById($sheetId) {
		foreach($this->sheetInfo as $sheetName=>$sheetInfo) {
			if($sheetInfo['sheetId'] === $sheetId) {
				return $sheetName;
			}
		}
		throw new Exception("Sheet ID $sheetId does not exist in the Excel file");
	}

	protected function getSheetXML($name) {
		return simplexml_load_string($this->getEntryData($this->sheetInfo[$name]['path']));
	}

	// converts an Excel date field (a number) to a unix timestamp (granularity: seconds)
	public static function toUnixTimeStamp($excelDateTime) {
		if(!is_numeric($excelDateTime)) {
			return $excelDateTime;
		}
		$d = floor($excelDateTime); // seconds since 1900
		$t = $excelDateTime - $d;
		return ($d > 0) ? ( $d - 25569 ) * 86400 + $t * 86400 : $t * 86400;
	}

}

class xlsreader_worksheet {
	protected $workbook;
	public $sheetName;
	protected $data;
	public $colCount;
	public $rowCount;
	protected $config;

	public function __construct($xml, $sheetName, xlsreader_class $workbook) {
		$this->config = $workbook->config;
		$this->sheetName = $sheetName;
		$this->workbook = $workbook;
		$this->parse($xml);
	}

	// returns an array of the data from the sheet
	public function getData() {
		return $this->data;
	}

	protected function parse($xml) {
		$this->parseDimensions($xml->dimension);
		$this->parseData($xml->sheetData);
	}

	protected function parseDimensions($dimensions) {
		$range = (string) $dimensions['ref'];
		$cells = explode(':', $range);
		$maxValues = $this->getColumnIndex($cells[1]);
		$this->colCount = $maxValues[0] + 1;
		$this->rowCount = $maxValues[1] + 1;
	}

	protected function parseData($sheetData) {
		$rows = array();
		$curR = 0;
		$lastDataRow = -1;
		foreach ($sheetData->row as $row) {
			$rowNum = (int)$row['r'];
			if($rowNum != ($curR + 1)) {
				$missingRows = $rowNum - ($curR + 1);
				for($i=0; $i < $missingRows; $i++) {
					$rows[$curR] = array_pad(array(),$this->colCount,null);
					$curR++;
				}
			}
			$curC = 0;
			$rowData = array();
			foreach ($row->c as $c) {
				list($cellIndex,) = $this->getColumnIndex((string) $c['r']);
				if($cellIndex !== $curC) {
					$missingCols = $cellIndex - $curC;
					for($i=0;$i<$missingCols;$i++) {
						$rowData[$curC] = null;
						$curC++;
					}
				}
				$val = $this->parseCellValue($c);
				if(!is_null($val)) {
					$lastDataRow = $curR;
				}
				$rowData[$curC] = $val;
				$curC++;
			}
			$rows[$curR] = array_pad($rowData, $this->colCount, null);
			$curR++;
		}
		if($this->config['removeTrailingRows']) {
			$this->data = array_slice($rows, 0, $lastDataRow + 1);
			$this->rowCount = count($this->data);
		} else {
			$this->data = $rows;
		}
	}

	protected function getColumnIndex($cell = 'A1') {
		if (preg_match("/([A-Z]+)(\d+)/", $cell, $matches)) {

			$col = $matches[1];
			$row = $matches[2];
			$colLen = strlen($col);
			$index = 0;

			for ($i = $colLen-1; $i >= 0; $i--) {
				$index += (ord($col{$i}) - 64) * pow(26, $colLen-$i-1);
			}
			return array($index-1, $row-1);
		}
		throw new Exception("Invalid cell index");
	}

	protected function parseCellValue($cell) {
		// $cell['t'] is the cell type
		switch ((string)$cell["t"]) {
			case "s": // Value is a shared string
				if ((string)$cell->v != '') {
					$value = $this->workbook->sharedStrings[intval($cell->v)];
				} else {
					$value = '';
				}
				break;
			case "b": // Value is boolean
				$value = (string)$cell->v;
				if ($value == '0') {
					$value = false;
				} else if ($value == '1') {
					$value = true;
				} else {
					$value = (bool)$cell->v;
				}
				break;
			case "inlineStr": // Value is rich text inline
				$value = self::parseRichText($cell->is);
				break;
			case "e": // Value is an error message
				if ((string)$cell->v != '') {
					$value = (string)$cell->v;
				} else {
					$value = '';
				}
				break;
			default:
				if(!isset($cell->v)) {
					return null;
				}
				$value = (string)$cell->v;

				// Check for numeric values
				if (is_numeric($value)) {
					if ($value == (int)$value) $value = (int)$value;
					elseif ($value == (float)$value) $value = (float)$value;
					elseif ($value == (double)$value) $value = (double)$value;
				}
		}
		return $value;
	}

	// returns the text content from a rich text or inline string field
    public static function parseRichText($is = null) {
        $value = array();
        if (isset($is->t)) {
            $value[] = (string)$is->t;
        } else {
            foreach ($is->r as $run) {
                $value[] = (string)$run->t;
            }
        }
        return implode(' ', $value);
    }
}
?>