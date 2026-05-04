<?php
class map_item_class {
/*
type: integer, float, numeric, decimal:n percent:n date datetime timestamp uppercase lowercase boolean crlf map
decimal: decimal|percent:decimal
required: yes / no / key
field_map: table->constant->field
*/
	var $type;
    var $decimal;
    var $required;
    var $comment;
    var $external;
    var $title;
    var $field_map=array();
    var $export=TRUE;
    var $fetch=FALSE;
    var $xls;
    function __construct($type,$comment="",$required=FALSE) {
    	$type_array=explode(":",$type);
    	$this->type=$type_array[0];
        $this->decimal=intval($type_array[1]);
        $this->comment=$comment;
        $this->required=$required;
    }
    function xls($name,$table,$field,$width=0,$type="") {
		$this->xls=new xls_field_class($name,$table,$field,$width,$type);
    }
}
class xls_field_class {
	var $name;
    var $table;
	var $field;
    var $type;
    var $decimal=0;
    var $width;
    function __construct($name,$table,$field,$width=0,$type="") {
		$this->name=$name;
        $this->table=$table;
        $this->field=$field;
		$this->width=$width;
        list($this->type,$this->decimal)=explode(":",$type);
        $this->decimal=intval($this->decimal);
    }
}
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
    var $function_list=array("download"=>"WWW => Desktop","template"=>"WWW Template => Desktop","preview"=>"Desktop => WWW (Preview only)","upload"=>"Desktop => WWW (Update)");
    var $file_type_list=array(".xls"=>"Excel (xls)",".txt"=>"Tab Delimited Text (txt)",".csv"=>"Comma Separated Values (csv)");
    var $delimiter;
    var $report;
    var $fhw;
    var $fhr;
    function scs_version() {
    	$results=array();
        $results['05/21/214']="Add table::export_query";
        $results['04/16/214']="Add map: title";
        $results['11/20/2013']="Add XLS option";
        $results['07/25/2013']="Add upload_open, upload_close";
        $results['05/09/2013']="Add export_data, import_review methods";
        $results['11/26/2012']="General cleanup";
        $results['10/08/2012']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
    function __construct($table,$report,$group="",$code="") {
    global $database;
		ob_start();
        if (!class_exists("xls_class")) unset ($this->file_type_list['.xls']);
		if (!$table) return;
		require_once("classes/" . $table . ".php");
		$map_class_name="{$table}_map_class";
		$database->$table->map=new $map_class_name($report);
        $database->$table->map->group=$group;
        $database->$table->map->code=$code;
    	$this->report=$report;
		$database->temp=new database_temp_class();
		$this->table=$table;
        $this->group=$group;
        $this->code=$code;
        if (!class_exists("xls_class")) unset($this->file_type_list[".xls"]);
        $this->report['file_type']=$_POST['file_type'];
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
		if (method_exists($table->map,"omit_field")) $table->map->omit_field($group,$code);
        if (method_exists($table->map,"options")) $table->map->options($group,$code);
        if (!is_array($table->map->omit_field)) $table->map->omit_field=array();
    }
    function export($query="") {
    global $database;
        $table=$database->{$this->table};
        $this->external_file="{$this->table}";
        if ($this->group) $this->external_file .= "_" . strtolower($this->group);
        if ($this->code) $this->external_file .= "_" . strtolower($this->code);
		$this->external_file .= $this->report['file_type'];
	    $this->update_results[]="Table: {$this->table}";
	    if ($this->group) $this->update_results[]="Group: {$this->group}";
	    if ($this->code) $this->update_results[]="Code: {$this->code}";
	    $this->update_results[]="File: " . $this->external_file;
        $fields=array();
        foreach ($table->map->field as $field => $map) {
            switch (TRUE) {
			  case (!$_POST['option_selected']):
              case ($map->required):
              case ($_POST["map_" . $field]):
                $fields[]=$field;
                $table->map->field[$field]->export=TRUE;
                break;
              default:
                $table->map->field[$field]->export=FALSE;
            }
        }
		$this->update_results[]="Fields: " . implode(", ",$fields);
        switch (TRUE) {
          case ($query):
			break;
		  case (method_exists($table,"export_query")):
        	$query=$table->export_query();
			break;
		  default:
			$query="select * from " . $this->table;
		}
		$table->query($query);
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
        $xls->worksheet("Data",$table->map->field);
	    while ($table->fetch=$table->fetch_array()) {
	        $table->fetch();
	        $xls->row($this->export_data($table));
	    }
		$xls->write();
    }
    function export_txt() {
    global $database;
        $table=$database->{$this->table};
        if (!ob_get_contents()) {
	        header("Content-type: text/plain");
	        header("Content-Disposition: attachment; filename=" . $this->external_file);
	        header("Pragma: no-cache");
	        header("Expires: 0");
		}
	    $fh = fopen("php://output", "w");
        if (!$this->delimiter) $this->delimiter="\t";
	    fputcsv($fh,$this->export_header($table),$this->delimiter,"\"");
	    while ($table->fetch = $table->fetch_array()) {
	        $table->fetch();
	        fputcsv($fh,$this->export_data($table),$this->delimiter,"\"");
	    }
	    fclose($fh);
    }
	function export_header($table) {
    	$results=array();
        foreach ($table->map->field as $field => $map) {
        	switch (TRUE) {
              case (!$map->export):
              	break;
              case ($map->title):
				$results[]=$map->title;
                break;
              default:
              	$results[]=$field;
			}
        }
        return $results;
    }
	function export_data($table) {
		$results=array();
        if (method_exists($table,"export_data")) $table->export_data();
        foreach ($table->map->field as $field => $map) {
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
	        switch ($map->type) {
	          case "integer":
	            $results[$field]=(intval($data));
                break;
	          case "float":
	            $results[$field]=(floatval($data));
                break;
	          case "decimal":
	          case "numeric":
	            $results[$field]=number_format($data,$map->decimal,".","");
                break;
	          case "percent":
	            $results[$field]=number_format(($data * .01),$map->decimal,".","") . "%";
                break;
	          case "date":
	            $results[$field]=fn_date($data,"ymd");
                break;
	          case "datetime":
	          case "timestamp":
	            $results[$field]=$data;
                break;
	          case "crlf":
	            $results[$field]=str_ireplace("\r\n","<br>",$data);
                break;
	          case "boolean":
              	if ($data) {
	                $results[$field]="Y";
				  } else {
	                $results[$field]="N";
	            }
                break;
	          default:
	            $results[$field]=$data;
	        }
        }
        return $results;
    }
    function export_html($title,$count=0) {
    global $database, $forms;
	    foreach ($forms->report as $key => $value) {
			if ($key != "action") print $forms->hidden($key,$value);
	    }
	    print "<script language=javascript>\n";
	    print "function fn_checked(checked) { \n";
	    print "  for(i=0; i< document.scs_form.elements.length; i++) {\n";
	    print "    field=document.scs_form.elements[i].name;\n";
	    print "    if (field.substring(0,4)=='map_') document.getElementById(field).checked=checked;\n";
	    print "    }\n";
	    print "  }\n";
	    print "</script>\n";
        $table=$database->{$this->table};
        print $forms->hidden("option_selected",1);
        print "<p class=standard><b>$title</b>\n";
		print "<br>Select all? " . $forms->checkbox("option_checked",1,1,array("onchange"=>"fn_checked(this.checked);")) . "</p>";
    	print "<table class='standard border'>\n";
        print "<tr>\n";
        print "<th>Include?</th>\n";
        print "<th>Field name</th>\n";
        print "<th>Type</th>\n";
        print "<th>Comment</th>\n";
        print "</tr>\n";
        foreach ($table->map->field as $field => $map) {
			if (in_array($field,$table->map->omit_field)) continue;
        	$field_name="map_" . $field;
            if ($map->required) {
            	$background=$forms->background->darkseagreen;
			  } else {
              	$background="";
			}
        	print "<tr $background>\n";
            print "<td class=center>";
            if ($map->required) {
            	print "&nbsp;";
			  } else {
				print $forms->checkbox($field_name,1,1);
            }
            print "</td>\n";
            print "<td>$field</td>\n";
            print "<td>" . $map->type . "</td>\n";
            print "<td>" . $map->comment . "</td>\n";
            print "</tr>\n";
        }
        print "</table>\n";
	    if ($count) {
	        $text=number_format($count) . " records will be downloaded";
	      } else {
	        $text="All records will be downloaded";
	    }
	    print
        	"<p class=center>\n" .
            $forms->button("Return",array("onclick"=>"fn_action('cancel');")) . " " .
            $forms->button($text,array("onclick"=>"fn_action('update');")) . "</p>\n";
    }
    function template() {
    global $database;
        $table=$database->{$this->table};
        foreach ($table->map->field as $field => $map) {
			if (in_array($field,$table->map->omit_field)) $table->map->field[$field]->export=FALSE;
		}
        $external_file="{$this->table}";
        if ($this->group) $external_file .= "_" . strtolower($this->group);
		$external_file .= "_template" . $this->report['file_type'];
	    header("Content-type: text/plain");
	    header("Content-Disposition: attachment; filename=$external_file");
	    header("Pragma: no-cache");
	    header("Expires: 0");
	    $fh = fopen("php://output", "w");
	    fputcsv($fh,$this->export_header($table),$this->delimiter,"\"");
	    fclose($fh);
	    die;
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
        while ($line = fgetcsv($this->fhr,0,$this->delimiter,"\"")) {
            fputcsv($this->fhw,$this->upload_line($line),"\t","\"");
        }
        fclose($this->fhw);
		$this->upload_close();
        switch (TRUE) {
          case (sizeof($this->errors)):
			$this->message="Error(s) found in import file";
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
	    foreach($data as $value) {
        	$value=trim($value);
	        if (strlen($value)) {
				$empty_row=FALSE;
            	break;
			}
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
            foreach ($table->map->field as $field => $map) {
            	if (isset($map->column)) $results[$field]=$this->upload_value($data[$map->column],$map);
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
    function upload_header($data) {
    global $database;
        $table=$database->{$this->table};
		$this->headers=TRUE;
        $xref=array();
	    foreach ($table->map->field as $field => $map) {
	        if ($map->title) {
	            $xref[strtolower($map->title)]=$field;
	          } else {
	            $xref[$field]=$field;
			}
	    }
        foreach ($data as $column => $name) {
			$name=strtolower($name);
            if (!array_key_exists($name,$xref)) continue;
			$field=$xref[$name];
            switch (TRUE) {
              case (!isset($table->map->field[$field]->column)):
                $table->map->field[$field]->column=$column;
                break;
               default:
                $table->map->field[$field]->duplicate=TRUE;
            }
        }
        foreach ($data as $column => $name) {
			$field=strtolower($name);
            switch (TRUE) {
              case (!array_key_exists($field,$table->map->field)):
                break;
              case (!isset($table->map->field[$field]->column)):
                $table->map->field[$field]->column=$column;
                break;
               default:
                $table->map->field[$field]->duplicate=TRUE;
            }
        }
		$table->map->complete=TRUE;
        foreach ($table->map->field as $field => $map) {
			if ((!isset($map->column)) && (!in_array($field,$table->map->omit_field))) $table->map->complete=FALSE;
            switch (TRUE) {
              case (isset($map->column)):
				$results[]=$field;
              	break;
              case (!$map->required):
                break;
              case (!isset($map->column)):
                $this->errors[$field]="Missing";
                break;
              case ($map->duplicate):
                $this->errors[$field]="Duplicate field";
                break;
            }
        }
		return $results;
    }
    function upload_value($value,$map) {
		switch ($map->type) {
          case "integer":
          case "float":
          case "numeric":
        	return fn_number($value);
          case "decimal":
        	return number_format(fn_number($value),$map->decimal,".","");
          case "percent":
          	if (strpos($value,"%")) {
				$perecnt=fn_number($value * .01);
              } else {
				$percent=fn_number($value);
            }
        	return number_format($percent,$map->decimal);
          case "date":
          	$date=fn_date($value,"mdy");
            if (!$date) {
            	return "0000/00/00";
              } else {
				return $date;
			}
          case "datetime":
          case "timestamp":
			return fn_date($value,"timestamp");
          case "uppercase":
          	return strtoupper($value);
          case "lowercase":
          	return strtolower($value);
          case "boolean":
			switch (TRUE) {
              case (strtolower($value)=="n"):
              case (strtolower($value)=="no"):
              case ($value=="0"):
            	return 0;
			  default:
              	return 1;
			}
          case "crlf":
			return str_ireplace("<br>","\r\n",$value);
          case "map":
			foreach ($map->field_map as $map) {
				if (strtolower($value) == strtolower($map)) return $map;
            }
            return "";
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
	    foreach ($forms->report as $key => $value) {
			if ($key != "action") print $forms->hidden($key,$value);
	    }
		print $forms->hidden("external_file",$this->external_file);
        $table=$database->{$this->table};
        $missing_count=0;
	    print "<p class='standard left'>Records in table: " . number_format($this->line_count);
        print "<br>Records accepted for update: " .  number_format($this->update_count);
		print "<br>Empty & header records: " . number_format(($this->empty_count + 1));
        if ($this->reject_count) print "<br>Records rejected: " .  number_format($this->reject_count);
        print "</p>\n";
	    print "<table class='small border'>\n";
	    print "<tr>\n";
	    print "<th>Field</th>\n";
	    print "<th>Required?</th>\n";
	    print "<th>Column#</th>\n";
	    print "<th>Type</th>\n";
	    print "<th>Error</th>\n";
	    print "</tr>\n";
	    foreach ($table->map->field as $field => $map) {
	        switch (TRUE) {
	          case (isset($this->errors[$field])):
	            $background=$forms->background->red;
                $missing_count++;
	            break;
	          case (isset($map->column)):
	            $background="";
	            break;
	          default:
	            $background=$forms->background->yellow;
                $missing_count++;
	        }
            if (!in_array($field,$table->map->omit_field)) {
	            print "<tr $background>\n";
                if ($map->title) {
                	$text=$map->title;
                  } else {
                  	$text=$field;
                }
	            print "<td>$text</td>\n";
	            print "<td class=center>";
	            if ($map->required) {
	                print "Yes";
	              } else {
	                print "No";
	            }
	            print "</td>\n";
	            print "<td class=center>";
	            if (isset($map->column)) {
	                print ($map->column + 1);
	              } else {
	                print "Omitted";
	            }
	            print "</td>\n";
	            print "<td class=center>" . $map->type . "</td>\n";
	            print "<td>" . $this->errors[$field] . "</td>\n";
	            print "</tr>\n";
			}
	    }
	    print "</table>\n";
	    print "<p class='left'>" . $forms->button("Cancel",array("onclick"=>"fn_action('cancel');"));
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
		if ($text) print " " . $forms->button($text,array("onclick"=>"fn_action('update');"));
	    print "</p>\n";
		if ($missing_count) {
	        print "<p class='standard center'>Legend: ";
	        print "<span style='background: #ffff66; color: #000000;'>&nbsp;&nbsp;Missing, required to add&nbsp;&nbsp;</span>\n";
	        print "&nbsp;&nbsp;<span style='background: #ff0000; color: #000000;'>&nbsp;&nbsp;Defined multiple times&nbsp;&nbsp;</span>\n";
	        print "</p>\n";
		}
    }
	function import() {
    global $database;
		$this->external_file=$_POST['external_file'];
		$table=$database->{$this->table};
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
        foreach ($table->map->field as $field => $map) {
			if (isset($map->column)) $fields[]=$field;
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
        	$this->upload_header($data);
			break;
          default:
			$results=array();
            foreach ($table->map->field as $field => $map) {
            	if (isset($map->column)) {
                	$results[$field]=$data[$map->column];
                }
            }
            $errors=$table->import_update($results,$this->line_count,$this->report['function']);
			if (!sizeof($errors)) {
            	$this->update_count++;
			  } else {
            	$this->errors[$this->line_count]=$errors;
			}
		}
    }
    function import_html() {
    global $forms;
	    foreach ($forms->report as $key => $value) {
			if ($key != "action") print $forms->hidden($key,$value);
	    }
		print $forms->hidden("external_file",$this->external_file);
		print "<p class=standard>" . implode("<br>",$this->update_results) . "</p>\n";
	    print "<p class=standard>" . $forms->button("Return",array("onclick"=>"fn_action('');")) . "</p>\n";
        if (sizeof($this->errors)) {
        	print "<table class='small border'>\n";
            print "<tr>\n";
            print "<th>Line#</th>\n";
            print "<th>Field</th>\n";
            print "<th>Value</th>\n";
            print "</tr>\n";
            foreach ($this->errors as $line => $error) {
            	$count=0;
                foreach ($error as $field => $field_error) {
	                print "<tr>\n";
	                if (!$count) print "<td class=center rowspan=" . sizeof($error) . ">$line</td>\n";
	                print "<td>$field</td>\n";
	                print "<td>$field_error</td>\n";
	                print "</tr>\n";
	                $count++;
				}
            }
            print "</table>\n";
		}
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
}
?>