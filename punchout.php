<?php
require_once "scs_header.php";
require_once "classes/punchout.php";
require_once "classes/industry.php";
switch (TRUE) {
  case (isset($_SERVER['HTTP_X_REQUESTED_WITH'])):
  	$results=array();
    $results['token']=bin2hex(openssl_random_pseudo_bytes(20));
	die( json_encode($results) );
}
$database->user->access("Administrator");
$forms->title("Punchout Client");

$forms->report['action']=$_REQUEST['action'];
$forms->report['record_id']=$_REQUEST['record_id'];
if ($forms->report['record_id']) {
	$database->punchout->read($forms->report['record_id']);
  } else {
	$database->punchout->data=new punchout_data_class();
}
switch ($forms->report['action']) {
  case "":
    break;
  case "cancel":
    $forms->message[]="Request cancelled";
    break;
  case "delete":
    $database->punchout->delete($forms->report['record_id']);
    $forms->message[]="punchout " . $database->punchout->data->code . " deleted";
    break;
  case "maintain":
  	$database->user->read($database->punchout->data->user_id);
    break;
  case "update":
	$database->punchout->data->user_id=intval($_POST['ajax_user_id']);
	$database->user->read($database->punchout->data->user_id);
    switch (TRUE) {
      case (!$database->user->meta->rows):
      	$forms->error("ajax_user_id","Invalid user ID");
        break;
      case ($database->user->data->status != "Active"):
      	$forms->error("ajax_user_id","Status must be active");
        break;
      case ($database->user->data->type != "Customer"):
      	$forms->error("ajax_user_id","Type must be customer");
        break;
    }
	$database->punchout->data->identity=trim(strtolower($_POST['identity']));
    if (!strlen($database->punchout->data->identity)) $forms->error('identity');
	$database->punchout->data->token=trim($_POST['token']);
    if (strlen($database->punchout->data->token) < 12) $forms->error('token',"Must be at least 12 characters");
	$database->punchout->data->url=trim($_POST['url']);
    $url=parse_url($database->punchout->data->url);
    switch (TRUE) {
      case (!strlen($database->punchout->data->url)):
      	$forms->error("url","Cannot be blank");
        break;
      case (!preg_match("/^(http|https)$/i",$url['scheme'])):
      	$forms->error("url","Scheme must be http(s)");
        break;
      case (!strlen($url['scheme'])):
      	$forms->error("url","Host name missing");
        break;
      case (!strlen($url['path'])):
      	$forms->error("url","Path name missing");
        break;
	}
	$database->punchout->data->comment=trim($_POST['comment']);
	$database->punchout->data->log=$_POST['log'];
	$database->punchout->data->active=$_POST['active'];
	if (sizeof($forms->error)) break;
    $database->punchout->update($database->punchout->meta->rows);
    switch (TRUE) {
      case (!$database->punchout->meta->error):
     	$forms->message[]="Record maintained";
        break;
      case (preg_match("/user_id/i",$database->punchout->meta->error)):
      	$forms->error('ajax_user_id',"Duplicate Entry");
      	break;
	  default:
      	$forms->error('code',"Duplicate Entry");
	}
	break;
}
$menu->head();
print $forms->message();
?>
<script>
function fn_action(obj,action,id,name) {
	if (action=='delete') {
	    if (!confirm('Delete: #' + id + ' ' + name)) {
	        obj.checked=false;
            return;
	    }
    }
	document.scs_form.action.value=action;
	document.scs_form.record_id.value=id;
	document.scs_form.submit();
}
jQuery(document).ready(function() {
});
function create_token() {
	jQuery.ajax({
        type: "post",
	    dataType: "json",
		data: { action: "token" },
	    success: function(response) {
	        for (var key in response) {
           		jQuery("#" + key).val(response[key]);
            }
        },
	});
}
</script>
<?php
print $forms->open();
print $forms->hidden("action");
print $forms->hidden("record_id");
switch (TRUE) {
  case ($forms->report['action'] == "maintain"):
  case (($forms->report['action'] == "update") && (sizeof($forms->error))):
	print "<table class='standard noborder'>";
    print "<tr>";
    print "<td width='30%'>User ID</td>";
    $text=array();
    $text[]=$forms->text("ajax_user_id",$database->punchout->data->user_id,10,40) . " " . $forms->error_text['ajax_user_id'];
    $text[]="Company Name: <span id=ajax_user_company_name>" . $database->user->data->company_name . "</span>";
    $text[]="Type: <span id=ajax_user_type>" . $database->user->data->type . "</span>";
    $text[]="Status: <span id=ajax_user_status>" . $database->user->data->status . "</span>";
    $text[]="IP Address: <span id=ajax_user_ip>" . $database->user->data->ip . "</span>";
    print "<td width='70%'>" . implode("<br>",$text) . "</td>";
    print "</tr>";
    print "<tr>";
    print "<td>Identity</td>";
    $text=array($forms->text("identity",$database->punchout->data->identity,12,12));
    if ($forms->error_text['identity']) $text[]=$forms->error_text['identity'];
    print "<td>" . implode("<br>",$text) . "</td>";
    print "</tr>";
    print "<tr>";
    $text=array("Shared Secret");
    $text[]=fn_href("(Create New)","javascript:create_token();");
    print "<td>" . implode(" ",$text) . "</td>";
    $text=array($forms->text("token",$database->punchout->data->token,40,40));
    if ($forms->error_text['token']) $text[]=$forms->error_text['token'];
    print "<td>" . implode("<br>",$text) . "</td>";
    print "</tr>";
    print "<tr>";
    print "<td>Harcourt URL</td>";
    $text=array($forms->text("url",$database->punchout->data->url,80));
    if ($forms->error_text['url']) $text[]=$forms->error_text['url'];
    print "<td>" . implode("<br>",$text) . "</td>";
    print "</tr>";
    print "<tr>";
    print "<td>Comment</td>";
    print "<td>" . $forms->textarea("comment",$database->punchout->data->comment,3,60) . "</td>";
    print "</tr>";
    print "<tr>";
    print "<td>Log XML?</td>";
    print "<td>" . $forms->checkbox("log",1,$database->punchout->data->log) . "</td>";
    print "</tr>";
    print "<tr>";
    print "<td>Active</td>";
    print "<td>" . $forms->checkbox("active",1,$database->punchout->data->active) . "</td>";
    print "</tr>";
    print "<tr>";
    print "<td>Last Access</td>";
    print "<td>" . ( ($database->punchout->data->access_timestamp) ? date("m/d/Y h:iA",$database->punchout->data->access_timestamp) : "Never") . "</td>";
    print "</tr>";
    print "</table>";
    $buttons=array();
    $buttons[]=$forms->button("Update",array("onclick"=>"fn_action(this,'update',"  . fn_escape($forms->report['record_id']) . ",'');"));
    $buttons[]=$forms->button("Cancel",array("onclick"=>"fn_action(this,'cancel','','');"));
	print "<p class='standard'>" . implode(" ",$buttons) . "</p>";
  	break;
  default:
	print "<table class='standard border tablesorter'>";
    print "<thead>";
    print "<tr>";
    print "<th>Identity</th>";
    print "<th>User ID</th>";
    print "<th>Company Name</th>";
    print "<th>Status</th>";
    print "<th>URL</th>";
    print "<th>Last Access</th>";
    print "<th>Del?</th>";
    print "</tr>";
    print "</thead>";
    print "<tr><td colspan=7>" . fn_href("Add New","javascript:fn_action(this,'maintain','0','');") . "</td></tr>";
    print "<tbody>";
	$query=array("select * from punchout");
    $query[]="order by identity";
	$database->punchout->query($query);
    while ($database->punchout->fetch = $database->punchout->fetch_array() ) {
        $database->punchout->fetch();
        $database->user->read($database->punchout->data->user_id);
        switch (TRUE) {
          case (!$database->user->meta->rows):
          	$bg=$forms->background->orangered;
          	$status="Not found";
            break;
          case ($database->user->data->status != "Active"):
          	$bg=$forms->background->sandybrown;
          	$status="Not active";
            break;
          case ($database->user->data->type != "Customer"):
          	$bg=$forms->background->sandybrown;
          	$status="Not a customer";
            break;
          default:
          	$bg="";
          	$status="OK";
		}
        print "<tr {$bg}>";
        print "<td>" . fn_href($database->punchout->data->identity,"javascript:fn_action(this,'maintain'," . fn_escape($database->punchout->data->id) . ",'');") . "</td>";
        print "<td class=center>" . $database->punchout->data->user_id . "</td>";
        print "<td>" . $database->user->data->company_name . "</td>";
        print "<td>{$status}</td>";
        print "<td>" . $database->punchout->data->url . "</td>";
        print "<td class=center>" . ( ($database->punchout->data->access_timestamp) ? date("m/d/Y h:iA",$database->punchout->data->access_timestamp) : "Never") . "</td>";
        if ($database->punchout->data->active) {
            $text="&nbsp;";
          } else {
            $text=$forms->radio("delete",$database->punchout->data->id,0,array("onclick"=>"fn_action(this,'delete'," . fn_escape($database->punchout->data->id) . "," . fn_escape($database->punchout->data->code) . ");"));
        }
        print "<td class=center>{$text}</td>";
        print "</td>";
        print "</tr>";
    }
    print "</tbody>";
    print "</table>";
	$database->punchout->free_result();
}
print $forms->close();
$menu->copyright();
?>