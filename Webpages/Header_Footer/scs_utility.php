<?php
include_once "scs_header.php";
include_once "scs_map.php";
include_once "classes/document.php";
$menu->access("Administrator");
$database->connect();
$menu->title="Competitor SKU Utility";
$menu->messages[]=$menu->title;

$function_list=array("overwrite"=>"Replace All Items (Desktop => WWW)","export"=>"Download (WWW => Desktop)");
$format_list=array(".txt",".csv");
$report['action']=$_POST['action'];
$report['function']=$_POST['function'];
$report['table']="inventory_xref";
$report['format']=$_POST['format'];
$report['import_file']=$_POST['import_file'];
if ($report['table']) {
	include_once "classes/" . $report['table'] . ".php";
	include_once "classes/" . $report['table'] . "_map.php";
	$table=$database->$report['table'];
}
$report['import_file_id']="scs.tmp";

if ($report['format']==".txt") {
    $field_delimiter="\t";
  } else {
    $field_delimiter=",";
}
switch (TRUE) {
  case (!$report['action']):
	break;
  case (($report['action']=="review") && ($report['function']=="export")):
	foreach ($_POST as $key => $value) {
		list($type,$field)=split("\t",base64_decode($key));
		if (($type=="field") && (isset($table->map->field[$field]))) {
			$table->map->field[$field]->export=TRUE;
        }
    }
    $fh=fopen($report['import_file_id'],"w");
    fputcsv($fh,$table->map->export_header(),$field_delimiter,"\"");
    $table->query($table->map->export_query());
    while ($table->fetch = mysql_fetch_array($table->meta->handle)) {
		$table->export_fetch();
        fputcsv($fh,$table->map->export_data($table),$field_delimiter,"\"");
    }
    $table->free_result();
    fclose($fh);
    header("Content-type: text/plain");
    header("Content-Disposition: attachment; filename=\"" . $report['table'] . $report['format'] . "\"");
    readfile($report['import_file_id']);
    unlink($report['import_file_id']);
    die;
  case ($report['action']=="review"):
    switch (TRUE) {
      case (!$_FILES['import_file']['size']):
        $menu->errors['import_file']=$menu->background['red'];
        break;
      case ($_FILES['import_file']['error']):
        $menu->errors['import_file']=$menu->background['red'];
        $menu->errors_text['import_file']="Error: " . $_FILES["import_file"]["error"];
        break;
      case ($_FILES['import_file']['type'] == "text/plain"):
      case (substr(strtolower($_FILES['import_file']['name']),-4) == $report['format']):
		$report['import_file']=$_FILES['import_file']['name'];
        if (file_exists($report['import_file_id'])) unlink($report['import_file_id']);
		$competitor=array();
	    $temp_fh=fopen($_FILES['import_file']['tmp_name'],"r");
		$fh=fopen($report['import_file_id'],"w");
	    $first_pass=TRUE;
        $data=array("sku","competitor","competitor_sku");
		$table->map->import_line($data);
		fputcsv($fh,$data,$field_delimiter,"\"");
		while ($line = fgetcsv($temp_fh,0,$field_delimiter,"\"")) {
	        $sku="";
	        foreach ($line as $key => $input_value) {
	            $value=strtoupper(trim(str_replace("\n"," ",$input_value)));
	            if ($value=="-") $value="";
	            switch (TRUE){
	              case (!$value):
	                break;
	              case ($first_pass):
	                $competitor[$key]=$value;
	                break;
	              case (!$competitor[$key]):
                  case ($key==1):
	                break;
	              case (!$key):
	                $sku=$value;
	                break;
	              case ($sku):
                  	$data=array($sku,$competitor[$key],$value);
					fputcsv($fh,$data,$field_delimiter,"\"");
					$table->map->import_line($data);
	                break;
	            }
	        }
	        $first_pass=FALSE;
	    }
		fclose($temp_fh);
		fclose($fh);
        $table->map->import_review();
        if ($report['function']=="overwrite") $menu->messages[]="<b>All records will be replaced with uploaded file</b>";
        $menu->messages[]="Review your selection";
        break;
       default:
        $menu->errors['import_file']=$menu->background['red'];
        $menu->errors_text['import_file']="File type cannot be <b>" . $_FILES['import_file']['type'] . "</b>";
    }
	break;
  case (($report['action']=="update") && (!file_exists($report['import_file_id']))):
	$menu->messages[]="File already updated";
	$menu->errors['update']=TRUE;
	break;
  case ($report['action']=="update"):
    $fh=fopen($report['import_file_id'],"r");
	while ($line = fgetcsv($fh,0,$field_delimiter,"\"")) {
		$table->import_update($table->map->import_line($line,TRUE));
    }
    fclose($fh);
    if ($report['function']=="overwrite") $table->query("delete from " . $report['table'] . " where update_timestamp <> " . fn_escape($table->map->update_timestamp));
    $menu->messages[]="Import file: " . $report['import_file'];
    $menu->messages[]=number_format($table->map->line_count - sizeof($table->map->line_errors)) . " record(s) updated";
    if (sizeof($table->map->line_errors)) {
    	$menu->messages[]=number_format(sizeof($table->map->line_errors)) . " lines contained error(s)";
    }
    unlink($report['import_file_id']);
	break;
}

$menu->head();
$menu->message();
/* Obsolete code

function fn_import_export() {
	if (document.scs_form.function.value == "export") {
    	display=false;
	  } else {
      	display=true;
	}
    fn_display("import_file_title",display);
    fn_display("import_file_field",display);
}
function fn_display(id,display) {
    if (display) {
    	document.getElementById(id).style.display="block";
	  } else {
    	document.getElementById(id).style.display="none";
	}
}
function initial_verify() {
	switch (true) {
      case (document.scs_form.function.value == "export"):
        break;
      case (!document.scs_form.import_file.value):
      	alert("Upload file name required");
		document.scs_form.import_file.focus();
        return;
    }
	fn_action('review');
}

*/
?>
<script type="text/javascript">
function review_overwrite() {
     if (confirm('Replace all records with import file?')) {
     	fn_action('update');
     }
}
function fn_action(action) {
	document.scs_form.action.value=action;
	document.scs_form.submit();
}
</script>
<?php
print "<form name=scs_form enctype='multipart/form-data' method=post action='" . $_SERVER['PHP_SELF'] . "'>\n";
print "<input type=hidden name=action>\n";
if ($report['action']) {
	print "<input type=hidden name=table value='" . $report['table'] . "'>\n";
	print "<input type=hidden name=format value='" . $report['format'] . "'>\n";
}
switch (TRUE) {
  case (($report['action']=="update") && (!sizeof($menu->errors))):
	print "<input type=hidden name=function value='" . $report['function'] . "'>\n";
    print "<table class='small border'>\n";
    print "<tr>\n";
    print "<th>Line#</th>\n";
    if (sizeof($table->map->alternate_name)) print "<th>Import field</th>\n";
    print "<th>Database field</th>\n";
    print "<th>Error</th>\n";
    print "</tr>\n";
    foreach ($table->map->line_errors as $line => $line_error) {
        $first_pass=FALSE;
        foreach ($line_error as $field => $error) {
            print "<tr>\n";
            if (!$first_pass) {
                $first_pass=TRUE;
                print "<td class=center rowspan=" . sizeof($line_error) . ">" . ($line + 1) . "</td>\n";
            }
            if (sizeof($table->map->alternate_name)) print "<td>" . $table->map->field[$field]->alternate_name . "</td>\n";
            print "<td>$field</td>\n";
            print "<td>$error</td>\n";
            print "</tr>\n";
        }
        print "</table>\n";
    }
    print "<p class=center><input type=button class=nav_button value='&lt; Return' onclick=\"javascript:fn_action('');\"></p>\n";
	break;
  case (($report['action']=="review") && (!sizeof($menu->errors))):
	print "<input type=hidden name=function value='" . $report['function'] . "'>\n";
	print "<input type=hidden name=import_file value='" . $report['import_file'] . "'>\n";
	print "<table class='small border'>\n";
    print "<caption class='standard left'><br>File name: <b>" . $report['import_file'] . "</b></caption>\n";
	print "<tr>\n";
	if (sizeof($table->map->alternate_name)) print "<th>Import field</th>\n";
	print "<th>Database field</th>\n";
    print "<th>Required?</th>\n";
    print "<th>Column#</th>\n";
    print "<th>Type</th>\n";
    print "<th>Error</th>\n";
    print "</tr>\n";
    foreach ($table->map->field as $field => $map) {
		switch (TRUE) {
          case (isset($table->map->table_errors[$field])):
          	$background=$menu->background['yellow'];
            break;
          case (isset($map->column)):
          	$background=$menu->background['greenbar'];
            break;
          default:
          	$background="";
		}
		print "<tr $background>\n";
		if (sizeof($table->map->alternate_name)) print "<td>" . $table->map->field[$field]->alternate_name . "</td>\n";
        print "<td>$field</td>\n";
        print "<td class=center>" . $map->required . "</td>\n";
        print "<td class=center>";
        if (isset($map->column)) {
			print ($map->column + 1);
		  } else {
          	print "Omitted";
		}
		print "</td>\n";
        print "<td class=center>" . $map->type . "</td>\n";
        print "<td>" . $table->map->table_errors[$field] . "</td>\n";
        print "</tr>\n";
    }
    print "</table>\n";
    print "<p class=center>" . number_format($table->map->line_count). " record(s) to import";
    if (sizeof($table->map->table_errors)) print "<br>Please correct error(s)";
    print "</p>\n";
    print "<p class=center><input type=button class=nav_button value='&lt; Back' onclick=\"javascript:fn_action('');\">\n";
    if ($report['function']=="overwrite") {
    	$script="javascript:review_overwrite('update');";
	  } else {
      	$script="javascript:fn_action('update');";
    }
    if ($table->map->update) print "<input type=button class=nav_button value='Next &gt;' onclick=\"$script\">\n";
    print "</p>\n";
    break;
  default:
  	print "<table class='small noborder'>\n";
    print "<tr>\n";
    print "<th>Action</th>\n";
    print "<th id=import_file_title>Upload File Name</th>\n";
    print "<th>Format</th>\n";
    print "<th>&nbsp;</th>\n";
    print "</tr>\n";
    print "<tr>\n";
	print "<td class=center>" . $menu->select("function",$function_list,$report['function'],FALSE,"fn_import_export();") . "</td>\n";
	print "<td class=center id=import_file_field><input type=file name=import_file id=import_file size=40 " . $menu->errors['import_file'] . ">";
    if ($menu->errors_text['import_file']) print "<br>" . $menu->errors_text['import_file'];
    print "</td>\n";
	print "<td class=center>" . $menu->select("format",$format_list,$report['format'],FALSE) . "</td>\n";
//    print "<td><input type=button class=nav_button value='Go!' onclick=\"javascript:initial_verify();\"></td>\n";
    print "<td><input type=button class=nav_button value='Go!' onclick=\"javascript:fn_action('review');\"></td>\n";
    print "</tr>\n";
    print "</table>\n";
/*
?>
<script type="text/javascript">
fn_import_export();
</script>
<?php
*/
}
print "</form>\n";
$database->disconnect();
$menu->copyright();
?>