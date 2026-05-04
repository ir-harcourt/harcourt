<?php
require_once "classes/mailform.php";
class mailform_entry_class {
	var $input;
	function scs_table_version() {
		$results=array();
        $results['03/26/2023']="Expand name to 60";
        $results['04/19/2021']="Rewrite";
        $results['10/26/2014']="Initial release";
		$results['file']= __FILE__;
        return $results;
    }
	function __construct($role) {
        global $database, $forms, $menu;
		$database->user->access($role);
        if (!isset($database->registry->email)) fn_url_redirect($menu->page->home);
	    $forms->title("Email Documents", key($this->scs_table_version()));
	    $forms->report['action']=$_REQUEST['action'];
	    $forms->report['record_id']=$_REQUEST['record_id'];
	    if ($forms->report['record_id']) {
	        $database->mailform->read($forms->report['record_id']);
	      } else {
	        $database->mailform->data=new mailform_data_class();
	    }
	    switch ($forms->report['action']) {
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
	        if (!$database->mailform->data->code) $forms->error('code',"Cannot be blank");
	        $database->mailform->data->name=trim($_POST['name']);
	        if (!$database->mailform->data->name) $forms->error('name',"Cannot be blank");
            $email=new email_class($_POST['recipient'],FALSE);
	        $database->mailform->data->recipient=$email->address;
            if ($email->error) $forms->error("recipient",$email->error);
            $database->mailform->data->bcc=array();
            $items=explode("\n",$_POST['bcc']);
            $errors=array();
            foreach ($items as $item) {
            	$email=new email_class($item,FALSE);
                if ( (!strlen($email->address)) || (in_array($email->address, $database->mailform->data->bcc)) ) continue;
	            $database->mailform->data->bcc[]=$email->address;
				switch (TRUE) {
                  case ($email->error):
                  	$errors[]=$email->address . ": " . $email->error;
                    break;
                  case ($email->address == $database->mailform->data->recipient):
                  	$errors[]=$email->address . ": Recipient";
                    break;
				}
            }
            if (sizeof($errors)) $forms->error("bcc",implode("<br>",$errors));
	        $database->mailform->data->css=trim($_POST['css']);
	        $css_error=verify_file($database->mailform->data->css,"css");
	        if ($css_error) $forms->error('css',$css_error);
	        $database->mailform->data->active=$_POST['active'];
	        $database->mailform->data->subject=trim($_POST['subject']);
	        if (!strlen($database->mailform->data->subject)) $forms->error('subject');
	        $database->mailform->data->header_html=trim($_POST['header_html']);
	        $database->mailform->data->footer_html=trim($_POST['footer_html']);
	        if (sizeof($forms->error)) break;
	        $database->mailform->update($forms->report['record_id']);
            switch (TRUE) {
              case (!$database->mailform->meta->error):
	            $forms->message[]="Record maintained";
                break;
              case (preg_match("/code_key/i",$database->mailform->meta->error)):
              	$forms->error("code","Duplicate Entry");
              	break;
			  default:
              	$forms->error("name","Duplicate Entry");
			}
		}
	    $menu->head();
	    $forms->message();
?>
<script>
function fn_action(obj, action, id, code) {
	if ( (action=='delete') && (!confirm("Delete " + code + "?")) ) {
        obj.checked=false;
        return;
    }
    document.scs_form.action.value=action;
    document.scs_form.record_id.value=id;
    document.scs_form.submit();
}
</script>
<?php
	    print $forms->open();
	    print $forms->hidden("action");
	    print $forms->hidden("record_id");

	    switch (TRUE) {
	      case (($forms->report['action'] == "update") && (sizeof($forms->error))):
	      case ($forms->report['action'] == "maintain"):
	        print "<table class='standard noborder'>";
	        print "<tr>";
	        print "<td width='20%'>Code</td>";
            $text=array($forms->text("code",$database->mailform->data->code,20,20));
            if ($forms->error_text['code']) $text[]=$forms->error_text['code'];
	        print "<td width='80%'>" . implode("<br>",$text) . "</td>";
	        print "</tr>";
	        print "<tr>";
	        print "<td>Name</td>";
            $text=array($forms->text("name",$database->mailform->data->name,60,60));
            if ($forms->error_text['name']) $text[]=$forms->error_text['name'];
	        print "<td>" . implode("<br>",$text) . "</td>";
	        print "</tr>";
	        print "<tr>";
	        print "<td>Subject</td>";
	        print "<td>" . $forms->text("subject",$database->mailform->data->subject,60,60);
	        print "</td>";
	        print "</tr>";
	        print "<tr>";
	        print "<td>Recipient</td>";
            $text=array( $forms->text("recipient",$database->mailform->data->recipient,80) );
            if ($forms->error_text['recipient']) $text[]=$forms->error_text['recipient'];
	        print "<td>" . implode("<br>",$text) . "</td>";
	        print "</tr>";
	        print "<tr>";
	        print "<td>BCC</td>";
            $text=array( $forms->textarea("bcc",$database->mailform->data->bcc,3,80) );
            if ($forms->error_text['bcc']) $text[]=$forms->error_text['bcc'];
	        print "<td>" . implode("<br>",$text) . "</td>";
	        print "</tr>";
	        print "<tr>";
	        print "<td>Active</td>";
	        print "<td>" . $forms->checkbox("active",1,$database->mailform->data->active) . "</td>";
	        print "</tr>";
	        print "<tr>";
	        print "<td>CSS (optional)</td>";
            $text=array($forms->text("css",$database->mailform->data->css,60));
	        if ($forms->error_text['css']) $text[]=$forms->error_text['css'];
	        print "<td>" . implode("<br>",$text) . "</td>";
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
	        print "<table class='standard border tablesorter'>";
	        print "<thead>";
	        print "<tr>";
	        print "<th>Code</th>";
	        print "<th>Name</th>";
	        print "<th>Subject</th>";
	        print "<th>Recipient</th>";
	        print "<th>BCC</th>";
	        print "<th width='5%'>Del?</th>";
	        print "</tr>";
	        print "</thead>";
	        print "<tr><td colspan=6>" . fn_href("Add new","javascript:fn_action(this,'maintain','0','');") . "</td></tr>";
	        print "<tbody>";
	        $query=array("select * from mailform");
	        $query[]="order by name";
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
	            print "<td>" . ( ($database->mailform->data->recipient) ? $database->mailform->data->recipient : "Visitor") . "</td>";
	            print "<td>" . implode("<br>",$database->mailform->data->bcc) . "</td>";
	            print "<td class=center>";
	            if (!$database->mailform->data->active) {
	                print $forms->radio("delete",1,0,array("onclick"=>"fn_action(this,'delete'," . fn_escape($database->mailform->data->id) . "," . fn_escape($database->mailform->data->code) . ");"));
	              } else {
	                print "&nbsp;";
	            }
	            print "</td>";
	            print "</tr>";
	        }
	        print "</tbody>";
	        print "</table>";
	        $database->mailform->free_result();
	    }


	    print $forms->close();
	    $menu->copyright();
    }
}
?>