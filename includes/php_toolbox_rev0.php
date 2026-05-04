<?php
if (!isset($menu)) {
	session_start();
	$forms=new standalone_forms();
	$menu=new standalone_menu();
	$obj=new php_toolbox("",FALSE);
}
class standalone_menu {
    function head() {
    global $forms;
    	print "<!DOCTYPE html>";
        print "<html>";
        print "<head>";
        print "<title>" . $forms->message[0] . "</title>";
        print "<meta charset='utf-8'>";
        print "</head>";
        print "<body>";
    }
    function copyright() {
    	print "</body>";
    	print "</html>";
        die();
    }
}
class standalone_forms {
    var $message=array();
	function title($text) {
    	$this->message[]=$text;
    }
    function message() {
		print "<div>" . implode("<br>",$this->message) . "</div>";
    }
    function select($field, $data, $compare, $blank=TRUE, $forms_options=array()) {
        $results=array();
        $text=array("select");
        $text[]="name={$field}";
        $text[]="id={$field}";
        if ($forms_options['onchange']) $text[]="onchange=\"" . $forms_options['onchange'] . "\"";
		$results[]="<" . implode(" ",$text) . ">";
        if ($blank) $results[]="<option></option>";
        foreach ($data as $key => $value) {
        	$text=array("option");
            $text[]="value={$key}";
            if ($key == $compare) $text[]="selected";
            $results[]="<" . implode(" ",$text) . ">$value</option>";
        }
		$results[]="</select>";
        return implode("",$results);
    }
}
class php_toolbox {
	function scs_table_version() {
		$results=array();
        $results['07/08/2025']="Allow impersonate access";
        $results['08/20/2020']="Initial release";
        return $results;
    }
	function __construct($access, $local=TRUE) {
    global $database, $menu, $forms;
    	$this->local=$local;
		if ( ($this->local) && (!$_SESSION['impersonate']) ) $database->user->access($access);
        $this->options=array();
        $this->options['status']="Session Variables";
        $this->options['module']="Installed Modules";
        $this->options['php']="PHP Information";
        $this->options['session']="Remove Session Variable";
        $this->options['cookie']="Remove Cookie";
        $this->action=trim(strtolower($_REQUEST['action']));
		$forms->title("PHP Toolbox " . key($this->scs_table_version()));
        if ( (strlen($this->action)) && (isset($this->options[$this->action])) ) $forms->message[]=$this->options[$this->action];
		switch ($this->action) {
	      case "cookie":
          	foreach ($_REQUEST as $key => $value) {
            	$field=explode("\t",base64_decode($key));
				if ($field[0] == "cookie") {
                	$forms->message[]="Cookie removed: " . $field[1];
                	setcookie($field[1], null, -1, '/');
				}
            }
          	break;
	      case "session":
          	foreach ($_REQUEST as $key => $value) {
            	$field=explode("\t",base64_decode($key));
				if ($field[0] == "session") {
                	$forms->message[]="Session variable removed: " . $field[1];
                	unset($_SESSION[ $field[1] ]);
				}
            }
	        break;
        }
	    $menu->head();
	    $forms->message();
?>
<script>
function toolbox_remove(obj, action, name) {
	if (!confirm("Remove " + action + ": " + name + " ?")) {
    	obj.checked=false;
		return;
    }
    obj.form.action.value=action;
    obj.form.submit();
}
</script>
<style>
table.scs_toolbox {width: 100%; border: 1px #000 solid; border-collapse: collapse;}
table.scs_toolbox th {border: 1px #000 solid; padding: 2px; background: #ddd; vertical-align: bottom; text-align: center;}
table.scs_toolbox td {border: 1px #000 solid; padding: 2px; vertical-align: top;}
table.scs_toolbox tr:hover {background: #eee;}
</style>
<?php
		print "<form name='scs_form' method='get'>";
//	    print $forms->open(array("method"=>"GET"));
        print "<p>Action: " . $forms->select("action",$this->options,"",TRUE,array("onchange"=>"this.form.submit();")) . "</p>";
        switch ($this->action) {
          case "status":
	        print "<pre>";
            print "<div class='standard'><b>Session data</b></div>";
			print_r($_SESSION);
            print "<div class='standard'><b>Server data</b></div>";
	        print_r($_SERVER);
	        print "<div class='standard'><b>Cookie data</b></div>";
	        print_r($_COOKIE);
	        print "<div class='standard'><b>Request data</b></div>";
	        print_r($_REQUEST);
            print "</pre>";
	        $php_path=preg_split("/" . PATH_SEPARATOR . "/",get_include_path());
	        $local_path=preg_split("/" . PATH_SEPARATOR . "/",get_include_path());
	        foreach ($local_path as $key => $value) {
	        	if (array_key_exists($key,$php_path)) unset($local_path[$key]);
	        }
	        print "<p class='standard'><b>Search path (PHP):</b><br>" . implode("<br>",$php_path) . "</p>";
	        if (sizeof($local_path)) print "<p class='standard'><b>Search path (Local):</b><br>" . implode("<br>",$local_path) . "</p>";
	        print "<p class='standard'><b>Error reporting:</b> " . error_reporting() . "</p>";
            break;
          case "module":
	        $modules=get_loaded_extensions();
	        natcasesort($modules);
	        print "<pre>" . implode("<br>",$modules) . "</pre>";
	        break;
          case "cookie":
			print "<table class='scs_toolbox'>";
	        print "<tr>";
	        print "<th style='width:5%'>Clear?</th>";
	        print "<th style='width:20%'>Name</th>";
	        print "<th style='width:75%'>Content</th>";
	        print "</tr>";
	        foreach ($_COOKIE as $cookie => $cookie_data) {
	            $field=base64_encode("cookie" . "\t" . $cookie);
	            print "<tr>";
	            print "<td><input type=checkbox name='$field' id='$field' value='1' onclick=\"toolbox_remove(this, 'cookie', '" . addslashes($cookie) . "');\"></td>";
	            print "<td>" . rawurlencode($cookie) . "</td>";
	            print "<td>" . wordwrap($cookie_data,100,"<br>",TRUE) . "</td>";
	            print "</tr>";
	        }
	        print "</table>";
            break;
          case "session":
			print "<table class='scs_toolbox'>";
	        print "<tr>";
	        print "<th style='width:5%'>Clear?</th>";
	        print "<th style='width:20%'>Name</th>";
	        print "<th style='width:75%'>Content</th>";
	        print "</tr>";
			foreach ($_SESSION as $key => $value) {
	            $field=base64_encode("session" . "\t" . $key);
                print "<tr>";
	            print "<td><input type=checkbox name='$field' id='$field' value='1' onclick=\"toolbox_remove(this, 'session', '" . addslashes($key) . "');\"></td>";
	            print "<td>{$key}</td>";
	            print "<td><pre>" . wordwrap(print_r($value,TRUE),100,"<br>",TRUE) . "</pre></td>";
                print "</tr>";
            }
	        print "</table>";
            print $session;
          	break;
          case "php":
          	phpinfo();
            break;
		}
        print "</form>";
//	    print $forms->close();
        $menu->copyright();
	}
}
?>