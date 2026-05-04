<?php
require_once "scs_header.php";
if ( (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) && ($_SERVER['REQUEST_METHOD'] == "POST") ) {
	$results=array();
    switch ($_POST['action']) {
      case "Remove":
      	$database->remote->delete($_POST['email']);
		$results['remote_message']=$_POST['email'] . " Removed";
        break;
      case "Reset":
      	$database->remote->read($_POST['email']);
      	$database->remote->token();
      	$database->remote->expiry();
      	$database->remote->unlock_code();
		if ($database->remote->meta->rows) $database->remote->update(TRUE);
		$results['remote_message']=$_POST['email'] . " Reset";
        break;
      default:
		$results['remote_message']="";
    }
    $results['remote_content']=remote_output($_POST['domain']);
	die( json_encode($results) );
}
$database->user->access("Administrator");
$forms->title("Company Remote Access Tokens");
$forms->report['action']=trim($_POST['action']);
$forms->report['domain']=trim($_POST['domain']);
if ($forms->report['action']) {
	if (!strlen($forms->report['domain'])) $forms->error("domain");
}
$menu->head();
print $forms->message();
?>
<script>
function remote_token(row) {
	var action=jQuery("#action" + row).val();
	var email=jQuery("#email" + row).val();
	if (!action) return;
    if (!confirm(action + " " + email + "?")) {
        jQuery("#action" + row).val("");
    	return;
	}
	jQuery.ajax({
        type: "post",
	    dataType: "json",
		data: { action: action, domain: jQuery("#domain").val(), email: email },
	    success: function(response) {
        	jQuery("#remote_message").html(response['remote_message']);
        	jQuery("#remote_content").html(response['remote_content']);
        },
        error: function(xhr, response) {
        	console.log(xhr);
        }
	});

}
</script>
<?php
print $forms->open();
print $forms->hidden("action","list");
$query=array("select * from user");
$query[]="where domain <> ''";
$query[]="order by company_name";
$items=array();
$database->user->query($query);
while ($database->user->fetch = $database->user->fetch_array()) {
	$database->user->fetch();
    $items[$database->user->data->domain]=$database->user->data->company_name;
}
$text=array();
if (!sizeof($items)) {
	$text[]="No records found";
  } else {
  	$text[]="Domain:";
    $text[]=$forms->select("domain",$items,$forms->report['domain']);
	$text[]=$forms->submit("List");
}
print "<p>" . implode(" ",$text) . "</p>";
print "<div id=remote_message></div>";
if ( ($forms->report['action']) && (!sizeof($forms->error)) ) print "<div id=remote_content>" . remote_output($forms->report['domain']) . "</div>";
print $forms->close();
$menu->copyright();
function remote_output($domain) {
global $database, $menu, $forms;
	$results=array();
	$results[]="<table class='standard border'>";
    $results[]="<thead>";
    $results[]="<tr>";
    $results[]="<th width='10%'>Action</th>";
    $results[]="<th width='30%'>Email</th>";
    $results[]="<th width='20%'>Expires</th>";
    $results[]="<th width='30%'>Token</th>";
    $results[]="<th width='10%'>Unlock Code</th>";
    $results[]="</thead>";
    $results[]="<tbody>";
	$query=array("select * from remote");
    $query[]="where email like " . fn_escape("%@" . $domain);
    $query[]="order by expiry desc";
    $database->remote->query($query);
    if (!$database->remote->meta->rows) {
    	$results[]="<tr><td colspan=5>No records found</td></tr>";
	  } else {
      	$row=0;
      	while ($database->remote->fetch = $database->remote->fetch_array()) {
        	$database->remote->fetch();
            $results[]=$forms->hidden("email{$row}",$database->remote->data->email);
            $results[]="<tr " . ( ($database->remote->data->expiry < strtotime("now")) ? $forms->background->yellow : $forms->background->limegreen) . ">";
            $results[]="<td class=center>" . $forms->select("action{$row}",array("Remove","Reset"),"",TRUE,array("onchange"=>"remote_token({$row});")) . "</td>";
            $results[]="<td>" . $database->remote->data->email . "</td>";
            $results[]="<td class=center>" . date("m/d/Y H:i",$database->remote->data->expiry) . "</td>";
            if ($database->remote->data->expiry < strtotime("now")) {
            	$results[]="<td class=center colspan=2>Expired</td>";
			  } else {
              	$timestamp=hexdec($database->remote->data->unlock_code);
              	$results[]="<td>" . $database->remote->data->token . "</td>";
                if ($timestamp < strtotime("now")) {
                	$results[]="<td class=center " . $forms->background->yellow . ">Expired</td>";
                  } else {
                	$results[]="<td class=center>" . $database->remote->data->unlock_code . "</td>";
				}
            }
            $results[]="</tr>";
            $row++;
        }
    }
    $results[]="</tbody>";
    $results[]="</table>";
    return implode("",$results);
}
?>