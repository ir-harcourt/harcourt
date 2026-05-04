<?php
require_once "classes/mailform.php";
class mysql_toolbox_class {
	var $input;
    var $constant;
    var $query_message;
	var $server;
    var $backup_list=array();
    var $results=array();
    var $stats=array();
    var $trace=array();
    var $upload_fh;
    var $temp_fh;
    var $row;
    function scs_table_version() {
    	$this->trace[]=__METHOD__;
    	$results=array();
        $results['06/23/2025']="Add auto_increment";
        $results['04/10/2025']="Add xlsx";
        $results['03/20/2025']="Add options:timestamp (timestamp backup file)";
        $results['01/01/2025']="Hotfix bug";
        $results['12/29/2024']="Add mysql_hotfix_class:alter(), query()";
        $results['10/02/2024']="Remove backup timestamp from development server";
        $results['08/06/2024']="Unlock lock only for MyIsam tables";
        $results['03/25/2024']="Add hotfix";
        $results['02/16/2024']="PHP 8 compatible";
        $results['07/13/2023']="General Improvements";
        $results['12/05/2022']="Add upload post_max_size upload_max_filesize";
        $results['08/23/2021']="Add query stopwatch";
        $results['01/06/2021']="Allow download NULL";
        $results['07/27/2020']="Restore by step";
        $results['01/07/2019']="Always include date in backup files";
        $results['12/02/2018']=array("Initial version 1 release","Based on mysql_rev0.php version 10/31/2018");
        return $results;
    }
    function __construct($role, $options=array()) {
        global $database;
        $this->trace[]=__METHOD__;
	    $this->stopwatch=new stopwatch_class($_REQUEST['function']);
        if ( (property_exists($database->user->constant,"role")) && (in_array($role, $database->user->constant->role)) ) $database->user->access($role);
        $database->temp2=new database_temp_class();
	    $this->server=$database->server();
        $this->constant=new mysql_toolbox_constant($options);
        if (preg_match("/^xmlhttprequest$/i",$_SERVER['HTTP_X_REQUESTED_WITH'])) {
            $this->json();
            } else {
            $this->html();
        }
    }
    function json() {
        global $database, $forms;
        $this->response=new stdClass();
        switch ($_POST['action']) {
          case "hotfix":
            $this->response->error=$this->json_hotfix();
            break;
          case "auto":
            $this->response->error=$this->json_auto();
            break;
        }

        die(json_encode($this->response));
    }
    function json_auto() {
        global $database;
        $this->response->auto_increment=$database->schema($_POST['table'], "AUTO_INCREMENT");
    }
    function json_hotfix() {
        global $database;
        $table=trim($_POST['table']);
        $field_convert="hotfix_convert_{$table}";
        $field_instructions="hotfix_message_{$table}";
        list($this->response->$field_convert, $this->response->$field_instructions) = $this->json_hotfix_process($table);
    }
    function json_hotfix_process($table) {
        global $database;
        switch (TRUE) {
          case (!strlen($table)):
            return array("Error", "Missing table name");
          case (!property_exists($database, $table)):
            return array("Error", "Invald name: {$table}");
          case (!method_exists($database->$table, "scs_hotfix")):
            return array("Error", "Table {$table} Function not found: scs_hotfix");
        }
        $database->$table->scs_hotfix(TRUE);
        $results=array();
        $this->response->hotfix=$database->$table->hotfix;
        if (strlen($database->$table->hotfix->error)) {
            $results[]="Error";
            $results[]=$database->$table->hotfix->error;
          } else {
            $field_convert="hotfix_version_{$table}";
            $this->response->$field_convert=fn_date(key($database->$table->hotfix->version), "ymd");
            $results[]="Complete";
            $results[]=implode("<br>", $database->$table->hotfix->meta->comment);
        }
        return $results;
    }
    function html() {
        global $database, $forms;
        $this->trace[]=__METHOD__;
		$forms->title("MySQL Toolbox (" . key($this->scs_table_version()) . ")");
        $this->input=new stdClass();
	    $this->input->action=$_REQUEST['action'];
	    $this->input->function=$_REQUEST['function'];
	    $this->input->database=( ($_REQUEST['database']) ? $_REQUEST['database'] : $database->meta->database);
	    $this->input->query=trim($_REQUEST['query']);
	    $this->input->query_output=$_REQUEST['query_output'];
	    $this->input->query_file=trim($_REQUEST['query_file']);
        if (!strlen($this->input->query_file)) $this->input->query_file="output.txt";
	    $this->input->upload_name=$_REQUEST['upload_name'];
	    $this->input->download_name=trim($_REQUEST['download_name']);
		if (!$this->input->download_name) $this->input->download_name=$this->server['database'] . ".sql";
        $this->input->xlsx_table=trim($_REQUEST['xlsx_table']);
	    $this->input->timeout=( (array_key_exists("timeout",$_REQUEST)) ? $_REQUEST['timeout'] : 600);
        switch (TRUE) {
          case (array_key_exists("archive",$_REQUEST)):
            $this->input->archive=$_REQUEST['archive'];
            break;
          case ($_SERVER['REQUEST_METHOD'] == "POST"):
            $this->input->archive=0;
            break;
          default:
            $this->input->archive=1;
        }
        if ($this->input->timeout) set_time_limit($this->input->timeout);
	    $this->input->table=array();
	    $this->input->restore=array();
	    $this->input->delete=array();
	    $this->input->execute=array();
	    foreach ($_REQUEST as $key => $value) {
	        $field=fn_base64($key,FALSE);
	        switch (TRUE) {
	          case (!is_array($field)):
	          case ($field[1]==""):
	            break;
	          case ($field[0] == "table"):
	            $this->input->table[]=$field[1];
	            break;
	          case ( ($field[0] == "restore") && (strlen($value)) ):
	            $this->input->restore[]=$value;
	            break;
	          case ( ($field[0] == "delete") && (strlen($value)) ):
	            $this->input->delete[]=$value;
	            break;
	          case ($field[0] == "execute"):
	            $this->input->execute[]=$field[1];
	            break;
	        }
	    }
	    switch (TRUE) {
	      case (!$this->input->action):
	      case (!$this->input->function):
	        break;
	      case (($this->input->function=="Query") && ($this->input->action=="update")):
          	$this->query_update();
            break;
	      case ($this->input->function=="Query"):
	        if (!$this->input->query) $forms->error('query');
	        if (sizeof($forms->error)) break;
	        break;
  		  case (($this->input->function=="Execute") && ($this->input->action=="update")):
          	$this->execute_update();
	        break;
		  case (in_array($this->input->function,array("Execute", "Upload"))):
          	$this->upload();
          	break;
		  case ($this->input->function=="Restore"):
          	$this->restore();
          	break;
		  case ( ($this->input->function=="Step") && ($this->input->action=="review")):
          	$this->step_review();
          	break;
  		  case (($this->input->function == "Step") && ($this->input->action=="update")):
          	$this->execute_update();
	        break;
		  case ($this->input->function == "Delete"):
          	$this->delete();
          	break;
		  case (in_array($this->input->function,array("Repair", "Backup", "Download"))):
          	$this->table();
			break;
          case ($this->input->function == "XLSX"):
            $this->xlsx();
            break;
          case ($this->input->function == "Hotfix"):
            $this->hotfix();
            break;
          case ($this->input->function == "Auto"):
            $this->auto();
            break;
		  default:
          	$forms->error('null',"","Invalid function: " . $this->input->function);
		}
        $this->html_body();
	}
    function step_review() {
        global $database, $forms;
    	$this->trace[]=__METHOD__;
		if (!$_POST['step_filename']) {
        	$forms->error("null","","No file selected");
            return;
		}
        $filename=$this->constant->scs_sql . $_POST['step_filename'];
        if (!file_exists($filename)) {
        	$forms->error("null","","{$filename} not found");
            return;
		}
        if (!copy($filename, $this->constant->upload_file)) {
        	$forms->error("null","","Cannot copy to " . $this->constant->upload_file);
            return;
        }
        $this->input->upload_name=$_POST['step_filename'];
    }
    function upload() {
        global $database, $forms;
    	$this->trace[]=__METHOD__;
        $this->input->upload_name=$_FILES['upload']['name'];
	    switch (TRUE) {
	      case (!$_FILES['upload']['name']):
	      case (!intval($_FILES['upload']['size'])):
	        $forms->error('execute','Entry cannot be blank');
	        return;
	      case (intval($_FILES['upload']['error'])):
	        $forms->error('upload','Error #' . $_FILES['upload']['error']);
	        return;
		  case ( ($this->input->function == "Upload") && (!preg_match('/\.sql$/i',$_FILES['upload']['name'])) ):
	        $forms->error('upload',"File type must be sql");
	        return;
          case ($this->input->function == "Upload"):
          	$this->upload_update($_FILES['upload']['tmp_name']);
            break;
          case ($this->input->function == "Execute"):
          	$this->execute_review($_FILES['upload']['tmp_name']);
            break;
		}
	}
    function upload_update($tmp_name) {
        global $database, $forms;
    	$this->trace[]=__METHOD__;
		$filename=preg_split("/\./",strtolower($this->input->upload_name));
		if (sizeof($filename) == 2) array_unshift($filename,$this->constant->database);
        $filename_url=$this->constant->scs_sql . implode(".",$filename);
		if (file_exists($filename_url)) unlink($filename_url);
        move_uploaded_file($tmp_name,$filename_url);
		$forms->message[]="Upload file: " . $this->input->upload_name;
    }
    function execute_review($tmp_name) {
        global $database, $forms;
    	$this->trace[]=__METHOD__;
        move_uploaded_file($tmp_name,$this->constant->upload_file);
	}
    function execute_update() {
        global $database, $forms;
    	$this->trace[]=__METHOD__;
	    $abort=0;
	    $database->temp->meta->error_abort=FALSE;
        if (!file_exists($this->constant->temp_file)) {
        	$this->error("null","","Temporary file missing");
            return;
        }
        $this->constant->temp_fh=fopen($this->constant->temp_file,"r");
        $field=array("execute");
        $field[1]=0;
		while (!feof($this->constant->temp_fh)) {
            if ($abort == 2) break;
        	$query=fgets($this->constant->temp_fh);
            if (array_key_exists(fn_base64($field), $_POST)) {
				$database->temp->query($query);
                switch (TRUE) {
                  case (!$database->temp->meta->errno):
          			$this->results[ $field[1] ]="Affected rows: " . number_format($database->affected_rows());
                  	break;
                  case ($_POST['execute_error'] == "dump"):
					die(wordwrap($query,400,"\n",TRUE));
                    break;
                  default:
					$this->results[ $field[1] ]=$database->temp->meta->error . " (" .  $database->temp->meta->errno . ")";
                    $abort=( ($_POST['execute_error'] == "ignore") ? 1 : 2);
				}
            }
            $field[1]++;
		}
        fclose($this->constant->temp_fh);
	    if (!$abort) {
	        $forms->message[]="Queries executed successfully";
	      } else {
	        $forms->message[]="Errors detected in query";

	    }
	    $database->temp->meta->error_abort=TRUE;
    }
    function query_update() {
        global $database, $forms;
    	$this->trace[]=__METHOD__;
	    $database->temp->meta->error_abort=FALSE;
	    $this->use_database(TRUE);
        $this->stopwatch->split("Execute");
	    $database->temp->query($this->input->query);
	    $database->temp->meta->error_abort=TRUE;
	    if ($database->temp->meta->error) {
	        $this->query_message="Database error #" . $database->temp->meta->errno . "</b><br>" . $database->temp->meta->error;
			return;
	      } else {
	        $this->query_message="Affected rows: " . number_format($database->affected_rows());
	    }
	    while ( $database->temp->fetch = $database->temp->fetch_array() ) {
	        if (!sizeof($this->results)) {
	            $data=array();
	            foreach ($database->temp->fetch as $key => $value) {
	                $data[]=$key;
	            }
	            $this->results[]=$data;
	        }
	        $data=array();
	        foreach ($database->temp->fetch as $key => $value) {
	            $data[]=$value;
	        }
	        $this->results[]=$data;
	    }
	    $database->temp->free_result();
        $this->stopwatch->split("Finalize");
	    $this->use_database(FALSE);
	    if ( $this->input->query_output != "txt") return;
	    header("Content-type: text/plain");
	    header("Content-Disposition: attachment; filename=" . $this->input->query_file);
	    header("Pragma: no-cache");
	    header("Expires: 0");
	    $fh=fopen("php://output", "w");
	    foreach ($this->results as $data) {
	    	fputcsv($fh,preg_replace("/\s/"," ",$data),"\t","\"");
	    }
	    fclose($fh);
	    die;
    }
    function restore() {
        global $database, $forms;
    	$this->trace[]=__METHOD__;
	    if (!sizeof($this->input->restore)) {
	        $forms->error("null","","No files selected to restore");
	        return;
	    }
	    foreach ($this->input->restore as $file) {
        	$this->stopwatch->split($file);
	    	$command=$this->mysql_command("mysql","--force");
	        $command[]="< " . $this->constant->scs_sql . $file;
	        $exec=array();
	        $exec['command']="(" . implode(" ",$command) . ")";
			$exec['results']=exec($exec['command'],$exec['output'],$exec['response']);
	    	if (intval($exec['response']) != 0) $forms->message[]=$exec['results'] . " : {$filename}";
	    }
	    if (method_exists($database->log,"ipc")) $database->log->ipc("MySQL: " . $this->input->function,implode(", ",$this->input->restore));
	    $forms->message[]="Restore files: " . implode(", ",$this->input->restore);
    }
    function delete() {
        global $database, $forms;
    	$this->trace[]=__METHOD__;
	    if (!sizeof($this->input->delete)) {
	        $forms->error("null","","No files selected to delete");
	        return;
	    }
	    foreach ($this->input->delete as $file) {
        	$filename=$this->constant->scs_sql . $file;
			if (file_exists($filename)) unlink($filename);
        }
	    if (method_exists($database->log,"ipc")) $database->log->ipc("MySQL: " . $this->input->function,implode(", ",$this->input->delete));
	    $forms->message[]="Delete files: " . implode(", ",$this->input->delete);
	}
    function table() {
        global $database, $forms;
    	$this->trace[]=__METHOD__;
	    switch (TRUE) {
	      case (!sizeof($this->input->table)):
	        $forms->error('null','',"No tables selected");
	        return;
	      case ( ($this->input->function=="Download") && (!preg_match("/.sql$/i",$this->input->download_name)) ):
	        $forms->error('download_name');
	        return;
	    }
	    if ($this->input->function=="Download") {
	        header("Content-type: text/plain");
	        header("Content-Disposition: attachment; filename=" . $this->input->download_name);
	        header("Pragma: no-cache");
	        header("Expires: 0");
	        $fh = fopen("php://output", "w");
	        fwrite($fh,"--\n");
	        fwrite($fh,"-- SCS MySQL Dump version: " . key($this->scs_table_version()) . "\n");
	        fwrite($fh,"-- MySQL version: " . $this->server['server_info'] . "\n");
	        fwrite($fh,"-- Host: " . $this->server['host'] . "\n");
	        fwrite($fh,"-- Database: " . $this->input->database . "\n");
	        fwrite($fh,"-- " . date("D M d, Y H:i:s\n"));
	        fwrite($fh,"--\n");
	    }
	    $this->use_database(TRUE);
	    foreach ($this->input->table as $table) {
        	$this->stopwatch->split($table);
	        switch ($this->input->function) {
	          case "Repair":
	            $this->table_repair($table);
	            break;
	          case "Backup":
	            $this->table_backup($table);
	            break;
	          case "Download":
	            $this->table_download($table, $fh);
	            break;
	        }
	    }
	    $this->use_database(FALSE);
	    if ((in_array($this->input->function,array("Repair","Download","Backup"))) && (method_exists($database->log,"ipc"))) {
	        $text=array();
	        if ($this->database != $this->constant->database) $text[]="Database: " . $this->input->database;
	        $text[]="Tables: " . implode(", ",$this->input->table);
	        $database->log->ipc("MySQL: " . $this->input->function,$text);
	    }
	    switch (TRUE) {
	      case ($this->input->function=="Download"):
	        fclose($fh);
	        die;
	      default:
	        $forms->message[]=$this->input->function . " tables: " . implode(", ",$this->input->table);
	    }
	}
	function table_repair($table) {
        global $database;
    	$this->trace[]=__METHOD__;
		$database->temp->query("repair table {$table}");
	}
	function table_backup($table) {
        global $database, $forms;
    	$this->trace[]=__METHOD__;
        $filename=array($this->constant->scs_sql);
        $filename[]="{$this->input->database}.{$table}";
        if ($this->constant->backup_suffix) $filename[]=$this->constant->backup_suffix;
        $filename[]=".sql";
	    $dump_file=implode("", $filename);
	    if (file_exists($dump_file)) unlink($dump_file);
	    $command=$this->mysql_command("mysqldump","--hex-blob");
	    $command[]=$table;
	    $command[]="> " . $dump_file;
	    $exec=array();
	    $exec['command']="(" . implode(" ",$command) . ")  2>&1";
	    $exec['results']=exec($exec['command'],$exec['output'],$exec['response']);
	    if (intval($exec['response']) != 0) $forms->message[]=$exec['results'] . " : {$table}";
    }
	function table_download($table,$fh) {
        global $database, $forms;
    	$this->trace[]=__METHOD__;
	    $database->temp->query("show table status where Name='{$table}'");
	    $database->temp->fetch=$database->temp->fetch_array();
        $this->constant->myisam=(preg_match("/^myisam/i", $database->temp->fetch['Engine'])) ? 1 : 0;
	    $this->constant->rows=$database->temp->fetch['Rows'];
	    $database->temp->query("show create table `$table`");
	    $database->temp->fetch=$database->temp->fetch_array();
	    $this->constant->create=$database->temp->fetch['Create Table'];
	    $fields=explode("\n",$database->temp->fetch['Create Table']);
	    $this->constant->field_map=array();
	    foreach ($fields as $field) {
	        $field=trim($field);
	        if (substr($field,0,1) != "`") continue;
	        list($field_name,$field_format)=explode("`",substr($field,1));
	        switch (TRUE) {
	          case (preg_match("/blob|binary|text/i",$field_format)):
	            $this->constant->field_map[$field_name]="binary";
	            break;
	          case (!preg_match("/not null/i",$field_format)):
	            $this->constant->field_map[$field_name]="null";
	            break;
	        }
	    }
	    $this->constant->fields=array();
	    $database->temp->query("show fields from `$table`");
	    while ($database->temp->fetch = $database->temp->fetch_array()) {
	        $this->constant->fields[]=$database->temp->fetch['Field'];
	    }
	    fwrite($fh,"\n");
	    fwrite($fh,"DROP TABLE IF EXISTS `$table`;\n");
	    fwrite($fh,$this->constant->create . ";\n");
	    fwrite($fh,"LOCK TABLES `$table` WRITE; \n");
	    if ($this->constant->myisam) fwrite($fh,"ALTER TABLE `$table` DISABLE KEYS; \n");
	    fwrite($fh,"-- Records in table: {$table}:  " . number_format($this->constant->rows) . "\n");
        $limit=500;
        $count=0;
        while (!$eof) {
            $query="select * from {$table} limit {$count}, {$limit}";
	        $database->temp->query($query);
            if (!$database->temp->meta->rows) break;
            $items=array();
            while ($database->temp->fetch = $database->temp->fetch_array()) {
                $text=array();
                foreach ($this->constant->fields as $key => $field) {
                	switch (TRUE) {
                      case ( (!strlen($database->temp->fetch[$field])) && ($this->constant->field_map[$field]) ):
                      	$text[]="NULL";
                        break;
                      case (preg_match('/[\\x80-\\xff]/', $database->temp->fetch[$field])):
                        $text[]="x'" . bin2hex($database->temp->fetch[$field]) . "'";
                        break;
                      default:
						$text[]=fn_escape($database->temp->fetch[$field]);
                      	break;
					}
                }
                $items[]="(" . implode(",",$text) . ")";
            }
            fwrite($fh,"INSERT INTO `{$table}` VALUES \n" . implode(",\n",$items) . ";" . "\n");
        	$database->temp->free_result();
            if ($database->temp->meta->rows != $limit) break;
            $count += $limit;
        }
	    if ($this->constant->myisam) fwrite($fh,"ALTER TABLE `$table` ENABLE KEYS;\n");
        fwrite($fh,"UNLOCK TABLES;\n");
    }
	function mysql_command($command, $options="") {
        global $database;
    	$this->trace[]=__METHOD__;
    	$results=array($command);
	    $results[]="--host=" . $database->meta->host;
	    $results[]="--user=" . $database->meta->user;
		$results[]="--password=" . ( ($this->constant->linux) ? $database->meta->password : fn_escape($database->meta->password));
        if (strlen($options)) $results[]=$options;
	    $results[]=$this->input->database;
        return $results;
    }
    function auto() {
        global $database, $forms;
    	$this->trace[]=__METHOD__;
        $this->auto_increment=intval($_POST['auto_increment']);
        if (!strlen($_POST['auto_table'])) $forms->error('auto_table');
        if (!$this->auto_increment) $forms->error('auto_increment');
        if (sizeof($forms->error)) return;
        $database->temp->query("alter table {$_POST['auto_table']} AUTO_INCREMENT={$this->auto_increment}");
    }
    function xlsx() {
        global $database, $forms;
        require_once "map_rev3.php";
    	$this->trace[]=__METHOD__;
        if (!strlen($this->input->xlsx_table)) {
            $forms->error("xlsx_table","Cannot be blank");
            return;
        }
        $options=array();
        $options["file_type"]=".xlsx";
        $this->map=new map_class("scs_database", $options);
        $map=array();
        $map['float']="decimal";
        $map['decimal']="decimal";
        $map['int']="decimal";
        $map['smallint']="decimal";
        $map['mediumint']="decimal";
        $map['bigint']="decimal";
        $map['date']="date";

        $database->temp->query("show create table {$this->input->database }.{$this->input->xlsx_table}");
        $database->temp->fetch();
        $items=explode("\n", $database->temp->fetch['Create Table']);
        foreach ($items as $item) {
            $item=trim($item);
            if (substr($item, 0, 1) != "`") continue;
            list($field_name, $field_type)=(explode(" ", $item));
            $type="alphanumeric";
            foreach ($map as $map_code => $map_type) {
                if (substr($field_type, 0, strlen($map_code)) == $map_code) {
                    $type=$map_type;
                    break;
                }
            }
            if ($type == "decimal") {
                list($prefix, $suffix)=explode(",", $field_type);
                $type .= ":" . intval(substr($suffix, 0, 1));
            }
            $database->scs_database->map->item(str_replace("`","", $field_name),array("type"=>$type));
        }
        $query="select * from {$this->input->xlsx_table}";
        $options=array();
        $options['filename']=$this->input->xlsx_table;
        $this->map->export($query, $options);
    }
    function html_body() {
        global $database, $forms, $menu;
    	$this->trace[]=__METHOD__;
		$this->stopwatch->split(__FUNCTION__);
	    $menu->head();
	    $forms->message();
        $this->backup_list();
        print $this->css();
        print $this->js();
	    print $forms->open(array("data"=>TRUE));
	    switch (TRUE) {
	      case ( ($this->input->function=="Query") && ($this->input->action=="update") ):
			print $this->html_query_update();
			print implode("",$this->html_stopwatch());
            break;
		  case ( ($this->input->function=="Query") && ($this->input->action=="review") && (!sizeof($forms->error)) ):
			print $this->html_query_review();
            break;
		  case ( (in_array($this->input->function,array("Execute", "Step"))) && ($this->input->action=="update") && (!sizeof($forms->error)) ):
			print $this->html_execute_update();
            break;
		  case ( (in_array($this->input->function,array("Execute", "Step"))) && ($this->input->action=="review") && (!sizeof($forms->error)) ):
			print $this->html_execute_review();
            break;
          default:
	    	$database->temp->query("show table status from " . $this->input->database);
            while ($database->temp->fetch = $database->temp->fetch_array()) {
            	$this->stats[$database->temp->fetch['Name']]=$database->temp->fetch;
            }
          	print "<table class='standard noborder'>";
            print "<tr><td class=mysql_toolbox_input1>Host</td><td class=mysql_toolbox_input2><b>" . $this->server['host'] . "</b></td></tr>";
           if (sizeof($this->constant->database_list['Database']) == 1) {
            	$text="<b>" . $this->input->database . "</b>";
			  } else {
              	$text=$forms->optgroup("database",$this->constant->database_list,$this->input->database,FALSE,array("onchange"=>"fn_database();"));
			}
            print "<tr><td class=mysql_toolbox_input1>Database</td><td class=mysql_toolbox_input2>{$text}</td></tr>";
            if (strlen($this->constant->scs_sql))  print "<tr><td class=mysql_toolbox_input1>Backup Location</td><td class=mysql_toolbox_input2><b>{$this->constant->scs_sql}</b></td></tr>";
            print "<tr><td class=mysql_toolbox_input1>Function</td><td class=mysql_toolbox_input2>" . $forms->optgroup("function",$this->function_list(),$this->input->function,TRUE,array("onchange"=>"fn_function(this.value);")) . "</td></tr>";
            $text=array($forms->file('upload',60,array("accept"=>".sql")));
            if (array_key_exists("upload",$forms->error_text)) $text[]="<b>" . $forms->error_text['upload'] . "</b>";
            print "<tr id='input_upload' class='mysql_div'><td class=mysql_toolbox_input1>SQL file</td><td class=mysql_toolbox_input2>" . implode("<br>",$text) . "</td></tr>";
            print "<tr id='input_query' class='mysql_div'><td class=mysql_toolbox_input1>Query<br>" . fn_href("Clear?","javascript:mysql_clear('query');") . "</td><td class=mysql_toolbox_input2>" . $forms->textarea("query",$this->input->query,0,60) . "</td></tr>";
	    	$table_field=fn_base64(array("table"));
            print "<tr id='input_download_name' class='mysql_div'><td class=mysql_toolbox_input1>Download file</td><td class=mysql_toolbox_input2>" . $forms->text("download_name",$this->input->download_name,30) . "</td></tr>";
            print "<tr id='input_archive' class='mysql_div'><td class=mysql_toolbox_input1>Archive existing files?</td><td class=mysql_toolbox_input2>" . $forms->checkbox("archive",1,$this->input->archive) . "</td></tr>";
            print "<tr id='input_table_select' class='mysql_div'><td class=mysql_toolbox_input1>Select all?</td><td class=mysql_toolbox_input2>" . $forms->checkbox($field,1,0,array("onchange"=>"fn_checked(this," . fn_escape($table_field) . ");")) . "</td></tr>";
			print "</table>";
			print $forms->div($this->html_table($table_field),array("id"=>"mysql_table", "class"=>"mysql_div"));
			print $forms->div($this->html_stats(),array("id"=>"mysql_stats", "class"=>"mysql_div"));
	        print $forms->div($this->html_restore(),array("id"=>"mysql_restore", "class"=>"mysql_div"));
	        print $forms->div($this->html_step(),array("id"=>"mysql_step", "class"=>"mysql_div"));
	        print $forms->div($this->html_delete(),array("id"=>"mysql_delete", "class"=>"mysql_div"));
            print $forms->div($this->html_query(),array("id"=>"mysql_query", "class"=>"mysql_div"));
	        print $forms->div($this->html_execute(),array("id"=>"mysql_execute", "class"=>"mysql_div"));
	        print $forms->div($this->html_upload(),array("id"=>"mysql_upload", "class"=>"mysql_div"));
	        print $forms->div($this->html_hotfix(),array("id"=>"mysql_hotfix", "class"=>"mysql_div"));
            print $forms->div($this->html_auto(),array("id"=>"mysql_auto", "class"=>"mysql_div"));
            print $forms->div($this->html_xlsx(),array("id"=>"mysql_xlsx", "class"=>"mysql_div"));
            print $forms->div($this->html_protected(),array("id"=>"mysql_protected", "class"=>"mysql_div"));
	        print $forms->div($this->html_server(),array("id"=>"mysql_server", "class"=>"mysql_div"));
	        print $forms->div($this->html_stopwatch(),array("id"=>"mysql_stopwatch", "class"=>"mysql_div"));
		}
		print $forms->hidden_fields($this->input);
	    print $forms->close();
        $menu->copyright();
    }
    function function_list() {
        global $database, $forms;
    	$this->trace[]=__METHOD__;
		$results=array();
        $results['File']=array();
        $results['Database']=array();
        $results['Hotfix']=array();
        $results['Server']=array();
	    if (in_array($this->input->database,$this->constant->protected)) {
	        $results['Server']["Protected"]="MySQL Tables";
	      } else {
	        if (strlen($this->constant->scs_sql)) {
	            $results['File']["Backup"]="Backup tables";
	            $results['File']["Restore"]="Restore tables";
	            $results['File']["Step"]="Restore by step";
	            $results['File']["Upload"]="Upload & save file";
	            $results['File']["Delete"]="Delete backup file";
	        }
	        $results['Database']["Repair"]="Repair tables";
	        $results['Database']["Download"]="Download table";
	        $results['Database']["Stats"]="Statistics";
	        $results['Database']["Query"]="Query";
	        $results['Database']["Execute"]="Upload & execute";
            $results['Database']["XLSX"]="XLSX Export";
            $results['Database']["Auto"]="Auto Increment";
            $results['Hotfix']["Hotfix"]="Hotfix Conversion";
		}
        $results['Server']["Stopwatch"]="Stop Watch";
	    $results['Server']["Server"]="Server information";
        return $results;
    }
	function use_database($change) {
	    global $database, $forms;
    	$this->trace[]=__METHOD__;
	    if ($this->input->database == $this->constant->database) return;
	    $database->temp->query("use " . (($change) ? $this->input->database : $this->constant->database) );
	}
	function html_table($field) {
	    global $database, $forms;
    	$this->trace[]=__METHOD__;
	    $results=array();
	    $database->temp->query("show table status from " . $this->input->database);
	    while ($database->temp->fetch = $database->temp->fetch_array()) {
	        $item=new stdClass();
	        $item->comment=$database->temp->fetch['Comment'];
	        $item->rows=$database->temp->fetch['Rows'];
            $item->update_time=strtotime($database->temp->fetch['Update_time']);
	        $items[$database->temp->fetch['Name']]=$item;
	    }
	    $database->temp->free_result();
        if (!sizeof($items)) return;
	    $results[]="<table class='border tablesorter'>";
	    $results[]="<thead>";
	    $results[]="<tr>";
	    $results[]="<th width='10%'><span id='table_function'></span></th>";
	    $results[]="<th width='30%'>Table</th>";
	    $results[]="<th width='30%'>Description</th>";
	    $results[]="<th width='10%'># Records</th>";
	    $results[]="<th width='20%'>Last Update</th>";
	    $results[]="</tr>";
	    $results[]="</thead>";
	    $results[]="<tbody>";
	    foreach ($items as $table => $item) {
	        $results[]="<tr>";
	        $results[]="<td class=center>" . $forms->checkbox(fn_base64(array("table",$table)),1,0,array("title"=>$table,"class"=>$field)) . "</td>";
            $results[]="<td>" . $table . "</td>";
	        $results[]="<td>" . $item->comment . "</td>";
	        $results[]="<td class='right'>" . number_format($item->rows) . "</td>";
	        $results[]="<td class='center'>" . ( ($item->update_time) ? date("m/d/Y H:i",$item->update_time) : "Never") . "</td>";
	        $results[]="</tr>";
	    }
	    $results[]="</tbody>";
	    $results[]="</table>";
	    $results[]="<p class=standard>" . $forms->button("Go!",array("onclick"=>"fn_action('update',1);")) . "</p>";
	    return $results;
	}
	function html_stats() {
	    global $database, $forms;
    	$this->trace[]=__METHOD__;
		$results=array();
	    $results[]="<table class='border tablesorter'>";
	    $results[]="<thead>";
	    $results[]="<tr>";
	    $results[]="<th width='40%'>Table</th>";
	    $results[]="<th width='30%'>Description</th>";
	    $results[]="<th width='10%'>Engine</th>";
	    $results[]="<th width='10%'># Records</th>";
	    $results[]="<th width='10%'>Last Update</th>";
	    $results[]="</tr>";
	    $results[]="</thead>";
	    $results[]="<tbody>";
        foreach ($this->stats as $table => $obj) {
	        $database->temp->query("show create table `" . $this->input->database . "`.`$table`");
	        $database->temp->fetch();
	        $database->temp->query("show create table `" . $this->input->database . "`.`$table`");
	        $database->temp->fetch();
	        $create=$database->temp->fetch['Create Table'];
            $create_id=fn_base64(array("create",$table));
		    $results[]="<tr>";
            $results[]="<td>" . fn_href($table,"javascript:mysql_create('$create_id');") . "<div id='$create_id' style='display:none'>" . str_replace("\n","<br>",$create) . "</div></td>";
            $results[]="<td>" . $obj['Comment'] . "</td>";
            $results[]="<td>" . $obj['Engine'] . "</td>";
            $results[]="<td class='right'>" . number_format($obj['Rows']) . "</td>";
            $timestamp=strtotime($obj['Update_time']);
            $results[]="<td class='center'>" . ( ($timestamp) ? date("m/d/Y H:i",$timestamp) : "Never") . "</td>";
		    $results[]="</tr>";
        }
	    $results[]="</tbody>";
	    $results[]="</table>";
		return $results;
	}
	function html_query() {
	    global $database, $forms;
    	$this->trace[]=__METHOD__;
	    $results=array();
	    $results[]="<p class=standard>" . $forms->button("Next >>",array("onclick"=>"fn_action('review',0);")) . "</p>";
	    return $results;
	}
    function html_query_review() {
        global $database, $forms;
    	$this->trace[]=__METHOD__;
    	$results=array();
		$results[]="<table class='standard noborder'>";
		$results[]="<tr><td class=mysql_toolbox_input1>Query</td><td class=mysql_toolbox_input2>" . str_replace("\n","<br>",$this->input->query) . "</td></tr>";
		$results[]="<tr><td class=mysql_toolbox_input1>Show results?</td><td class=mysql_toolbox_input2>" . $forms->select('query_output',array(""=>"No output","display"=>"Display","txt"=>"Tab Delimited Text"),$this->input->query_output,FALSE) . "</td></tr>";
		$results[]="<tr id='input_query_file'><td class=mysql_toolbox_input1>Output file</td><td class=mysql_toolbox_input2>" . $forms->text("query_file",$this->input->query_file,30) . "</td></tr>";
        $results[]="</table>";
	    $text=array($forms->button("<< Back",array("onclick"=>"fn_action('',0);")));
	    $text[]=$forms->button("Execute >>",array("onclick"=>"fn_action('update',1);"));
	    $results[]="<p class=standard>" . implode(" ",$text) . "</p>";
        return implode("",$results);
    }
    function html_query_update() {
	    global $database, $forms;
    	$this->trace[]=__METHOD__;
    	$results=array();
	    $results[]="<p><b>" . $this->query_message . "</b></p>";
	    $results[]="<p>" . str_replace("\n","<br>",$this->input->query) . "</p>";
	    if ( ($this->input->query_output == "display") && (sizeof($this->results)) ) {
	        $results[]="<table class='standard border'>";
	        foreach ($this->results as $item_key => $item) {
	            $results[]="<tr>";
	            foreach ($item as $value) {
	                $results[]=( (!$item_key) ?  "<th>$value</th>" : "<td>$value</td>");
	            }
	            $results[]="</tr>";
	        }
	        $results[]="</table>";
	    }
	    $results[]="<p class=standard>" . $forms->button("Return",array("onclick"=>"fn_action('',0);")) . "</p>";
		return implode("",$results);
    }
	function html_upload() {
	global $database, $forms;
    	$this->trace[]=__METHOD__;
	    $results=array();
	    $results[]="<p>" . $forms->button("Go!",array("onclick"=>"fn_action('',0);")) . "</p>";
	    return $results;
	}
	function html_execute() {
	    global $database, $forms;
    	$this->trace[]=__METHOD__;
	    $results=array();
        $results[]="<table class='standard noborder'>";
        $results[]="<tr>";
        $results[]="<td class=mysql_toolbox_input1>Max Post Size</td>";
        $results[]="<td class=mysql_toolbox_input2>" . ini_get("post_max_size") . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td class=mysql_toolbox_input1>Max Upload</td>";
        $results[]="<td class=mysql_toolbox_input2>" . ini_get("upload_max_filesize") . "</td>";
        $results[]="</tr>";
        $results[]="</table>";
	    $results[]="<p>" . $forms->button("Next >>",array("onclick"=>"fn_action('review',0);")) . "</p>";
	    return $results;
	}
    function html_execute_review() {
        global $database, $forms;
    	$this->trace[]=__METHOD__;
        if (!file_exists($this->constant->upload_file)) {
        	$forms->error("null","","File not found: " . $this->constant->upload_file);
            return;
		}
		$items=array();
        $this->upload_fh=fopen($this->constant->upload_file,"r");
        $this->temp_fh=fopen($this->constant->temp_file,"w");
        $this->row=0;
        $classname=fn_base64(array("execute","","checked"));
        $query=array();
		while (!feof($this->upload_fh)) {
        	$contents=trim(fgets($this->upload_fh));
            if ( (!strlen($contents)) || (substr($contents,0,2) == "--") ) continue;
            $query[]=$contents;
            if (substr($contents,-1) == ";") {
				$items[]=$this->html_execute_item($query, $classname);
                $query=array();
                $this->row++;
            }
        }
        if (sizeof($query)) $items[]=$this->html_execute_item($query, $classname);
        fclose($this->upload_fh);
        fclose($this->temp_fh);
        $text=array($forms->button("<< Back",array("onclick"=>"fn_action('',0);")));
		$text[]=((!sizeof($items)) ? "No records found" : $forms->button("Execute >>",array("onclick"=>"fn_action('update',1);")));
        $buttons="<p class=standard>" . implode(" ",$text) . "</p>";
    	$results=array();
        $text=array("Database: " . $this->input->database);
	    $text[]="File name: " . $this->input->upload_name;
	    $text[]="Queries: " . number_format(sizeof($items));
	    $text[]="Max Query Length: " . number_format($this->constant->max_allowed_packet);
	    print"<p class=standard>" . implode("<br>",$text) . "</p>";
	    $results[]=$buttons;
	    $results[]="<p>On error: " . $forms->select("execute_error",array("abort"=>"Abort & End","ignore"=>"Continue & Ignore","dump"=>"Dump query"),"",FALSE) . "</p>";
	    $results[]="<p>Select all? " . $forms->checkbox($classname,1,1,array("onchange"=>"fn_checked(this," . fn_escape($classname) . ");")) . "</p>";
	    $results[]="<table class='small border tablesorter'>";
        $results[]="<thead>";
	    $results[]="<tr>";
	    $results[]="<th width='3%'>Line#</th>";
	    $results[]="<th width='3%'>Execute?</th>";
	    $results[]="<th width='4%'>Size</th>";
	    $results[]="<th width='90%' colspan=2>Query</th>";
	    $results[]="</tr>";
        $results[]="</thead>";
        $results[]="<tbody>";
		$results[]=implode("",$items);
        $results[]="</tbody>";
	    $results[]="</table>";
		$results[]="<p class=standard>" . $forms->font("orangered","<b>&nbsp;&nbsp;Field&nbsp;&nbsp;</b>","Exceeds max query length of " . number_format($this->constant->max_allowed_packet)) . "</p>";
	    $results[]=$buttons;
        return implode("",$results);
    }
    function html_execute_item($text, $classname) {
        global $database, $forms;
    	$query=implode("",$text);
        if (substr($query,-1) != ";") $query .= ";";
		fwrite($this->temp_fh,"{$query}\n");
    	$results=array();
        $results[]="<tr " . ( (strlen($query) > $this->constant->max_allowed_packet) ? $forms->background->orangered : "") . ">";
		$results[]="<td class=center>" . ($this->row + 1) . "</td>";
        $results[]="<td class=center>" .  ( (strlen($query) > $this->constant->max_allowed_packet) ? "" : $forms->checkbox(fn_base64(array("execute",$this->row)),1,1,array("class"=>$classname)) ) . "</td>";
        $results[]="<td class=right>" . number_format(strlen($query)) . "</td>";
        if (strlen($query) > 200) {
		    $results[]="<td>" . substr($query,0,80) . "</td>";
		    $results[]="<td>" . substr($query,-80) . "</td>";
		  } else {
		    $results[]="<td colspan=2>{$query}</td>";
		}
        $results[]="<tr>";
        return implode("",$results);
    }
    function html_execute_update() {
        global $database, $forms;
    	$this->trace[]=__METHOD__;
        $items=array();
        $row=0;
        $this->temp_fh=fopen($this->constant->temp_file,"r");
        $field=array("execute",0);
		$button="<p class=standard>" . $forms->button("Return",array("onclick"=>"fn_action('',0);"));
		while (!feof($this->temp_fh)) {
        	$query=fgets($this->temp_fh);
            if (array_key_exists($row, $this->results)) {
            	$item=array();
                $item[]="<tr>";
              	$item[]="<td class=center>" . ($row + 1) . "</td>";
                $item[]="<td>" . substr($query,0,100) . "</td>";
                $item[]="<td>" . $this->results[$row] . "</td>";
                $item[]="</tr>";
                $items[]=implode("",$item);
            }
            $row++;
        }
        fclose($this->temp_fh);
        $results=array();
        $results[]=$button;
	    $results[]="<table class='small border tablesorter'>";
	    $results[]="<thead>";
	    $results[]="<tr>";
	    $results[]="<th width='5%'>Line#</th>";
	    $results[]="<th width='30%'>Results</th>";
	    $results[]="<th width='65%'>Query</th>";
	    $results[]="</tr>";
	    $results[]="</thead>";
	    $results[]="<tbody>";
		$results[]=implode("",$items);
	    $results[]="</tbody>";
	    $results[]="</table>";
	    $results[]=$button;
	    $results[]="</p>";
        return implode("",$results);
    }
	function html_auto() {
	    global $database, $forms;
    	$this->trace[]=__METHOD__;
        $list=array();
        foreach ($this->stats as $table => $arr) {
            if (strlen($arr['Auto_increment'])) $list[$table]=$arr['Comment'];
        }
        $results=array();
        $results[]="<table class=noborder>";
        $results[]="<tr>";
        $results[]="<td class=mysql_toolbox_input1>Table</td>";
        $results[]="<td class=mysql_toolbox_input2>" . $forms->select("auto_table", $list, $_POST['auto_table'], TRUE, array("onchange"=>"fn_auto_increment(this.value)")) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>Auto Increment</td>";
        $results[]="<td>" . $forms->text("auto_increment", $this->auto_increment, 10, 10) . "</td>";
        $results[]="</tr>";
        $results[]="</table>";
        $results[]=$forms->button("Update Auto Increment", array("onclick"=>"fn_action('auto','');"));
        return implode("", $results);
    }
	function backup_list() {
    	$this->trace[]=__METHOD__;
	    if ( (!strlen($this->constant->scs_sql)) || (!$this->input->database) || (in_array($this->input->database,$this->constant->protected)) ) return;
	    $this->backup_list=array();
	    $this->backup_list[ $this->input->database ]=array();
	    foreach ($this->constant->database_list['Database'] as $db) {
	        $this->backup_list[$db]=array();
	    }
	    $this->backup_list['Unknown']=array();
	    $dh=dir($this->constant->scs_sql);
	    while (false !== ($filename = $dh->read())) {
	        if ( (substr($filename,0,1) == ".") || (!preg_match("/(.*)\.(sql)$/i",$filename)) ) continue;
	        $info=stat($this->constant->scs_sql . $filename);
	        $item=new stdClass();
	        $item->filename=$filename;
	        $item->name=preg_split("/\./",$filename);
	        $item->size=$info['size'];
	        $item->mtime=$info['mtime'];
            if (sizeof($item->name) == 2) {
              	$item->database=$this->input->database;
                $item->table=$item->name[0];
			  } else {
              	$item->database=$item->name[0];
                $item->table=$item->name[1];
            }
            if (!in_array($item->database,$this->constant->database_list['Database'])) $item->database="Unknown";
            if (!array_key_exists($item->table,$this->backup_list[$item->database])) $this->backup_list[$item->database][$item->table]=array();
            $keys=array(9999999999 - $item->mtime);
            $keys[]=sizeof($this->backup_list[$item->database][$item->table]);
			$this->backup_list[$item->database][$item->table][implode(":",$keys)]=$item;
		}
	    $dh->close();
	}
	function html_restore() {
	    global $database, $forms;
    	$this->trace[]=__METHOD__;
		if (!sizeof($this->backup_list)) return;
	    $results=array();
	    $results[]="<table class='standard border'>";
	    $results[]="<tr>";
	    $results[]="<th width='10%'>Restore Latest?</th>";
	    $results[]="<th width='40%'>Table Name</th>";
	    $results[]="<th width='10%'>Version</th>";
	    $results[]="<th width='20%'>Create Timestamp</th>";
	    $results[]="<th width='20%'>Size</th>";
	    $results[]="</tr>";
        foreach ($this->backup_list as $db_name => $db_items) {
	        if (!sizeof($db_items)) continue;
            ksort($db_items);
	        $db_class=fn_base64(array("restore",$db_name));
	        $results[]="<tr style='background-color: #dddddd;'>";
	        $results[]="<td class=center>" . $forms->checkbox($field,1,0,array("onclick"=>"restore_database(this," . fn_escape($db_class) . ");")) . "</td>";
	        $results[]="<td style='text-align: left;' colspan=4>Database: <b>{$db_name}</b></td>";
	        $results[]="</tr>";
	        foreach ($db_items as $table_name => $table_items) {
            	ksort($table_items);
                $row=0;
	            $table_class=fn_base64(array("restore",$db_name,$table_name));
	            $table_item=fn_base64(array("restore_checkbox",$db_name,$table_name));
                foreach ($table_items as $item) {
	                $results[]="<tr>";
                    if (!$row) {
                    	$results[]="<td class=center rowspan=" . sizeof($table_items) . ">" . $forms->checkbox($table_item,1,0,array("class"=>$db_class,"onclick"=>"restore_table(this);","id"=>$table_class)) . "</td>";
                    	$results[]="<td rowspan=" . sizeof($table_items) . ">{$table_name}</td>";
                    }
                    $results[]="<td class='center'>" . $forms->radio($table_class,$item->filename,"",array("class"=>( (!$row) ? "mysql_current" : "mysql_prior"),"onclick"=>"restore_file(" . fn_escape($table_class) . ");")) . "</td>";
                    $results[]="<td class='center'>" . fn_href( date("m/d/Y H:i.s",$item->mtime),"scs_sql/" . $item->filename,array(),array("target"=>"_blank") ) . "</td>";
	                $results[]="<td class=right>" . number_format($item->size) . "</td>";
	                $results[]="</tr>";
                    $row++;
				}
			}
		}
	    $results[]="</table>";
	    $text=array();
	    $text[]=$forms->button("Go!",array("onclick"=>"fn_action('update',1);"));
	    $results[]="<p class=standard>" . implode("",$text) . "</p>";
	    return $results;
	}
	function html_step() {
	    global $database, $forms;
    	$this->trace[]=__METHOD__;
		if (!sizeof($this->backup_list)) return;
	    $results=array();
	    $results[]="<table class='standard border'>";
	    $results[]="<tr>";
	    $results[]="<th width='10%'>Restore?</th>";
	    $results[]="<th width='50%'>Table Name</th>";
	    $results[]="<th width='20%'>Create Timestamp</th>";
	    $results[]="<th width='20%'>Size</th>";
	    $results[]="</tr>";
        foreach ($this->backup_list as $db_name => $db_items) {
	        if (!sizeof($db_items)) continue;
            ksort($db_items);
	        $db_class=fn_base64(array("restore",$db_name));
	        $results[]="<tr style='background-color: #dddddd;'>";
	        $results[]="<td class=center>&nbsp;</td>";
	        $results[]="<td style='text-align: left;' colspan=4>Database: <b>{$db_name}</b></td>";
	        $results[]="</tr>";
	        foreach ($db_items as $table_name => $table_items) {
            	ksort($table_items);
                $row=0;
	            $table_item=fn_base64(array("step_item",$db_name,$table_name));
                foreach ($table_items as $item) {
	                $results[]="<tr>";
                    $results[]="<td class='center'>" . $forms->radio("step_filename",$item->filename,"") . "</td>";
                    if (!$row) $results[]="<td rowspan=" . sizeof($table_items) . ">{$table_name}</td>";
                    $results[]="<td class='center'>" . fn_href( date("m/d/Y H:i.s",$item->mtime),"scs_sql/" . $item->filename,array(),array("target"=>"_blank") ) . "</td>";
	                $results[]="<td class=right>" . number_format($item->size) . "</td>";
	                $results[]="</tr>";
                    $row++;
				}
			}
		}
	    $results[]="</table>";
	    $text=array();
	    $text[]=$forms->button("Next >>",array("onclick"=>"fn_action('review',1);"));
	    $results[]="<p class=standard>" . implode("",$text) . "</p>";
	    return $results;
	}
	function html_delete() {
	    global $database, $forms;
    	$this->trace[]=__METHOD__;
		if (!sizeof($this->backup_list)) return;
	    $results=array();
	    $results[]="<table class='standard border'>";
	    $results[]="<tr>";
	    $results[]="<th width='50%'>Table Name</th>";
	    $results[]="<th width='10%'>Delete File?</th>";
	    $results[]="<th width='20%'>Create Timestamp</th>";
	    $results[]="<th width='20%'>Size</th>";
	    $results[]="</tr>";
        foreach ($this->backup_list as $db_name => $db_items) {
	        if (!sizeof($db_items)) continue;
	        $db_class=fn_base64(array("delete",$db_name));
	        $results[]="<tr style='background-color: #dddddd;'>";
	        $results[]="<td>Database: <b>{$db_name}</b></td>";
            $results[]="<td class=center>" . $forms->checkbox($field,1,0,array("onclick"=>"delete_database(this," . fn_escape($db_class) . ");")) . "</td>";
            $results[]="<td colspan=2>&nbsp;</td>";
	        $results[]="</tr>";
	        foreach ($db_items as $table_name => $table_items) {
            	ksort($table_items);
                $row=0;
	        	$table_class=fn_base64(array("delete",$db_name,$table_name));
                $table_checkbox=fn_base64(array("delete_checkbox",$db_name,$table_name));
                foreach ($table_items as $item) {
	                $results[]="<tr>";
                    if (!$row) $results[]="<td rowspan=" . sizeof($table_items) . ">{$table_name}</td>";
                    $table_id=fn_base64(array("delete",$db_name,$table_name,$row));
                    $results[]="<td class='center'>" . ( (!$row) ? "Current" : $forms->checkbox($table_id,$item->filename,"",array("class"=>$db_class)) ) . "</td>";
                    $results[]="<td class='center'>" . date("m/d/Y H:i.s",$item->mtime) . "</td>";
	                $results[]="<td class=right>" . number_format($item->size) . "</td>";
	                $results[]="</tr>";
                    $row++;
				}
			}
		}
	    $results[]="</table>";
	    $text=array();
	    $text[]=$forms->button("Go!",array("onclick"=>"fn_action('update',1);"));
	    $results[]="<p class=standard>" . implode("",$text) . "</p>";
	    return $results;
	}
    function html_hotfix() {
        global $database, $forms;
        $tables=array();
        foreach ($database as $table => $obj) {
            if ( (is_object($obj)) && (method_exists($database->$table, "scs_hotfix")) ) $tables[]=$table;
        }
        sort($tables);
        $results=array();
        $results[]="<table class=border>";
        $results[]="<tr>";
        $results[]="<th>Convert?</th>";
        $results[]="<th>Table</th>";
        $results[]="<th>Comment</th>";
        $results[]="<th>Current Version</th>";
        $results[]="<th>Update Version</th>";
        $results[]="<th>Instructions</th>";
        $results[]="</tr>";
        foreach ($tables as $table) {
            $database->$table->scs_hotfix();
            $obj=$database->$table->hotfix;
            $results[]="<tr>";
            $text=(strlen($obj->error)) ? "Complete" : $forms->checkbox("hotfix_checkbox_{$table}",1,0,array("onclick"=>"fn_hotfix(" . fn_escape($table) . ");"));
            $results[]="<td class=center id=hotfix_convert_{$table}>{$text}</td>";
            $results[]="<td>{$table}</td>";
            $results[]="<td>{$obj->table_comment}</td>";
            $results[]="<td class=center id=hotfix_version_{$table}>" . fn_date($obj->table_version, "ymd") . "</td>";
            $results[]="<td class=center>" . fn_date(array_key_last($obj->version),"ymd") . "</td>";
            $results[]="<td id=hotfix_message_{$table}>" . ( (strlen($obj->error)) ? $obj->error : implode("<br>", $obj->instructions) ) . "</td>";
            $results[]="</tr>";
        }
        $results[]="</table>";
        return $results;
    }
    function html_xlsx() {
        global $database, $forms;
        $results=array();
        $results[]="<table class=noborder>";
        $results[]="<tr>";
        $results[]="<td width='20%'>Table</td>";
        $results[]="<td width='80%'>" . $forms->select("xlsx_table", array_keys($this->stats), $this->input->xlsx_table, TRUE) . "</td>";
        $results[]="<tr>";
        $results[]="</table>";
        $results[]="<p>" . $forms->button("Export table", array("onclick"=>"fn_action('xlsx',1);")) . "</p>";
        return $results;
    }
	function html_protected() {
	    global $database, $forms;
    	$this->trace[]=__METHOD__;
	    if ( (!$this->input->database) || (!in_array($this->input->database,$this->constant->protected)) ) return;
	    $database->temp->query("show table status from " . $this->input->database);
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
	        $database->temp->query("show create table " . $this->input->database . ".{$table}");
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
	        $text[]=fn_href($table,"javascript:mysql_create('$create_id');");
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
	function html_server() {
	    global $database, $forms;
    	$this->trace[]=__METHOD__;
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
	    foreach ($this->server as $key => $value) {
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
    function html_stopwatch() {
	    global $database, $forms;
    	$this->trace[]=__METHOD__;
	    return $this->stopwatch->results(TRUE);
    }
    function css() {
        return <<< EOT
<style type='text/css'>
.mysql_toolbox_input1 {width: 20% !important}
.mysql_toolbox_input2 {width: 80% !important}
.mysql_div { display: none; }
</style>
EOT;
    }
    function js() {
        return <<< EOT
<script>
document.addEventListener('DOMContentLoaded', function() {
	fn_function(document.getElementById('function').value);
});
function fn_action(action,text) {
	if ( (text) && (!confirm("Continue?")) ) return;
	document.scs_form.action.value=action;
	document.scs_form.submit();
}
function fn_database() {
	document.scs_form.action.value="";
	document.scs_form.function.value="";
	document.scs_form.submit();
}
function fn_function(text) {
	obj=document.getElementById('table_function');
    if (obj) obj.innerHTML=text;
    var show_table=0;
    var show_table_name=0;
    var show_stats=0;
    var show_restore=0;
    var show_step=0;
    var show_delete=0;
    var show_query=0;
    var show_upload=0;
    var show_execute=0;
    var show_hotfix=0;
    var show_protected=0;
    var show_server=0;
    var show_xlsx=0;
    var show_auto=0;
    var show_stopwatch=0;

    var input_upload=0;
    var input_archive=0;
    var input_download_name=0;

    switch (text) {
      case 'Download':
		show_table=1;
        input_download_name=1;
        break;
      case 'Backup':
		show_table=1;
        break;
      case 'Restore':
		show_restore=1;
        break;
      case 'Step':
		show_step=1;
        break;
      case 'Repair':
		show_table=1;
        break;
      case 'Stats':
		show_stats=1;
        break;
      case 'Delete':
		show_delete=1;
        break;
      case 'Query':
		show_query=1;
        break;
      case 'Upload':
		show_upload=1;
        input_upload=1;
        input_archive=1;
        break;
      case 'Execute':
		show_execute=1;
        input_upload=1;
        break;
      case 'Auto':
		show_auto=1;
        break;
      case 'XLSX':
		show_xlsx=1;
        break;
      case 'Protected':
      	show_protected=1;
        break;
      case "Hotfix":
        show_hotfix=1;
        break;
      case 'Server':
		show_server=1;
        break;
      case 'Stopwatch':
		show_stopwatch=1;
        break;
	}
	mysql_show('mysql_table',1,show_table);
	mysql_show('mysql_stats',1,show_stats);
	mysql_show('mysql_protected',1,show_protected);
	mysql_show('mysql_restore',1,show_restore);
	mysql_show('mysql_step',1,show_step);
	mysql_show('mysql_delete',1,show_delete);
	mysql_show('mysql_query',1,show_query);
	mysql_show('mysql_upload',1,show_upload);
    mysql_show('mysql_execute',1,show_execute);
	mysql_show('mysql_hotfix',1,show_hotfix);
	mysql_show('mysql_server',1,show_server);
	mysql_show('mysql_stopwatch',1,show_stopwatch);
    mysql_show('mysql_xlsx',1,show_xlsx);
    mysql_show('mysql_auto',1,show_auto);

    mysql_show('input_table_select',0,show_table);
    mysql_show('input_query',0,show_query);

    mysql_show('input_upload',0,input_upload);
    mysql_show('input_archive',0,input_archive);
    mysql_show('input_download_name',0,input_download_name);
}
function mysql_show(id,div,show) {
	obj=document.getElementById(id);
    switch (true) {
      case (!obj):
      	return;
      case (!show):
		obj.style.display="none";
        break;
      case (!div):
      	obj.style.display="table-row";
        break;
      default:
      	obj.style.display="block";
	}
}
function mysql_create(id) {
	if (document.getElementById(id).style.display == "none") {
		div_show(id,1);
	  } else {
		div_show(id,0);
	}
}
function fn_checked(obj,id) {
	var items=document.getElementsByClassName(id);
	for(i=0; i< items.length; i++) {
    	items[i].checked=obj.checked;
    }
}
function mysql_clear(field) {
	document.getElementById(field).value='';
}
function restore_database(obj,id) {
	var items=document.getElementsByClassName(id);
	for(i=0; i < items.length; i++) {
    	items[i].checked=obj.checked;
		restore_table(items[i]);
    }
}
function restore_table(obj) {
	var items=document.getElementsByName(obj.id);
	for(j=0; j < items.length; j++) {
    	items[j].checked=false;
		if ( (obj.checked) && (items[j].className=="mysql_current") ) items[j].checked=true;
    }
}
function restore_file(id) {
	document.getElementById(id).checked=true;
}
function delete_database(obj,id) {
	var items=document.getElementsByClassName(id);
	for(i=0; i < items.length; i++) {
    	items[i].checked=obj.checked;
		delete_table(items[i]);
    }
}
function delete_table(obj) {
	var items=document.getElementsByClassName(obj.id);
	for(j=0; j < items.length; j++) {
		items[j].checked=obj.checked;
    }
}
function delete_file(id) {
	document.getElementById(id).checked=true;
}
function fn_auto_increment(table) {
    jQuery.ajax({
        dataType: 'json',
		method: 'post',
        data: { action: 'auto', table: table },
        success: function(response) { scscpq_fill(response) }
    });
}
function fn_hotfix(table) {
    jQuery("#hotfix_checkbox_" + table).prop("checked", false);
    if (!confirm("Apply hotfix to table " + table + "?")) return;
    jQuery("#hotfix_convert_" + table).html("Working ...");
    jQuery.ajax({
        dataType: 'json',
		method: 'post',
        data: { action: 'hotfix', table: table },
        success: function(response) { scscpq_fill(response) },
        error: function(response) { jQuery("#hotfix_convert_" + table).html("Internal Error") }
    });
}
</script>
EOT;
    }
}
class mysql_toolbox_constant {
	var $database;
	var $scs_sql;
    var $backup_suffix;
    var $linux;
    var $max_allowed_packet=0;
	var $protected=array("information_schema","mysql","performance_schema");
    var $database_list=array();
	var $execute_temp="scs_mysqltoolbox.tmp";
	var $execute_sql="scs_mysqltoolbox.sql";
    var $upload_file;
    var $temp_file;
	function __construct($options) {
        global $database, $forms;
        $this->backup_suffix=( (!fn_development_server()) && ($options['timestamp']) ) ? "." . date("Ymd") : "";
		$database->temp->query("show variables where Variable_name='max_allowed_packet'");
        $database->temp->fetch();
        $this->max_allowed_packet=intval($database->temp->fetch['Value']);
		list($this->upload_file,$this->temp_file)=preg_split("/\\^/",$_SESSION['mysqltoolbox']);
        if (!strlen($this->upload_file)) $this->upload_file=tempnam(sys_get_temp_dir(), 'mysql');
        if (!strlen($this->temp_file)) $this->temp_file=tempnam(sys_get_temp_dir(), 'mysql');
		$_SESSION['mysqltoolbox']=$this->upload_file . "^" . $this->temp_file;
	    $this->database=$database->meta->database;
        if (!$this->scs_sql($options['folder'], ".scs_sql")) $this->scs_sql($options['folder'], "scs_sql");
        $database->temp->query("show variables like 'version_compile_os'");
        $database->temp->fetch();
        $this->linux=( (preg_match("/^win/i",$database->temp->fetch['Value'])) ? 1 : 0);
        $this->database_list['Database']=array();
	    $this->database_list['Protected']=array();
        if (!$options['database']) {
        	$this->database_list['Database'][]=$database->meta->database;
		  } else {
	        $database->temp->query("show databases");
	        while ($database->temp->fetch = $database->temp->fetch_array()) {
	            if (in_array($database->temp->fetch['Database'],$this->protected)) {
	                $this->database_list['Protected'][ $database->temp->fetch['Database'] ]=$database->temp->fetch['Database'];
	              } else {
	                $this->database_list['Database'][ $database->temp->fetch['Database'] ]=$database->temp->fetch['Database'];
	            }
	        }
		}
	}
    function scs_sql($subfolder, $folder) {
        $text=array();
        $text[]=(substr($_SERVER['DOCUMENT_ROOT'], -1) == "/") ? substr($_SERVER['DOCUMENT_ROOT'], 0, -1) : $_SERVER['DOCUMENT_ROOT'];
        if (strlen($subfolder)) $text[]=$subfolder;
        $text['folder']=$folder;
        $folder=implode("/", $text);
        if (is_dir($folder)) {
            $this->scs_sql="{$folder}/";
            return TRUE;
        }
    }
}
class mysql_hotfix_class {
    var $table;
    var $table_comment;
    var $table_version;
    var $version=array();
    var $instructions=array();
    var $query=array();
    var $error;
    var $meta;
    function __construct($table, $table_comment) {
        global $database;
        $this->table=$table;
        $this->table_comment=$table_comment;
        $this->table_version=fn_date($database->table_comment($table), "mdy");
        $this->trace=array();
        $this->error="";
    }
    function version($date, $comment) {
        $this->version[fn_date($date, "mdy")]=$comment;
    }
    function instructions($text) {
        $this->instructions=(is_array($text)) ? $text : array($text);
    }
    function process($execute) {
        global $database;
        ksort($this->version);
        $this->error=$this->eligible();
        if ( (!$execute) || ($this->error) ) return;
        array_shift($this->version);
        foreach ($this->version as $date => $comment) {
            if ($date <= $this->table_version) continue;
            $function="scs_hotfix_" . date("Ymd",strtotime($date));
            if (!method_exists($database->{$this->table}, $function)) {
                $this->error="Missing function: {$function}";
                break;
            }
            $database->transaction("start");
            $meta=new stdClass();
            $meta->count=0;
            $meta->comment=array();
            $this->meta=$meta;
            $this->error=$database->{$this->table}->$function($this->meta);
            $text=array();
            $text[]="Version " . fn_date($date, "ymd");
            $text[]="From " . fn_date($this->table_version, "ymd");
            $text[]=number_format($this->meta->count) . " records";
            $text[]="({$comment})";
            if ($this->error) $text[]="(Errors detected)";
            $this->meta->comment[]=implode(" ", $text);
            if ($this->error) {
                $database->transaction("rollback");
                $this->meta->comment[]="Error: " . $this->meta->error;
              } else {
                $this->table_version=$date;
                $query=array("alter table `{$this->table}`");
                $query[]="COMMENT = " . fn_escape("{$this->table_comment} (" . fn_date($this->table_version, "ymd") . ")");
                $database->{$this->table}->query($query);
                $this->query[]=implode(" ", $query);
                $database->transaction("commit");
            }
            $type=array();
            $type[]=($this->meta->error) ? "Hotfix error" : "Hotfix";
            $type[]=$this->table;
            $database->log->ipc($type, $this->meta->comment);
            if ($this->error) break;
        }
    }
    function eligible() {
        switch (TRUE) {
          case (!$this->table_version):
            return "Table comment missing";
          case (!sizeof($this->version)):
            return "Revision table empty";
          case (!array_key_exists($this->table_version, $this->version)):
            return "Conversion not available from version " . $this->table_version;
          case ($this->table_version == array_key_last($this->version)):
            return "Up to date";
          case (sizeof($this->version) < 2):
            return "Nothing to convert";
        }
    }
    function alter($fields) {
        global $database;
        if ( (!is_array($fields)) || (!sizeof($fields)) ) die("not an array");
        $query=array("alter table `{$this->table}`");
        $query[]=implode(",\n", $fields);
        $database->temp->query($query);
        $this->query[]=implode(" ", $query);
        return $database->temp->affected_rows();
    }
    function update($fields, $join="") {
        global $database;
        if ( (!is_array($fields)) || (!sizeof($fields)) ) die("not an array");
        $query=array("update `{$this->table}`");
        if (strlen($join)) $query[]=$join;
        $query[]="set " . implode(", ", $fields);
        $database->temp->query($query);
        $this->query[]=implode(" ", $query);
        return $database->temp->affected_rows();
    }
}
?>
