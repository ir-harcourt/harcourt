<?php
$forms->title("MySQL Toolbox (Revised 10/31/2018)");
set_time_limit(120);
if ( (function_exists("stripslashes_deep")) && ($mysql_stripslashes) ) $_POST=array_map('stripslashes_deep', $_POST);
$forms->report['action']=$_POST['action'];
$forms->report['function']=$_POST['function'];
$forms->report['database']=$_POST['database'];
$forms->report['query_results']=$_POST['query_results'];
$forms->report['upload_name']=$_POST['upload_name'];
$forms->report['backup_name']=trim($_POST['backup_name']);
$forms->report['query']=trim($_POST['query']);
$forms->report['upload_temp']="scs_mysqltoolbox.tmp";
$forms->report['upload_sql']="scs_mysqltoolbox.sql";
$forms->report['server']=$database->server();

$forms->constant->function_list=array();
if ( ($forms->report['database']) && ($forms->report['database'] != $database->meta->database) ) {
	$forms->constant->function_list[]="Tables";
  } else {
	$forms->constant->function_list[]="";
	$forms->constant->function_list[]="Backup";
	$forms->constant->function_list[]="Repair";
	if ($mysql_query) {
	    $forms->constant->function_list[]="Query";
	    $forms->constant->function_list[]="Upload";
	}
}
$forms->constant->function_list[]="Server";
$forms->constant->database_list=array($database->meta->database);
$database->temp->query("show databases");
while ($database->temp->fetch = $database->temp->fetch_array()) {
	if ($database->temp->fetch['Database'] != $database->meta->database) $forms->constant->database_list[]=$database->temp->fetch['Database'];
}
if (!$forms->report['backup_name']) $forms->report['backup_name']=$forms->report['server']['database'] . ".sql";
$forms->constant->results=array();
$forms->constant->results['']="No output";
$forms->constant->results['display']="Display";
$forms->constant->results['txt']="Tab Delimited Text";

$database->temp=new database_temp_class();
$database->temp2=new database_temp_class();

$forms->constant->checked=array();
foreach ($_POST as $key => $item) {
	if (preg_match("/^checked_/",$key)) $forms->constant->checked[]=$item;
}
switch (TRUE) {
  case (!$forms->report['action']):
  case (!$forms->report['function']):
	break;
  case (($forms->report['function']=="Query") && ($forms->report['action']=="update")):
	$database->temp->meta->error_abort=FALSE;
	$database->temp->query($forms->report['query']);
	$database->temp->meta->error_abort=TRUE;
	if ($database->temp->meta->error) {
	    $query_message="<p>Database error #" . $database->temp->meta->errno . "</b><br>" . $database->temp->meta->error . "</p>\n";
	  } else {
	    $query_message=="Affected rows: " . number_format($database->affected_rows());
	}
	$results=array();
	$results[]=$forms->report['query'];
 	if ($database->temp->meta->error) {
		$forms->message[]="Error(s) found in query";
	    $query_message="<p>Database error #" . $database->temp->meta->errno . "</b><br>" . $database->temp->meta->error . "</p>\n";
        break;
	}
	$forms->message[]="Query executed successfully";
	$query_message="Affected rows: " . number_format($database->affected_rows());
	$results[]=$query_message;
    $results[]="Output: " . $forms->constant->results[ $forms->report['query_results'] ];
	$query_results=array();
    while ( $database->temp->fetch = $database->temp->fetch_array() ) {
        if (!sizeof($query_results)) {
            $line_results=array();
            foreach ($database->temp->fetch as $key => $value) {
				$line_results[]=$key;
            }
            $query_results[]=$line_results;
        }
        $line_results=array();
        foreach ($database->temp->fetch as $key => $value) {
			$line_results[]=$value;
        }
        $query_results[]=$line_results;
    }
    $database->temp->free_result();
	if ( $forms->report['query_results']=="txt") {
	    header("Content-type: text/plain");
	    header("Content-Disposition: attachment; filename=" . trim($_POST['query_file']));
	    header("Pragma: no-cache");
	    header("Expires: 0");
	    $fh=fopen("php://output", "w");
        foreach ($query_results as $contents) {
			fputcsv($fh,preg_replace("/\s/"," ",$contents),"\t","\"");
		}
		fclose($fh);
        die;
    }
	break;
  case ($forms->report['function']=="Query"):
	if (!$forms->report['query']) $forms->error('query');
	if (sizeof($forms->error)) break;
	break;
  case (($forms->report['function']=="Upload") && ($forms->report['action']=="update")):
	$database->temp->meta->error_abort=FALSE;
	$upload_sql=preg_split("/\n/",file_get_contents($forms->report['upload_sql']));
    $upload_results=array();
    $error_count=0;
	$results=array();
    $results[]="File name: " . $forms->report['upload_name'];
    foreach ($upload_sql as $key => $value) {
		if (!$value) continue;
	    $upload_results[$key]=new mysql_upload_class($key,$value);
	    $error_count=$upload_results[$key]->query($error_count);
        $results[]=$upload_results[$key]->results;
    }
    if ($error_count) {
    	$forms->message[]=$error_count . " Errors detected in query";
	  } else {
    	$forms->message[]="Queries executed successfully";
	}
	unlink($forms->report['upload_sql']);
	$database->temp->meta->error_abort=TRUE;
    break;
  case ($forms->report['function']=="Upload"):
	switch (TRUE) {
	  case (!$_FILES['upload']['name']):
	  case (!intval($_FILES['upload']['size'])):
		$forms->error('upload','Entry cannot be blank');
        break;
	  case (intval($_FILES['upload']['error'])):
		$forms->error('upload','Upload error #' . $_FILES['upload']['error']);
        break;
	  default:
        $forms->report['upload_name']=$_FILES['upload']['name'];
		if (file_exists($forms->report['upload_temp'])) unlink($forms->report['upload_temp']);
		if (file_exists($forms->report['upload_sql'])) unlink($forms->report['upload_sql']);
        move_uploaded_file($_FILES['upload']['tmp_name'],$forms->report['upload_temp']);
        $fh=fopen($forms->report['upload_sql'],"w");
		$contents=preg_split("/\n/",file_get_contents($forms->report['upload_temp']));
		unlink($forms->report['upload_temp']);
		$upload_results=array();
        $row=0;
	    foreach ($contents as $value) {
	        $value=trim($value);
	        switch (TRUE) {
	          case (!strlen($value)):
	          case (substr($value,0,2) == "--"):
	            continue;
	          default:
	            $upload_results[$row] .= " $value";
	            if (substr($value,-1,1) == ";") {
					fwrite($fh,$upload_results[$row] . "\n");
                    $row++;
                }
	        }
	    }
        fclose($fh);
    }
    break;
  default:
	if (!sizeof($forms->constant->checked)) $forms->error('function','',"No tables selected");
	if ( ($forms->report['function']=="Backup") && (!preg_match("/.sql$/i",$forms->report['backup_name'])) ) $forms->error('backup_name');
    if (sizeof($forms->error)) break;
	if ($forms->report['function']=="Backup") {
	    header("Content-type: text/plain");
	    header("Content-Disposition: attachment; filename=" . $forms->report['backup_name']);
	    header("Pragma: no-cache");
	    header("Expires: 0");
	    $fh = fopen("php://output", "w");
        $database->temp->query("select version() as 'version'");
		$database->temp->fetch();
		fwrite($fh,"--\n");
		fwrite($fh,"-- SCS MySQL Dump version: 10/10/2018\n");
		fwrite($fh,"-- MySQL version: " . $database->temp->fetch['version'] . "\n");
		fwrite($fh,"-- Host: " . $forms->report['server']['host'] . "\n");
		fwrite($fh,"-- Database: " . $forms->report['server']['database'] . "\n");
		fwrite($fh,"-- " . date("D M d, Y H:i:s\n"));
		fwrite($fh,"--\n");
	}
    foreach ($forms->constant->checked as $table) {
		switch ($forms->report['function']) {
		  case "Repair":
	        $query = "repair table `{$table}`";
	        $database->temp->query($query);
	        $comment="Table <b>$table</b> repaired";
	        $forms->message[]=$comment;
			break;
          case "Backup":
 	        $database->temp->query("show table status where Name='{$table}'");
	        $database->temp->fetch=$database->temp->fetch_array();
            $forms->constant->rows=$database->temp->fetch['Rows'];
 	        $database->temp->query("show create table `$table`");
	        $database->temp->fetch=$database->temp->fetch_array();
            $forms->constant->create=$database->temp->fetch['Create Table'];
            $forms->constant->fields=array();
	        $database->temp->query("show fields from `$table`");
	        while ($database->temp->fetch = $database->temp->fetch_array()) {
                $forms->constant->fields[]=$database->temp->fetch['Field'];
	        }
			fwrite($fh,"\n");
			fwrite($fh,"DROP TABLE IF EXISTS `$table`;\n");
			fwrite($fh,$forms->constant->create . ";\n");
			fwrite($fh,"LOCK TABLES `$table` WRITE; \n");
			fwrite($fh,"ALTER TABLE `$table` DISABLE KEYS; \n");
			fwrite($fh,"-- Records in table: {$table}:  " . number_format($forms->constant->rows) . "\n");
            $forms->constant->limit=1000;
            $forms->constant->count=0;
            $eof=0;
			do {
            	$query="select * from $table limit " . $forms->constant->count . " , " . $forms->constant->limit;
	        	$database->temp->query($query);
                $items=array();
	        	while ($database->temp->fetch = $database->temp->fetch_array()) {
	                $text=array();
	                foreach ($forms->constant->fields as $key => $field) {
	                    $text[]=fn_escape($database->temp->fetch[$field]);
	                }
                    $items[]="(" . implode(",",$text) . ")";
                }
				$forms->constant->count += $database->temp->meta->rows;
                if ( (sizeof($items)) ? fwrite($fh,"INSERT INTO `{$table}` VALUES " . implode(",",$items) . ";" . "\n") : $eof=1);
            } while (!$eof);
            $database->temp->free_result();
			fwrite($fh,"ALTER TABLE `$table` ENABLE KEYS;\n");
			fwrite($fh,"UNLOCK TABLES;\n");
			break;
		}
    }
	if ((in_array($forms->report['function'],array("Repair","Backup"))) && (method_exists($database->log,"ipc"))) {
		$database->log->ipc("MySQL: " . $forms->report['function'],implode(", ",$forms->constant->checked));
    }
    if ($forms->report['function']=="Backup") {
    	fclose($fh);
        die;
	}
}
$menu->head();
$forms->message();
?>
<script language=javascript>
document.addEventListener('DOMContentLoaded', function() {
	fn_function(document.getElementById('function').value);
});
function fn_action(action,query) {
	if (query) {
	    if (!confirm("Execute query?")) return;
    }
	document.scs_form.action.value=action;
	document.scs_form.submit();
}
function fn_database() {
	document.scs_form.action.value="";
	document.scs_form.function.value="";
	document.scs_form.submit();
}
function fn_checked(checked) {
	for(i=0; i< document.scs_form.elements.length; i++) {
		field=document.scs_form.elements[i].name;
		if (field.substring(0,8) == 'checked_') document.getElementById(field).checked=checked;
	}
}
function fn_function(text) {
	document.getElementById('function_lit').innerHTML=text;
    var show_table=0;
    var show_table_name=0;
    var show_query=0;
    var show_upload=0;
    var show_tables;
    var show_server=0;
    switch (text) {
      case 'Backup':
		show_table=1;
        show_table_name=1;
        break;
      case 'Repair':
		show_table=1;
        break;
      case 'Query':
		show_query=1;
        break;
      case 'Upload':
		show_upload=1;
        break;
      case 'Tables':
      	show_tables=1;
        break;
      case 'Server':
		show_server=1;
        break;
	}
	div_show('mysql_table',show_table);
	div_show('mysql_tables',show_tables);
	div_show('mysql_table_name',show_table_name);
	div_show('mysql_query',show_query);
	div_show('mysql_upload',show_upload);
	div_show('mysql_server',show_server);
}
function fn_create(id) {
	if (document.getElementById(id).style.display=="none") {
		div_show(id,1);
	  } else {
		div_show(id,0);
	}
}
</script>
<?php
print $forms->open(array("data"=>TRUE));
print $forms->hidden('action');
switch (TRUE) {
  case (($forms->report['function']=="Query") && ($forms->report['action']=="update")):
	print $forms->hidden("function",$forms->report['function']);
	print $forms->hidden("query_results",$forms->report['query_results']);
    print $forms->hidden("query",$forms->report['query']);
    print "<p><b>$query_message</b></p>\n";
    print "<p>" . str_replace("\n","<br>",$forms->report['query']) . "</p>\n";
    if ( ($forms->report['query_results']=="display") && (sizeof($query_results)) ) {
		print "<table class='standard border'>\n";
        foreach ($query_results as $item_key => $item) {
			print "<tr>\n";
            foreach ($item as $value) {
            	if (!$item_key) {
                	print "<th>$value</th>\n";
				  } else {
                	print "<td>$value</td>\n";
				}
            }
	        print "</tr>\n";
        }
		print "</table>\n";
	}
	print "<p class=standard>" . $forms->button("Return",array("onclick"=>"fn_action('','');"));
    print "</p>\n";
	break;
  case ((!sizeof($forms->error)) && ($forms->report['function']=="Query") && ($forms->report['action']=="review")):
	print $forms->hidden("function",$forms->report['function']);
    print $forms->hidden("query",$forms->report['query']);
    print "<p><b>Review your query</b></p>\n";
    print "<p>" . str_replace("\n","<br>",$forms->report['query']) . "</p>\n";
	print
    	"<p class=standard>Show results? " .
        $forms->select('query_results',$forms->constant->results,$forms->report['query_results'],FALSE) .
        "<br>File name: " . $forms->text("query_file","file.txt",30) .
        "</p>\n";
	print "<p class=standard>" . $forms->button("<< Back",array("onclick"=>"fn_action('','');"));
    print $forms->button("Execute >>",array("onclick"=>"fn_action('update','1');"));
    print "</p>\n";
	break;

  case (($forms->report['function']=="Upload") && ($forms->report['action']=="update")):
	print $forms->hidden("function",$forms->report['function']);
	print $forms->hidden("upload_name",$forms->report['upload_name']);
    print "<table class='small border'>\n";
	print "<tr>\n";
    print "<th width='5%'>Line#</th>\n";
    print "<th width='20%'>Results</th>\n";
    print "<th width='75%'>Query</th>\n";
	print "</tr>\n";
    foreach ($upload_results as $item) {
        switch (TRUE) {
		  case (!$item->checked):
			$bg=$forms->background->white;
            $status="Skipped";
            break;
		  case ($item->abort):
			$bg=$forms->background->khaki;
            $status="Skipped due to errors in prior command";
            break;
		  case ($item->error):
			$bg=$forms->background->orangered;
            $status=$item->error . " (" .  $item->errno . ")";
            break;
          default:
			$bg=$forms->background->limegreen;
            $status="Affected rows: " . number_format($item->rows);
        }
	    print "<tr $bg>\n";
	    print "<td class=center>" . ($item->line + 1) . "</td>\n";
	    print "<td>$status</td>\n";
	    print "<td>" . substr($item->query,0,80) . "</td>\n";
	    print "</tr>\n";
    }
    print "</table>\n";
	print "<p class=standard>" . $forms->button("Return",array("onclick"=>"fn_action('','');"));
    print "</p>\n";
	break;
  case ((!sizeof($forms->error)) && ($forms->report['function']=="Upload") && ($forms->report['action']=="review")):
	print $forms->hidden("function",$forms->report['function']);
	print $forms->hidden("upload_name",$forms->report['upload_name']);
	print
    	"<p class=standard>File name: " . $forms->report['upload_name'] .
        "<br>Queries: " . number_format(sizeof($upload_results)) . "</p>\n";
	print "<p class=standard>" . $forms->button("<< Back",array("onclick"=>"fn_action('','');"));
	print "<p>Select all? " . $forms->checkbox("checked",1,1,array("onchange"=>"fn_checked(this.checked);")) . "</p>";
    print "<table class='small border'>\n";
	print "<tr>\n";
    print "<th width='3%'>Line#</th>\n";
    print "<th width='3%'>Execute?</th>\n";
    print "<th width='94%'>Query</th>\n";
	print "</tr>\n";
    foreach ($upload_results as $key => $value) {
	    print "<tr>\n";
	    print "<td class=center>" . ($key + 1) . "</td>\n";
	    print "<td class=center>" . $forms->checkbox("checked_" . $key,$key,$key) . "</td>\n";
	    print "<td>" . substr($value,0,80) . "</td>\n";
	    print "</tr>\n";
    }
    print "</table>\n";
    print $forms->button("Execute >>",array("onclick"=>"fn_action('update','1');"));
    print "</p>\n";
	break;
  default:
  	$text=array();
    $text[]="Host: <b>" . $forms->report['server']['host'] . "</b>";
    $text[]="Database: " . $forms->select("database",$forms->constant->database_list,$forms->report['database'],FALSE,array("onchange"=>"fn_database();"));
    $text[]="Function: " . $forms->select("function",$forms->constant->function_list,$forms->report['function'],FALSE,array("onchange"=>"fn_function(this.value);"));
	print "<p class=standard>" . implode("<br>",$text) . "</p>";
    $items=array();
	$database->temp->query("show table status");
	while ($database->temp->fetch = $database->temp->fetch_array()) {
		$item=new stdClass();
        $item->comment=$database->temp->fetch['Comment'];
        $item->rows=$database->temp->fetch['Rows'];
        $item->update_time=date("m/d/Y H:i",strtotime($database->temp->fetch['Update_time']));
        $items[$database->temp->fetch['Name']]=$item;
    }
    $database->temp->free_result();
	foreach ($items as $table => $item) {
		$database->temp->query("show create table `$table`");
        $database->temp->fetch();
        $items[$table]->create=$database->temp->fetch['Create Table'];
    }
	print "<div id='mysql_table'>\n";
	print "<p>Select all? " . $forms->checkbox("checked",1,0,array("onchange"=>"fn_checked(this.checked);")) . "</p>";
	print "<table class='small border tablesorter'>\n";
	print "<thead>\n";
	print "<tr>\n";
	print "<th width='5%'><span id='function_lit'></span></th>\n";
	print "<th width='40%'>Name</th>\n";
	print "<th width='30%'>Description</th>\n";
	print "<th width='10%'># Records</th>\n";
	print "<th width='15%'>Last Update</th>\n";
	print "</tr>\n";
	print "</thead>\n";
	print "<tbody>\n";
	foreach ($items as $table => $item) {
	    print "<tr>\n";
        $check_id="checked_{$table}";
        $create_id="create_{$table}";
	    print "<td class=center>" . $forms->checkbox($check_id,$table,"",array("title"=>$table)) . "</td>\n";
		print "<td>" . fn_href($table,"javascript:fn_create('$create_id',1);") . "<div id='$create_id' style='display:none'>" . str_replace("\n","<br>",$item->create) . "</div></td>\n";
	    print "<td>" . $item->comment . "</td>\n";
	    print "<td class='right'>" . number_format($item->rows) . "</td>\n";
	    print "<td class='center'>" . $item->update_time . "</td>\n";
	    print "</tr>\n";
	}
	$database->temp2->free_result();
	print "</tbody>\n";
	print "</table>\n";
	$text=array();
    $text[]=$forms->button("Go!",array("onclick"=>"fn_action('update','');"));
    $text[]="<span id='mysql_table_name'> Backup file: " . $forms->text("backup_name",$forms->report['backup_name'],30) . "</span>";
	print "<p class=standard>" . implode("",$text) . "</p>";
	print "</div>\n";

	print "<div id='mysql_query'>\n";
	print $forms->hidden("query_results",$forms->report['query_results']);
	print "<p class=standard>Query:<br>" . $forms->textarea("query",$forms->report['query'],0,80) . "</p>\n";
	print "<p class=standard>" . $forms->button("Next >>",array("onclick"=>"fn_action('review','');")) . "</p>\n";
	print "</div>\n";

	print "<div id='mysql_upload'>\n";
	print
    	"<p class=standard>Upload sql file: " . $forms->file('upload',60) .
        " <b>" . $forms->error_text['upload'] . "</b></p>\n";
	print "<p class=standard>" . $forms->button("Next >>",array("onclick"=>"fn_action('review','');")) . "</p>\n";
	print "</div>\n";
	$database->temp->free_result();
    print $forms->div(mysql_toolbox_tables(),array("id"=>"mysql_tables"));
    print $forms->div(mysql_toolbox_server(),array("id"=>"mysql_server"));
}
print $forms->close();
$menu->copyright();
function mysql_toolbox_tables() {
global $database, $forms;
	if ( (!$forms->report['database']) ||  ($forms->report['database'] == $database->meta->database) ) return;
	$database->temp->query("show table status from " . $forms->report['database']);
    if ($database->temp->meta_rows) return "<p>No tables found</p>";
    $items=array();
	while ($database->temp->fetch = $database->temp->fetch_array()) {
		$item=new stdClass();
        $item->comment=$database->temp->fetch['Comment'];
        $item->rows=$database->temp->fetch['Rows'];
        $item->update_time=date("m/d/Y H:i",strtotime($database->temp->fetch['Update_time']));
        $items[$database->temp->fetch['Name']]=$item;
    }
    $database->temp->free_result();
	foreach ($items as $table => $item) {
		$database->temp->query("show create table " . $forms->report['database'] . ".{$table}");
        $database->temp->fetch();
        $items[$table]->create=$database->temp->fetch['Create Table'];
    }
	$results=array();
	$results[]="<table class='small border tablesorter'>";
	$results[]="<thead>";
	$results[]="<tr>";
	$results[]="<th width='30%'>Table</th>";
	$results[]="<th width='60%'>Description</th>";
	$results[]="<th width='10%'># Records</th>";
	$results[]="</tr>";
	$results[]="</thead>";
	$results[]="<tbody>";
    foreach ($items as $table => $item) {
    	$results[]="<tr>";
        $text=array();
        $create_id="create_{$table}";
        $text[]=fn_href($table,"javascript:fn_create('$create_id',1);");
        $text[]="<div id='$create_id' style='display:none'>" . str_replace("\n","<br>",$item->create) . "</div>";
    	$results[]="<td>" . implode("",$text) . "</td>";
    	$results[]="<td>" . $item->comment . "</td>";
    	$results[]="<td class=right>" . number_format($item->rows) . "</td>";
    	$results[]="</tr>";
    }
	$results[]="</tbody>";
    $results[]="</table>";
    return $results;
}
function mysql_toolbox_server() {
global $database, $forms;
	$results=array();
	$database->temp->query("show variables");
	$data=array();
	$data['Connection']=array();
	$data['Version']=array();
	$data['Server Variables']=array();
	$data['Logging']=array();
	$data['SSL']=array();
	$data['Performance']=array();
	$data['INNODB Variables']=array();
    foreach ($forms->report['server'] as $key => $value) {
		$data['Connection'][$key]=$value;
    }
	while ($database->temp->fetch = $database->temp->fetch_array() ) {
		$key=$database->temp->fetch['Variable_name'];
		$value=$database->temp->fetch['Value'];
        switch (TRUE) {
          case (preg_match("/version/i",$key)):
          case (preg_match("/license/i",$key)):
        	$data['Version'][$key]=$value;
			break;
          case (preg_match("/innodb/i",$key)):
        	$data['INNODB Variables'][$key]=$value;
			break;
          case (preg_match("/log/i",$key)):
        	$data['Logging'][$key]=$value;
			break;
          case (preg_match("/performance/i",$key)):
        	$data['Performance'][$key]=$value;
			break;
          case (preg_match("/ssl/i",$key)):
        	$data['SSL'][$key]=$value;
			break;
		  default:
			$data['Server Variables'][$key]=$value;
		}
    }
	$database->temp->free_result();
	$results[]="<table class='standard border'>";
    $results[]="<tr>";
    $results[]="<th width='20%'>Variable</th>";
    $results[]="<th width='80%'>Value</th>";
    $results[]="</tr>";
    foreach ($data as $type => $type_data) {
		if (!sizeof($type_data)) continue;
    	$results[]="<tr><th colspan=2 class='left'><b>$type</b></th></tr>";
        foreach ($type_data as $key => $value) {
	        $results[]="<tr>";
	        $results[]="<td>$key</td>";
	        $results[]="<td>" . wordwrap($value,80,"<br>",TRUE) . "</td>";
	        $results[]="</tr>";
        }
    }
    $results[]="</table>";
    return $results;
}
class mysql_upload_class {
	var $line;
	var $query;
    var $checked=FALSE;
    var $abort=FALSE;
    var $rows=0;
    var $errno=0;
    var $error;
    var $results;
	function __construct($line,$query) {
    global $forms;
    	$this->line=$line;
		$this->query=$query;
		$this->checked=in_array($line,$forms->constant->checked);
    }
    function query($error_count) {
    global $database;
		switch (TRUE) {
		  case (!$this->checked):
          	$this->results("Skipped");
            return $error_count;
          case ($error_count):
			$this->results("Skipped due to errors in prior command");
            $this->abort=TRUE;
            return $error_count;
        }
        $database->temp->query($this->query);
        $this->errno=$database->temp->meta->errno;
        $this->error=$database->temp->meta->error;
		$this->rows=number_format($database->affected_rows());
        if ($this->errno) {
          	$this->results($this->error . " (" .  $this->errno . ")");
			return $error_count + 1;
		  } else {
          	$this->results("Affected rows: " . number_format($database->affected_rows()));
          	return $error_count;
		}
    }
    function results($text) {
		$this->results=($this->line + 1) . "). $text";
    }
}
?>