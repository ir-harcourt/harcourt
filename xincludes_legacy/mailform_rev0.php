<?php
include_once "classes/mailform.php";
$forms->title("SCS Email Form Definition (10/26/2014)");
$forms->report['action']=$_REQUEST['action'];
$forms->report['record_id']=$_REQUEST['record_id'];
if ($forms->report['record_id']) {
	$database->mailform->read($forms->report['record_id']);
  } else {
	$database->mailform->data=new mailform_data_class();
}
switch ($forms->report['action']) {
  case "":
	$module_version=$database->registry->module_version(array('email'=>'04/11/2014'));
	if ($module_version) {
	    $menu->head();
	    $forms->message();
        print $module_version;
        $menu->copyright();
		die;
    }
	break;
  case "cancel":
    $forms->message[]="Request cancelled";
    break;
  case "delete":
	$database->mailform->delete($forms->report['record_id']);
    $forms->message[]=("Email Form " . $database->mailform->data->code . " deleted");
    break;
  case "maintain":
    break;
  case "update":
	$database->mailform->data->code=trim(strtolower($_POST['code']));
    if (!$database->mailform->data->code) $forms->error('code');
	$database->mailform->data->name=trim($_POST['name']);
    if (!$database->mailform->data->name) $forms->error('name');
	$database->mailform->data->from_user=$_POST['from_user'];
	$database->mailform->data->user_id=$_POST['user_id'];
	$database->mailform->data->bcc_user_id=$_POST['bcc_user_id'];
	$database->mailform->data->css=trim($_POST['css']);
    $css_error=verify_file($database->mailform->data->css,"css");
    if ($css_error) $forms->error('css',$css_error);
	$database->mailform->data->active=$_POST['active'];
	$database->mailform->data->subject=trim($_POST['subject']);
    if (!$database->mailform->data->subject) $forms->error('subject');
	$database->mailform->data->header_html=trim($_POST['header_html']);
	$database->mailform->data->footer_html=trim($_POST['footer_html']);
    if (sizeof($forms->error)) break;
	$database->mailform->update($forms->report['record_id']);
    if ($database->mailform->meta->error) {
    	$forms->error("code",'',$database->mailform->meta->error);
	  } else {
		$forms->message[]="Record maintained";
	}
}
$menu->head();
$forms->message();
?>
<script>
function fn_action(obj,action,id,title) {
	if (action=='delete') {
	    if (!confirm("Delete " + title + "?")) {
        	obj.checked=false;
            return;
	    }
    }
    document.scs_form.action.value=action;
    document.scs_form.record_id.value=id;
    document.scs_form.submit();
}
</script>
<?php
print $forms->open(array("data"=>TRUE));
print $forms->hidden("action");
print $forms->hidden("record_id");
switch (TRUE) {
  case (($forms->report['action'] == "update") && (sizeof($forms->error))):
  case ($forms->report['action'] == "maintain"):
    print "<table class='standard noborder'>";
    print "<tr>";
    print "<td width='30%'>Code</td>";
	print "<td width='70%'>" . $forms->text("code",$database->mailform->data->code,20,20) . "</td>";
    print "</tr>";
    print "<tr>";
    print "<td>Name</td>";
	print "<td>" . $forms->text("name",$database->mailform->data->name,45,45) . "</td>";
    print "</tr>";
	print "<tr>";
    print "<td>Sent from user?</td>";
    print "<td>" . $forms->checkbox("from_user",1,$database->mailform->data->from_user) . "</td>";
    print "</tr>";
    $query_where=array();
	$query_where['status']=new query_where("status","=","Active");
	$query_where['email']=new query_where("email","<>","");
    $having_where=array();
	$having_where['domain']=new query_where("domain","in",$database->registry->email->domain);
    $fields=array();
	$fields[]="id as 'id'";
    $fields[]="substring(email,locate('@',email) + 1) as 'domain'";
    $fields[]="name as 'name'";
    $fields[]="email as 'email'";
    if (isset($database->user->data->role)) {
		$fields[]="role as 'role'";
	  } else {
		$fields[]="type as 'type'";
    }
    $query=array("select");
    $query[]=implode(",\n",$fields);
    $query[]="from user";
    $query[]=$database->where($query_where);
    $query[]=$database->where($having_where,TRUE);
    $query[]="order by domain, name";
	$database->temp->query($query);
    $results=array();
    while ($database->temp->fetch = $database->temp->fetch_array()) {
		$domain=$database->temp->fetch['domain'];
        if (!array_key_exists($domain,$results)) $results[$domain]=array();
		$results[$domain][$database->temp->fetch['id']]=$database->temp->fetch['name'] . " (" . $database->temp->fetch['email'] . ")";
    }
    $database->temp->free_result();
    if (sizeof($results)) {
	    print "<tr>\n";
	    print "<td>Webmaster</td>\n";
	    print "<td>" . $forms->optgroup("user_id",$results,$database->mailform->data->user_id) . "</td>\n";
	    print "</tr>\n";
    }
    $query_where=array();
	$query_where['status']=new query_where("status","=","Active");
	$query_where['email']=new query_where("email","<>","");
    if (isset($database->user->data->role)) {
		$query_where['role']=new query_where("role","in",array("Internal","Executive","Administrator","Super User"));
	  } else {
		$query_where['type']=new query_where("type","in",array("Internal","Executive","Administrator","Super User"));
	}
    $fields=array();
	$fields[]="id as 'id'";
    $fields[]="substring(email,locate('@',email) + 1) as 'domain'";
    $fields[]="name as 'name'";
    $fields[]="email as 'email'";
    if (isset($database->user->data->role)) {
		$fields[]="role as 'role'";
	  } else {
		$fields[]="type as 'role'";
    }
    $query=array("select");
    $query[]=implode(",\n",$fields);
    $query[]="from user";
    $query[]=$database->where($query_where);
    $query[]="order by domain, name";
	$database->temp->query($query);
    $results=array();
    while ($database->temp->fetch = $database->temp->fetch_array()) {
		$results[$database->temp->fetch['domain']][$database->temp->fetch['id']]=$database->temp->fetch['name'] . " (" . $database->temp->fetch['email'] . ")";
    }
    $database->temp->free_result();
    if (sizeof($results)) {
	    print "<tr>";
	    print "<td>BCC</td>";
	    print "<td>" . $forms->optgroup("bcc_user_id",$results,$database->mailform->data->bcc_user_id) . "</td>";
	    print "</tr>";
    }
	print "<tr>";
    print "<td>Active</td>";
    print "<td>" . $forms->checkbox("active",1,$database->mailform->data->active) . "</td>";
    print "</tr>";
    print "<tr>";
    print "<td>CSS (optional)</td>";
	print "<td>" . $forms->text("css",$database->mailform->data->css,60);
    if ($forms->error_text['css']) print "<br>" . $forms->error_text['css'];
    print "</td>";
    print "</tr>";
    print "<tr>";
    print "<td>Subject</td>";
	print "<td>" . $forms->text("subject",$database->mailform->data->subject,60,60);
    print "</td>";
    print "</tr>";
	print "<tr>";
    print "<td>Header HTML</td>";
    print "<td>" . $forms->textarea("header_html",$database->mailform->data->header_html,20,80) . "</td>";
    print "</tr>";
	print "<tr>";
    print "<td>Footer HTML</td>";
    print "<td>" . $forms->textarea("footer_html",$database->mailform->data->footer_html,20,80) . "</td>";
    print "</tr>";
    print "</table>";
    $buttons=array();
    $buttons[]=$forms->button("Update",array("onclick"=>"fn_action(this,'update'," . fn_escape($forms->report['record_id']) . ",'');"));
    $buttons[]=$forms->button("Cancel",array("onclick"=>"fn_action(this,'cancel','0','');"));
    print "<p>" . implode(" ",$buttons) . "</p>";
 	break;
  default:
    print "<table class='border'>";
	print $forms->caption();
    print "<tr>";
	print "<th>Code</th>";
	print "<th>Name</th>";
	print "<th>Subject</th>";
	print "<th>Direction</th>";
	print "<th>Recipient(s)</th>";
	print "<th width='5%'>Del?</th>";
    print "</tr>";
    print "<tr><td colspan=6>" . fn_href("Add new","javascript:fn_action(this,'maintain','0','');") . "</td></tr>";
    $query=array("select * from mailform");
    $query[]="order by from_user,code";
    $database->mailform->query($query);
    while ($database->mailform->fetch = $database->mailform->fetch_array()) {
        $database->mailform->fetch();
		if (!$database->mailform->data->active) {
        	$bg=$forms->background->yellow;
		  } else {
            $bg="";
		}
	    print "<tr $bg>";
	    print "<td>" . fn_href($database->mailform->data->code,"javascript:fn_action(this,'maintain'," . fn_escape($database->mailform->data->id) . ",'');") . "</td>";
        print "<td>" . $database->mailform->data->name . "</td>";
        print "<td>" . $database->mailform->data->subject . "</td>";
        $recipient=array();
        if ($database->mailform->data->from_user) {
        	$text="From User";
		  } else {
            $text="To User";
		}
        print "<td>$text</td>";
        if ($database->mailform->data->user_id) {
			$database->user->read($database->mailform->data->user_id);
            $recipient[]=$database->user->data->email;
		  } else {
            $recipient[]=$database->registry->email->webmaster . " (Webmaster)";
        }
        if ($database->mailform->data->bcc_user_id) {
			$database->user->read($database->mailform->data->bcc_user_id);
            $recipient[]=$database->user->data->email . " (BCC)";
        }
        print "<td>" . implode("<br>",$recipient) . "</td>";
	    print "<td class=center>";
		if (!$database->mailform->data->active) {
            print $forms->radio("delete",1,0,array("onclick"=>"fn_action(this,'delete'," . fn_escape($database->mailform->data->id) . "," . fn_escape($database->mailform->data->code) . ");"));
          } else {
            print "&nbsp;";
        }
        print "</td>";
        print "</tr>";
	}
    print "</table>";
	$database->mailform->free_result();
}
print $forms->close();
$menu->copyright();
?>