<?php
require_once "classes/salesforce.php";
class salesforce_toolbox_class {
	var $results=array();
	function scs_table_version() {
		$results=array();
        $results['01/28/2021']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
	function __construct($role,$options=array()) {
    global $database, $forms, $menu;
		$database->user->access($role);
	    $this->action=$_POST['action'];
        $this->options=$options;
        if (!isset($this->options['title'])) $this->options['title']="Salesforce Toolbox";
        if (!isset($this->options['administrator'])) $this->options['administrator']=FALSE;
    	if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        	$this->json();
		  } else {
          	$this->html();
        }
    }
	function html() {
    global $database, $forms, $menu;
	    $forms->title($this->options['title']);
	    $forms->message[]="Revised " . key($this->scs_table_version());

	    $forms->report['server']=$_POST['server'];
	    $forms->report['connect_server']=trim($_POST['connect_server']);
	    $forms->report['connect_username']=trim($_POST['connect_username']);
	    $forms->report['connect_password']=trim($_POST['connect_password']);
	    $forms->report['connect_token']=trim($_POST['connect_token']);
	    $forms->report['connect_wsdl']=trim($_POST['connect_wsdl']);
	    $forms->report['query']=trim($_POST['query']);
	    $forms->report['search']=trim($_POST['search']);
	    $forms->report['delete_id']=array();
	    if (strlen($_POST['delete_id'])) {
	        $items=preg_split("/\n/",$_POST['delete_id']);
	        foreach ($items as $item) {
	            $item=trim($item);
	            if ( (strlen($item)) && (!in_array($item,$forms->report['delete_id'])) ) $forms->report['delete_id'][]=$item;
	        }
	    }
	    $forms->report['upsert_table']=trim($_POST['upsert_table']);
	    $forms->report['upsert_id']=trim($_POST['upsert_id']);
		$forms->report['upsert_content']=trim($_POST['upsert_content']);
	    switch ($this->action) {
	      case "":
	        $server=( ($database->registry->salesforce->server) ? $database->registry->salesforce->server : "sandbox");
	        $forms->report['server']=$server;
	        $forms->report['connect_server']=$database->registry->salesforce->server;
	        $forms->report['connect_username']=$database->registry->salesforce->{$server}['username'];
	        $forms->report['connect_password']=$database->registry->salesforce->{$server}['password'];
	        $forms->report['connect_token']=$database->registry->salesforce->{$server}['token'];
	        $forms->report['connect_wsdl']=$database->registry->salesforce->{$server}['wsdl'];
	        break;
	      case "Connect":
	        if (!$forms->report['connect_username']) $forms->error('connect_username');
	        if (!$forms->report['connect_password']) $forms->error('connect_password');
	        if (!$forms->report['connect_token']) $forms->error('connect_token');
	        if (verify_file($forms->report['connect_wsdl'],"xml",TRUE)) $forms->error('connect_wsdl');
	        break;
	      case "Query":
	        if (!$forms->report['query']) $forms->error('query');
	        break;
	      case "Search":
	        if (!$forms->report['search']) $forms->error('search');
	        break;
	      case "Delete":
	        if (!sizeof($forms->report['delete_id'])) $forms->error('delete_id');
	        break;
	      case "Upsert":
          	if (!strlen($forms->report['upsert_table'])) $forms->error('upsert_table',"Cannot be blank");
	        $this->upsert=array();
	        $lines=explode("\n",$forms->report['upsert_content']);
	        $errors=array();
	        foreach ($lines as $line) {
	            $line=trim($line);
	            if (!strlen($line)) continue;
	            $data=preg_split("/\=/",$line,2);
	            switch (TRUE) {
	              case (sizeof($data) <> 2):
	                $errors[]="Malformed: {$line}";
	                break;
	              case (array_key_exists($data[0],$this->upsert)):
	                $errors[]="Already defined: {$line}";
	                break;
	              default:
	                $this->upsert[ $data[0] ]=$data[1];
	            }
	        }
	        switch (TRUE) {
	          case (sizeof($errors)):
	            $forms->error('upsert_content',implode("<br>",$errors));
	            break;
	          case (!sizeof($this->upsert)):
	            $forms->error('upsert_content',"Cannot be blank");
	            break;
	        }
			break;
	    }

	    $menu->head();
	    $forms->message();
        $this->js();
	    print $forms->open();
	    print $forms->hidden("action");

		$sf=new SforceEnterpriseClient();
        print "<div>" . $sf->printDebugInfo() . "</div>";

	    $server=array();
	    if (isset($database->registry->salesforce->production)) $server[]="production";
	    if (isset($database->registry->salesforce->sandbox)) $server[]="sandbox";
	    print "<p class='standard'>Server: " . $forms->select("server",$server,$forms->report['server'],FALSE) . "</p>";

	    $menu->tabs=new jquery_tab_class('standard');
	    $menu->tabs->landing_tab($this->action,TRUE);
	    $menu->tabs->item("Connect",$this->connect());
		$menu->tabs->item("Query",$this->query());
		$menu->tabs->item("Search",$this->search());
		if ($this->options['administrator'])  $menu->tabs->item("Upsert",$this->upsert());
		if ($this->options['administrator']) $menu->tabs->item("Delete",$this->delete());
		$menu->tabs->item("Types",$this->gettypes());
	    $menu->tabs->output();
		print $forms->close();
        $menu->copyright();
    }
    function connect() {
	    global $database, $forms, $menu;
        $results=array();
        $results[]="<table class='standard noborder'>";
        $results[]="<tr>";
        $results[]="<td width='30%'>Server</td>";
        $results[]="<td width='70%'>" . $forms->select("connect_server",array("production","sandbox"),$forms->report['connect_server'],FALSE,array("onchange"=>"fn_server();")) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td width='30%'>User name</td>";
        $results[]="<td width='70%'>" . $forms->text("connect_username",$forms->report['connect_username'],60) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>Password</td>";
        $results[]="<td>" . $forms->text("connect_password",$forms->report['connect_password'],30) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>Token</td>";
        $results[]="<td>" . $forms->text("connect_token",$forms->report['connect_token'],30) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>WSDL XML File</td>";
        $results[]="<td>" . $forms->text("connect_wsdl",$forms->report['connect_wsdl'],60) . "</td>";
        $results[]="</tr>";
        $results[]="</table>";
        $results[]="<p>" . $forms->button("Go!",array("onclick"=>"fn_action('Connect');")) . "</p>";
        switch (TRUE) {
          case (sizeof($forms->error)):
          case ($this->action != "Connect"):
            break;
          default:
            $options=array();
            $options['debug']=TRUE;
            $options['username']=$forms->report['connect_username'];
            $options['password']=$forms->report['connect_password'];
            $options['token']=$forms->report['connect_token'];
            $options['wsdl']=$forms->report['connect_wsdl'];
            $menu->crm->login($options);
            $results[]=$this->status();
        }
        return $results;
    }
	function status($message="") {
	global $database, $forms, $menu;
	    $text=array();
	    switch (TRUE) {
	      case ($menu->crm->meta->error):
	        $status=$forms->font("red",$menu->crm->meta->error);
	        break;
	      case ($menu->crm->error):
	        $status=$forms->font("red",$menu->crm->error);
	        break;
	      default:
	        $status="<b>Success</b>";
	    }
	    $text[]=$this->action . " status: " . $status;
		$text[]="Records: <b>" . number_format(sizeof($menu->crm->results)) . "</b>";
        $results=array();
        $results[]=implode("<br>",$text);
        $results[]=parse_object($menu->crm->results);
		return implode("\n",$results);
	}
	function query() {
	global $database, $forms, $menu;
	    $results=array();
	    $results[]="<p>" . $forms->textarea("query",explode("\n",$forms->report['query']),3,118) . "</p>";
	    $results[]="<p>" . $forms->button("Go!",array("onclick"=>"fn_action('Query');")) . "</p>";
	    switch (TRUE) {
	      case (sizeof($forms->error)):
	      case ($this->action != "Query"):
	        break;
	      default:
	        $menu->crm->login(array("server"=>$forms->report['server']));
	        $menu->crm->query($forms->report['query']);
	        $results[]=$this->status();
	    }
	    return $results;
	}
    function search() {
    global $database, $forms, $menu;
        $results=array();
        $text=array();
        $text[]="FIND {313} IN Phone FIELDS RETURNING<br>Contact(Id, Phone, FirstName, LastName, email),<br>Lead(Id, Phone, FirstName, LastName, email),<br>Account(Id, Phone, Name)<br>limit 5";
        $text[]=$forms->textarea("search",explode("\n",$forms->report['search']),3,118);
        if ($forms->error_text['search']) $text[]=$forms->error_text['search'];
        $results[]="<p>" . implode("<br>",$text) . "</p>";
        $results[]="<p>" . $forms->button("Go!",array("onclick"=>"fn_action('Search');")) . "</p>";
        switch (TRUE) {
          case (sizeof($forms->error)):
          case ($this->action != "Search"):
            break;
          default:
            $menu->crm->login(array("server"=>$forms->report['server']));
            $menu->crm->search($forms->report['search']);
            $results[]=$this->status();
        }
        return $results;
    }
    function upsert() {
    global $database, $forms, $menu;
        $results=array();
        $results[]="<table class='standard noborder'>";
        $results[]="<tr>";
        $results[]="<td width='30%'>Table</td>";
        $text=array($forms->text("upsert_table",$forms->report['upsert_table'],30));
        if ($forms->error_text['upsert_table']) $text[]=$forms->error_text['upsert_table'];
	    $results[]="<td width='70%'>" . implode("<br>",$text) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td width='30%'>ID</td>";
        $text=array($forms->text("upsert_id",$forms->report['upsert_id'],30));
        if ($forms->error_text['upsert_id']) $text[]=$forms->error_text['upsert_id'];
	    $results[]="<td width='70%'>" . implode("<br>",$text) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>Content</td>";
        $text=array("Format: field=value");
        $text[]=$forms->textarea("upsert_content",$forms->report['upsert_content'],10,80);
        if ($forms->error_text['upsert_content']) $text[]=$forms->error_text['upsert_content'];
        $results[]="<td>" . implode("<br>",$text) . "</td>";
        $results[]="</tr>";
        $results[]="</table>";
        $results[]="<p>" . $forms->button("Upsert",array("onclick"=>"fn_action('Upsert');")) . "</p>";
        switch (TRUE) {
          case (sizeof($forms->error)):
          case ($this->action != "Upsert"):
            break;
          default:
            $results[]="<pre>" . print_r($this->upsert,TRUE) . "</pre>";
            $menu->crm->login(array("server"=>$forms->report['server']));
            $menu->crm->upsert($forms->report['upsert_table'],(object) $this->upsert);
            $results[]=$this->status();

        }
        return $results;
    }

	function delete() {
	global $database, $forms, $menu;
	    $results=array();
	    $results[]="<table class='standard noborder'>";
	    $results[]="<tr>";
	    $results[]="<td width='30%'>Record ID</td>";
        $text=array($forms->textarea("delete_id",$forms->report['delete_id'],12,30));
        if ($forms->error_text['delete_id']) $text[]=$forms->error_text['delete_id'];
	    $results[]="<td width='70%'>" . implode("<br>",$text) . "</td>";
	    $results[]="</tr>";
	    $results[]="</table>";
	    $results[]="<p>" . $forms->button("Delete",array("onclick"=>"fn_action('Delete');")) . "</p>";
	    switch (TRUE) {
	      case (sizeof($forms->error)):
          case ($this->action != "Delete"):
	        break;
	      default:
	        $menu->crm->login(array("server"=>$forms->report['server']));
	        $menu->crm->delete($forms->report['delete_id']);
	        $results[]=$this->status();
	    }
	    return $results;
	}

	function gettypes() {
	global $database, $forms, $menu;
	    $results=array();
	    $results[]="<table class='standard border'>";
	    $results[]="<tr>";
	    $results[]="<td width='50%' id=gettypes_list></td>";
	    $results[]="<td width='50%' id=gettypes_table></td>";
	    $results[]="</tr>";
	    $results[]="</table>";
	    return $results;
	}
    function js() {
?>
<script>
function fn_action(action) {
	document.scs_form.action.value=action;
	document.scs_form.submit();
}
function fn_server() {
	jQuery.ajax({
		type: "post",
	    data: { action: 'server', ajax_id: jQuery('#connect_server').val() },
	    dataType: 'json',
	    success:function(data){
        	jQuery('#connect_username').val(data['username']);
        	jQuery('#connect_password').val(data['password']);
        	jQuery('#connect_token').val(data['token']);
        	jQuery('#connect_wsdl').val(data['wsdl']);
        }
	});
}
function fn_gettypes(table) {
	jQuery.ajax({
		type: "post",
	    data: { action: 'gettypes', ajax_id: jQuery('#connect_server').val(), type: table },
	    dataType: 'json',
	    success:function(data){
        	if (table) {
        		jQuery("#gettypes_table").html(data['html']);
                document.getElementById('gettypes_table').scrollIntoView()
			  } else {
        		jQuery("#gettypes_list").html(data['html']);
			}
        }
	});
}
jQuery( document ).ready(function() {
    fn_gettypes("");
});
</script>
<?php
    }
	function json() {
    global $database, $forms, $menu;
	    $this->results["status"]="Unknown error";
	    if ($this->action != "server") $menu->crm->login();
	    switch (TRUE) {
	      case ($this->action=="server"):
	        if (!isset($database->registry->salesforce->{$_POST['ajax_id']})) {
	            $this->results['status']="Server not defined";
	           } else {
	            $this->results['status']="Server: " . $_POST['ajax_id'];
	            foreach ($database->registry->salesforce->{$_POST['ajax_id']} as $key => $value) {
	                $this->results[$key]=$value;
	            }
	        }
	        break;
	      case ($menu->crm->meta->error):
	        $this->results['status']=$menu->crm->meta->error;
	        break;
	      case ($this->action=="gettypes"):
	        $menu->crm->gettypes($_POST['type']);
	        $html=array();
	        $this->results['status']="";
	        switch (TRUE) {
	          case (!sizeof($this->results)):
	            $html[]="Table " . $_POST['type'] . " not found";
	            break;
	          case (!strlen($_POST['type'])):
	            $text=array();
	            foreach ($menu->crm->results as $item => $obj) {
	                $key=strtoupper(substr($item,0,1));
	                if (!array_key_exists($key,$text)) $text[$key]=array();
	                $text[$key][strtoupper($item)]=fn_href($item,"javascript:fn_gettypes(" . fn_escape($item) . ");");
	            }
	            ksort($text);
	            foreach ($text as $key => $items) {
	                ksort($items);
	                $content=array("<b>{$key}</b>");
	                foreach ($items as $item) {
	                    $content[]=$item;
	                }
	                $html[]="<div class='small newspaper2'>" . implode("<br>",$content) . "</div><br>";
	            }
	            break;
	          default:
	            $html[]="<div class='standard'><b>" . $_POST['type'] . "</b></div>";
	            $html[]="<table class='small border'>";
	            $html[]="<thead>";
	            $html[]="<tr>";
	            $html[]="<th>Field</th>";
	            $html[]="<th>Type</th>";
	            $html[]="</tr>";
	            $html[]="</thead>";
	            $html[]="<tbody>";
	            foreach ($menu->crm->results[$_POST['type']] as $key => $value) {
	                $html[]="<tr>";
	                $html[]="<td>$key</td>";
	                $html[]="<td>$value</td>";
	                $html[]="</tr>";
	            }
	            $html[]="</table>";
	        }
	        $this->results['html']=implode(" ",$html);
	        break;
	      default:
	        $this->results['status']="Unknown action: " . $this->action;
	    }
	    die(json_encode($this->results));
	}
}
?>