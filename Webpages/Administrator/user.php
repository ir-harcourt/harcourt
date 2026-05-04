<?php
include "scs_header.php";
include_once "classes/user.php";
include_once "classes/document.php";
include_once "classes/userdocument.php";
$menu->access();
$menu->title="User Manager";
$database->connect();
$menu->messages[]=$menu->title;
$report['action']=$_POST['action'];
$report['record_id']=$_POST['record_id'];
$report['return_url']=$_POST['return_url'];
$report['report_status']=$_POST['report_status'];
$report['report_company']=$_POST['report_company'];
if ($report['record_id']) $database->user->read($report['record_id']);
$document=array();

switch ($report['action']) {
  case "":
    break;
  case "cancel":
    $menu->messages[]="Request cancelled";
    break;
  case "delete":
    $database->user->delete($report['record_id']);
    $query =
        "delete from userdocument \n" .
        "where user_id=" . fn_escape($database->user->data->id);
    $database->userdocument->query($query);
    $menu->messages[]="IP address " . $database->user->data->ip . " deleted";
    break;
  case "maintain":
    if (!$database->user->data->id) {
    	$database->user->data=new user_data_class();
        $database->user->data->ip="";
	  } else {
	    $query =
	        "select * from userdocument \n" .
            "where user_id = " . fn_escape($report['record_id']);
        $database->userdocument->query($query);
	    while ($database->userdocument->fetch = mysql_fetch_array($database->userdocument->meta->handle)) {
	        $database->userdocument->fetch();
            $document[$database->userdocument->data->document_id]=TRUE;
        }
        $database->userdocument->free_result();
    }
    break;
  case "update":
	$database->user->data->ip=$_POST['ip'];
    switch (TRUE) {
      case (!intval(ip2long($database->user->data->ip))):
      case (long2ip(ip2long($database->user->data->ip)) != $_POST['ip']):
      	$menu->errors['ip']=$menu->background['red'];
    }
	$database->user->data->company=$_POST['company'];
	if (!$database->user->data->company) $menu->errors['company']=$menu->background['red'];
	$database->user->data->first_name=$_POST['first_name'];
	$database->user->data->last_name=$_POST['last_name'];
    $email=new email_class($_POST['email'],FALSE);
	$database->user->data->email=$email->address;
	if ($email->error) $menu->errors['email']=$menu->background['red'];
	$database->user->data->status=$_POST['status'];
	$database->user->data->login_document_id=intval($_POST['login_document_id']);
	$database->user->data->landing_document_id=intval($_POST['landing_document_id']);
    foreach ($_POST as $field => $value) {
    	$field_data=split("\t",base64_decode($field));
        if ($field_data[0] != "document") continue;
        $document[$field_data[1]]=TRUE;
    }
	if (!sizeof($menu->errors)) {
        $database->user->update($database->user->meta->rows);
        if (mysql_errno()) {
			$menu->errors['ip']=$menu->background['red'];
            $menu->messages[]=mysql_error();
        }
    }
	if (!sizeof($menu->errors)) {
    	$menu->messages[]="Record maintained";
        $query =
        	"delete from userdocument \n" .
            "where user_id=" . fn_escape($database->user->data->id);
        $database->userdocument->query($query);
        foreach ($document as $key => $value) {
			$database->userdocument->update($database->user->data->id,$key);
        }
        if ($report['return_url']) fn_url_redirect($report['return_url']);
	}
	break;
}
$menu->head();
$menu->message();
?>
<script type="text/javascript">
function fn_action(obj,action,id,name) {
	if (action=='delete') {
	    if (!confirm("Delete: #" + id + " " + name)) {
	        obj.checked=false;
            return;
	    }
    }
	document.maintain_form.action.value=action;
	document.maintain_form.record_id.value=id;
	document.maintain_form.submit();
}
</script>
<?php
print "<form name=maintain_form method=post action='" . $_SERVER['PHP_SELF'] . "'>\n";
print "<input type=hidden name=action>\n";
print "<input type=hidden name=record_id>\n";
print "<input type=hidden name=return_url " . fn_html_value($report['return_url']) . ">\n";
switch (TRUE) {
  case ($report['action'] == "maintain"):
  case (($report['action'] == "update") && (sizeof($menu->errors))):
	print "<input type=hidden name=report_status " . fn_html_value($report['report_status']) . ">\n";
	print "<input type=hidden name=report_company size=40 " . fn_html_value($report['report_company']) . ">\n";
	print "<table class='standard noborder'>\n";
    print "<tr>\n";
    print "<td width=30%>IP Address</td>\n";
    print "<td width=70%><input type=text name=ip size=20 " . fn_html_value($database->user->data->ip,"","",$menu->errors['ip']) . "></td>\n";
    print "</tr>\n";
    if ($database->user->data->ip) {
    	print "<tr>\n";
        print "<td>DNS</td>\n";
        print "<td><b>" . gethostbyaddr($database->user->data->ip) . "</b></td>\n";
        print "</tr>\n";
	}
    print "<tr>\n";
    print "<td>Company name</td>\n";
    print "<td><input type=text name=company size=40 " . fn_html_value($database->user->data->company,"","",$menu->errors['company']) . "></td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>First/Last name</td>\n";
    print
    	"<td>" .
        "<input type=text name=first_name size=30 " . fn_html_value($database->user->data->first_name,"","",$menu->errors['first_name']) . ">" .
        " <input type=text name=last_name size=30 " . fn_html_value($database->user->data->last_name,"","",$menu->errors['last_name']) . ">" .
        "</td>\n";
    print "</tr>\n";

    print "<tr>\n";
    print "<td>Email</td>\n";
    print "<td><input type=text name=email size=40 " . fn_html_value($database->user->data->email,"","",$menu->errors['email']) . "></td>\n";
    print "</tr>\n";

    print "<tr>\n";
    print "<td>Status</td>\n";
    print "<td><select name=status>\n";
    foreach ($database->user->constant->status as $value) {
    	if ($database->user->data->status == $value) {
        	$selected="selected";
          } else {
          	$selected="";
        }
        print "<option $selected>$value</option>\n";
    }
    print "</select>\n";
    print "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Login page</td>\n";
    print "<td>";
    $database->document->select($database->user->data->login_document_id,"login_document_id");
    print "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Landing page</td>\n";
    print "<td>";
    $database->document->select($database->user->data->landing_document_id,"landing_document_id");
    print "</td>\n";
    print "</tr>\n";
    print "<tr><td colspan=2>&nbsp;</td></tr>\n";
    $query =
    	"select * from document \n" .
        "where parent_id=0 \n" .
        "and file <> '' \n" .
        "and active=1 \n" .
        "order by id";
    $database->document->query($query);
    while ($database->document->fetch = mysql_fetch_array($database->document->meta->handle)) {
        $database->document->fetch();
		print "<tr>\n";
        print "<td>" . $database->document->data->name;
        if ($database->document->data->description) print "<br>" . $database->document->data->description;
        print "</td>\n";
        if ($document[$database->document->data->id]) {
        	$checked="checked";
          } else {
          	$checked="";
        }
        $field=base64_encode("document" . "\t" . $database->document->data->id);
        print "<td><input type=checkbox name='$field' value='1' $checked></td>\n";
        print "</tr>\n";
	}
    $database->document->free_result();
    print "</table>\n";
	print "<p class='standard center'>\n";
	print "<input class=fbutton type=button value='Update' onclick=\"javascript:fn_action(this,'update','" . $database->user->data->id . "','');\"> \n";
    if ($report['return_url']) {
		print "<input class=fbutton type=button value='Cancel' onclick=\"javascript:location.href('" . $report['return_url'] . "');\">\n";
      } else {
		print "<input class=fbutton type=button value='Cancel' onclick=\"javascript:fn_action(this,'cancel','','');\">\n";
    }
	print "</p>\n";
  	break;
  default:
	print "<table class='standard noborder'>\n";
    print "<tr>\n";
    print "<th>Status</th>\n";
    print "<th>Company</th>\n";
    print "<th>&nbsp;</th>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td class=center><select name=report_status>\n";
    print "<option></option>\n";
    foreach ($database->user->constant->status as $value) {
    	if ($report['report_status'] == $value) {
        	$selected="selected";
          } else {
          	$selected="";
        }
        print "<option $selected>$value</option>\n";
    }
    print "</select>\n";
    print "</td>\n";
    print "<td class=center><input type=text name=report_company " . fn_html_value($report['report_company']) . "></td>\n";
    print "<td class=center><input class=fbutton type=button value='List' onclick=\"javascript:fn_action(this,'list','','');\">\n";
    print "</tr>\n";
    print "</table>\n";
	print "<table class='standard border'>\n";
    print "<caption>&nbsp;</caption>\n";
    print "<tr>\n";
    print "<th>IP</th>\n";
    print "<th>Company Name</th>\n";
    print "<th>Status</th>\n";
    print "<th>Del?</th>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td colspan=4><a href=\"javascript:fn_action(this,'maintain','0','');\">Add new</a></td>\n";
    print "</tr>\n";
    if ($report['action'] == "list") {
 		$query_where=array();
        if ($report['report_status']) $query_where[] = new query_where("status","=",$report['report_status']);
        if ($report['report_company']) $query_where[] = new query_where("upper(company)","LIKE",strtoupper($report['report_company']) . "%");
	    $query =
	        "select * from user \n" .
            $database->where($query_where) .
	        "order by company,ip";
	    $database->user->query($query);
	    while ($database->user->fetch = mysql_fetch_array($database->user->meta->handle)) {
	        $database->user->fetch();
	        print "<tr>\n";
	        print
	            "<td>" .
	            "<a href=\"javascript:fn_action(this,'maintain','" . $database->user->data->id . "','');\">" .
	            $database->user->data->ip .
	            "</a></td>\n";
	        print "<td>" . $database->user->data->company . "</td>\n";
	        print "<td>" . $database->user->data->status . "</td>\n";
	        print "<td class=center>";
	        switch ($database->user->data->status) {
	          case "Active":
	          case "Administrator";
	            print "&nbsp;";
	            break;
	          default:
	            print "<input type=radio name='delete' onclick=\"javascript:fn_action(this,'delete','" . $database->user->data->id . "','" . $database->user->data->ip . "');\">\n";
	        }
	        print "</td>\n";
	        print "</tr>\n";
	    }
	}
    print "</table>\n";
	$database->user->free_result();
}
print "</form>\n";
$database->disconnect();
$menu->copyright();
?>