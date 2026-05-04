<?php
require_once "scs_header.php";
$database->user->access("Super User");

$obj=new local_class();

class local_class {
    function scs_table_version() {
		$results=array();
        $results['05/06/2025']="Initial release";
        return $results;
    }
	function __construct() {
        if (preg_match("/^xmlhttprequest$/i",$_SERVER['HTTP_X_REQUESTED_WITH'])) {
            $this->json();
            } else {
            $this->html();
        }
    }
    function json() {
        $this->response=new stdClass();

        die(json_encode($this->response));
    }
    function html() {
        global $database, $forms, $menu;
        $forms->title("jQuery Toolbox", key($this->scs_table_version()));
        print $menu->head();
        print $forms->message();
	    $this->tabs=new jquery_tab_class();
        $this->tabs->item("Remote", $this->html_remote());
	    $this->tabs->output();
        print $menu->copyright();
    }
    function html_remote() {
        global $database, $forms;
        $results=array();
        $results[]="<table class=border>";
        $results[]="<tr>";
        $results[]="<td width='20%'>Email Address</td>";
        $results[]="<td width='80%'>" . $forms->text("email","",50,0, "", array("class"=>"remote")) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>Domain</td>";
        $results[]="<td id=remote_domain></td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>Company Name</td>";
        $results[]="<td id=remote_company_name></td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>Token</td>";
        $results[]="<td id=remote_token></td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>Expiry</td>";
        $results[]="<td id=remote_expiry></td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>Unlock Code</td>";
        $results[]="<td id=remote_unlock_code></td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>Unlock Expires</td>";
        $results[]="<td id=remote_unlock_expiry></td>";
        $results[]="</tr>";
        $results[]="</table>";
        return $results;
    }
}
?>