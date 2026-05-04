<?php
header("Access-Control-Allow-Origin: *");
$obj=new mysql_access();
class mysql_access {
	var $action;
    var $query;
    var $folder;
	function scs_table_version() {
		$results=array();
        $results['05/16/2024']="Support scs_sql & .scs_sql";
        $results['02/01/2022']="Improve show_tables";
        $results['09/29/2020']="Add memory_limit";
        $results['07/22/2020']="Rewrite";
        $results['05/26/2020']="Revise output";
        $results['04/30/2020']="Initial Release";
        $results['file']=__FILE__;
        return $results;
    }
	function __construct() {
    global $database, $forms, $menu;
    	$this->action=$_POST['action'];
    	$this->query=trim($_POST['query']);
        $this->response=new stdClass();
        $this->response->error="0";
        $this->response->version=key($this->scs_table_version());
        $this->response->options_html="&nbsp;";
        $this->response->command_html="&nbsp;";
    	switch (TRUE) {
          case ($_SERVER['REQUEST_METHOD'] != "POST"):
          	$this->response->error="102";
            break;
          case (!strlen($this->action)):
          	$this->response->error="103";
            break;
          case (!method_exists($this, $this->action)):
          	$this->response->error="104";
            break;
          default:
          	$this->options();
          	$this->response->error=$this->{$this->action}();
		}
        if ($this->response->error) {
          	header("HTTP/1.1 401 Unauthorized");
            $this->response->status_html="Action " . $this->action . ": " . $this->response->error;
        	$this->response->table_html="&nbsp;";
		  } else {
          	$this->response->memory_limit_html=ini_get("memory_limit");
		}
		$fh=fopen("mysql_remote.log","a");
        $text=array();
        $text[]=$_SERVER['REMOTE_ADDR'];
        $text[]=date("m/d/Y H:i.s");
        $text[]=$this->action;
        $text[]=$_POST['data'];
        $text[]=$this->response->error;
        fwrite($fh,implode("\t",$text) . "\n");
        fclose($fh);
		die (json_encode($this->response));
    }
    function connection() {
    global $database, $forms, $menu;
    	$error=0;
    	switch (TRUE) {
          case ($_POST['connection']['host'] != $database->meta->host):
          	$error=201;
            break;
          case ($_POST['connection']['user'] != $database->meta->user):
          	$error=202;
            break;
          case ($_POST['connection']['password'] != $database->meta->password):
          	$error=203;
            break;
          case ($_POST['connection']['database'] != $database->meta->database):
          	$error=204;
            break;
		}
        if (!$error) $this->response->status_html="Connection successful";
        return $error;
    }
    function load() {
    global $database, $forms, $menu;
		$error=$this->connection();
        if ($error) return $error;
        switch (TRUE) {
          case (is_dir(".scs_sql")):
            $this->folder=".scs_sql";
            break;
          case (is_dir("scs_sql")):
            $this->folder="scs_sql";
            break;
          default:
            return 301;
        }
        $this->response->folder=$this->folder;
        return $this->load_table();
	}
    function options() {
    global $database, $menu, $forms;
		$results=array();
        $results[]="<table class='standard border'>";
        $results[]="<tr>";
        $results[]="<th width='20%'>Command Action</th>";
        $results[]="<th width='80%'>Query</th>";
        $results[]="</tr>";
	    $results[]="<tr>";
	    $results[]="<td>" . $forms->select("options",array("show_tables","show_databases","query"),"",TRUE,array("onchange"=>"fn_action(this,'');")) . "</td>";
        $results[]="<td>" . $forms->textarea("query",$this->query,3,80) . "</td>";
	    $results[]="</tr>";
        $results[]="</table>";
        $this->response->options_html=implode($results);
	}
    function output($caption) {
    global $database, $forms, $menu;
    	$first_pass=FALSE;
    	$results=array();
        $results[]="<table class='standard border'>";
        $results[]=$forms->caption("<br><b>{$caption}</b>");
        while ($database->temp->fetch = $database->temp->fetch_array() ) {
        	if (!$first_pass) {
            	$first_pass=TRUE;
            	$results[]="<tr>";
            	foreach ($database->temp->fetch as $key => $value) {
                	$results[]="<th>{$key}</th>";
                }
            	$results[]="</tr>";
            }
            $results[]="<tr>";
            foreach ($database->temp->fetch as $key => $value) {
            	$results[]="<td>{$value}</td>";
            }
            $results[]="</tr>";
		}
        $results[]="</table>";
        return implode("",$results);
    }
    function show_tables() {
    global $database, $forms, $menu;
    	$results=array();
        $results[]="<table class='standard border'>";
        $results[]=$forms->caption("<br><b>Show Tables</b>");
        $results[]="<tr>";
        $results[]="<th>Table</th>";
        $results[]="<th>Description</th>";
        $results[]="<th>Engine</th>";
        $results[]="<th># Records</th>";
        $results[]="<th>Last Update</th>";
        $results[]="</tr>";
        $database->temp->query("show table status");
        $tables=array();
        while ($database->temp->fetch = $database->temp->fetch_array() ) {
        	$results[]="<tr>";
            $results[]="<td>" . $database->temp->fetch['Name'] . "</td>";
            $results[]="<td>" . $database->temp->fetch['Comment'] . "</td>";
            $results[]="<td>" . $database->temp->fetch['Engine'] . "</td>";
			$results[]="<td class=right>" . number_format($database->temp->fetch['Rows']) . "</td>";
            $timestamp=strtotime($database->temp->fetch['Update_time']);
            $results[]="<td class=center>" . ( ($timestamp) ? date("m/d/Y H:i.s",$timestamp) : "Never") . "</td>";
        	$results[]="</tr>";
        }
        $results[]="</table>";
    	$this->response->command_html=implode("",$results);
    }
    function show_databases() {
    global $database, $forms, $menu;
        $database->temp->query("show databases");
    	$this->response->command_html=$this->output("Show Databases");
    }
    function query() {
    global $database, $forms, $menu;
    	if (!strlen($this->query)) return;
        $database->temp->meta->error_abort=FALSE;
    	$results=array();
        $text=array("Query:");
        $text[]=$this->query;
		$database->temp->query($this->query);
        if ($database->temp->meta->error) {
        	$text[]=$database->temp->meta->error;
		  } else {
          	$text[]="Records found: " . number_format($database->temp->meta->rows);
          	$text[]="Affected rows: " . number_format($database->temp->affected_rows());
        }
        $results[]="<p>" . implode("<br>",$text) . "</p>";
        if ( (!$database->temp->meta->error) || ($database->temp->meta->rows) ) $results[]=$this->output("Query");
    	$this->response->command_html=implode("",$results);
    }
    function load_table($content=array()) {
    global $database, $forms, $menu;
	    $dh=dir($this->folder);
        $items=array();
	    while (false !== ($filename = $dh->read())) {
	        if ( (substr($filename,0,1) == ".") || (!preg_match("/(.*)\.(sql)$/i",$filename)) ) continue;
			$text=array();
            $text[]="<tr>";
            $text[]="<td id=" . fn_base64(array("results",$filename)) . ">";
            $text[]=$forms->select(fn_base64(array("action",$filename)),array("run","hide"),"",TRUE,array("onchange"=>"fn_action(this," . fn_escape($filename) . ");"));
            $text[]="</td>";
            $text[]="<td>{$filename}</td>";
            $text[]="</tr>";
            $items[]=implode("",$text);
		}
        if (!sizeof($items)) $items[]="<tr><td>No files found</td><td>&nbsp;</td></tr>";
    	$results=array(implode("",$content));
        $results[]="<table class='standard border'>";
        $results[]=$forms->caption("<br><b>Import Tables</b>");
        $results[]="<tr>";
        $results[]="<th width='20%'>Import Action</th>";
        $results[]="<th width='80%'>Table</th>";
        $results[]="</tr>";
        $results[]=implode("",$items);
        $results[]="</table>";
		$this->response->table_html=implode("",$results);
        return 0;
    }
    function hide() {
    global $database, $forms, $menu;
        $field=fn_base64(array("results",$_POST['data']));
	    $this->response->$field="Hidden";
	    $this->response->status_html="Hide: " . $_POST['data'];
        return 0;
    }
    function run() {
    global $database, $forms, $menu;
		$error=$this->connection();
        if ($error) return $error;
        $this->folder=(is_dir(".scs_sql")) ? ".scs_sql" : "scs_sql";
        $name=$_POST['data'];
        $filename="{$this->folder}/{$name}";
        switch (TRUE) {
          case (!strlen($_POST['data'])):
          	return 401;
          case (!file_exists($filename)):
          	return 402;
		}
        set_time_limit(600);
        if ($_POST['memory_limit']) ini_set("memory_limit",$_POST['memory_limit']);
		$pdo=new PDO("mysql:host={$database->meta->host};dbname={$database->meta->database}", $database->meta->user, $database->meta->password);
		$query=file_get_contents($filename);
		$stmt=$pdo->prepare($query);
        $stmt_status=$stmt->execute();
        $comment=array();
        if (!$stmt_status) $comment[]="(Possible error)";
		$this->response->status_html=implode(" ",$comment);
        $field=fn_base64(array("results",$name));
	    $this->response->$field=( (!$stmt_status) ? "Run (possible error)" : "Run successful");
	}
}
?>