<?php
/* Notes
server if cert failure: imap.gmail.com:993/imap/ssl/novalidate-cert
*/
require_once "classes/imap.php";
$forms->title("IMAP Toolbox");
$menu->imap=new imap_class();
switch (TRUE) {
  case (isset($_SERVER['HTTP_X_REQUESTED_WITH'])):
	$results=array();
    $results['message']="";
    $results['elapsed']="";
    $results['search_match']="";
    $results['view_message']="";

	$connection=array();
	$connection['server']=$_POST['connection_server'];
	$connection['mailbox']=$_POST['connection_mailbox'];
	$connection['sent']=$_POST['connection_sent'];
	$connection['email']=$_POST['connection_email'];
	$connection['password']=$_POST['connection_password'];
    $query=array();
    for ($i=0; $i < 5; $i++) {
    	if ($_POST["search_criteria{$i}"]) $query[$i]=$menu->imap->query($_POST["search_criteria{$i}"],$_POST["search_text{$i}"]);
    }
    $results['query']=$query;
    switch (TRUE) {
      case ( (!isset($_SESSION['imap_toolbox'])) || ($_SESSION['imap_toolbox'] != $_POST['access']) ):
    	unset($_SESSION['imap_toolbox']);
	    $results['message']="Connection lost, please refresh page";
		break;
      case ($_POST['action'] == "connection"):
		$menu->imap->settings($connection);
		$results['message']=( ($menu->imap->open("")) ? "Connection Successful" : $menu->imap->meta->error);
        break;
      case ($_POST['action'] == "reset"):
      	$results['message']="Connection reset to default";
		$results['connection_server']=$database->registry->imap->server;
		$results['connection_mailbox']=$database->registry->imap->mailbox;
		$results['connection_sent']=$database->registry->imap->sent;
		$results['connection_email']=$database->registry->imap->email;
		$results['connection_password']=$database->registry->imap->password;
        break;
     case (preg_match("/^search_criteria/i",$_POST['action'])):
		$item=substr($_POST['action'],-1);
        if (array_key_exists($item,$query)) {
	        $results['message']=$query[$item]->name . " (" . $query[$item]->code . ")";
	        $results['search_type']=$query[$item]->type;
        }
		break;
      case ($_POST['action'] == "search"):
      	if (!sizeof($query)) {
			$results['message']="No items selected";
            break;
        }
        $errors=array();
		foreach($query as $i => $item) {
        	if ($item->error) $errors[]="Criteria-{$i}: " . $item->error;
        }
        if (sizeof($errors)) {
			$results['message']=implode(", ",$errors);
            break;
        }
		$menu->imap->settings($connection);
		if (!$menu->imap->open()) {
        	$results['message']=$menu->imap->meta->error;
            break;
        }
		if (!$menu->imap->search($query)) {
        	$results['message']=$menu->imap->meta->error;
            break;
        }
        $results['messages']=$menu->imap->messages;
        $results['message']=number_format(sizeof($menu->imap->messages)) . " message(s) found";
		$html=array();
        $html[]="<table class='small border'>";
        $html[]="<tr>";
        $html[]="<th rowspan=2>Msg#</th>";
        $html[]="<th rowspan=2>Date</th>";
        $html[]="<th rowspan=2>From</th>";
        $html[]="<th rowspan=2>Subject</th>";
        $html[]="<th colspan=5>Flags</th>";
        $html[]="</tr>";
        $html[]="<tr>";
        foreach ($menu->imap->constant->flag as $flag => $value) {
        	$html[]="<th>{$flag}?</th>";
        }
        $html[]="</tr>";
        foreach ($menu->imap->overview($menu->imap->messages) as $item) {
	        $html[]="<tr>";
	        $html[]="<td class=center>" . fn_href($item->msgno,"javascript:fn_view_msg(" . fn_escape($item->msgno) . ");") . "</td>";
	        $html[]="<td>" . date("m/d/Y h:i A",strtotime($item->date)) . "</td>";
	        $html[]="<td>" . $item->from . "</td>";
	        $html[]="<td>" . $item->subject . "</td>";
        	foreach ($menu->imap->constant->flag as $flag => $value) {
            	$field=fn_base64(array("message",$item->msgno,$flag));
            	$html[]="<td class=center>" . $forms->checkbox($field,1,$item->$flag,array("onchange"=>"fn_flag(" . fn_escape($item->msgno) . "," . fn_escape($flag) . ",this.checked);")) . "</td>";
			}
	        $html[]="</tr>";
        }
        $html[]="</table>";
        $results['search_match']=implode("",$html);
		break;
      case ($_POST['action'] == "flag_set"):
		$menu->imap->settings($connection);
		if (!$menu->imap->open()) {
        	$results['message']=$menu->imap->meta->error;
            break;
        }
        if (!$menu->imap->flags($_POST['search_msg'],$_POST['flag_name'],(($_POST['flag_checked']=="true") ? 1 : 0) )) {
        	$results['message']=$menu->imap->meta->error;
		  } else {
          	$text=array("Message #" . $_POST['search_msg']);
            $text[]="Flag: " . $_POST['flag_name'];
            $text[]="Status: " . (($_POST['flag_checked']=="true") ? "On" : "Off");
          	$results['message']=implode(" ",$text);
        }
      case ($_POST['action'] == "mailbox"):
		$menu->imap->settings($connection);
		if (!$menu->imap->open("")) {
        	$results['message']=$menu->imap->meta->error;
            break;
        }
        if (!$menu->imap->mailbox_list()) {
        	$results['message']=$menu->imap->meta->error;
            break;
        }
        $results['message']=sizeof($menu->imap->mailboxes) . " mailboxes";
        $html=array("<ul>");
        foreach ($menu->imap->mailboxes as $mailbox) {
        	$html[]="<li>" . preg_replace("/^" . preg_quote($menu->imap->meta->stream,"/") . "/i","",$mailbox) . "</li>";
        }
        $html[]="</ul>";
        $results['mailbox_html']=implode("",$html);
		break;
      case ($_POST['action'] == "mail"):
/* add to sent folder?
https://stackoverflow.com/questions/49789537/imap-mail-compose-composing-email-with-attachments-and-message (looks promising)
*/
      	$errors=array();
      	$envelope=array();
        $envelope['from']=$database->registry->imap->email;
        $email=new email_class($_POST['mail_to'],TRUE);
        $envelope['to']=$email->address;
        if ($email->error) $errors[]="TO: " . $email->error;
        $email=new email_class($_POST['mail_cc'],FALSE);
        $envelope['cc']=$email->address;
        if ($email->error) $errors[]="CC: " . $email->error;
        $email=new email_class($_POST['mail_bcc'],FALSE);
        $envelope['bcc']=$email->address;
        if ($email->error) $errors[]="BCC: " . $email->error;
        $envelope['subject']=trim($_POST['mail_subject']);
        if (!strlen($envelope['subject'])) $errors[]="Blank subject";

        $mail_body=trim($_POST['mail_body']);
        if (!strlen($mail_body)) $errors[]="Blank body";
		$results['envelope']=$envelope;
        if (sizeof($errors)) {
        	$results['message']=implode(", ",$errors);
            break;
        }
//imap_mail_composer
		$body=array();
	    $body[0]=array();
        $body[0]["type"]=TYPEMULTIPART;
        $body[0]["subtype"]="mixed";

	    $body[1]=array();
	    $body[1]["type"] = TYPETEXT;
	    $body[1]["subtype"] = "html";
	    $body[1]["description"] = "";
	    $body[1]["contents.data"] = $mail_body;
		$results['body']=$body;
        if (!$menu->imap->append($envelope,$body)) {
        	$results['message']=$menu->imap->meta->error;
            break;
        }
        $results['message']="OK";

      	break;
      default:
		$results['message']="Invalid action: ". $_POST['action'];
    }
	$results['meta']=$menu->imap->meta;
	$menu->imap->close();
    if (property_exists($menu->imap,"mailbox")) $results['mailbox']=$menu->imap->mailbox;
    if (property_exists($menu->imap,"stopwatch")) {
        $results['stopwatch_html']=implode("",$menu->imap->stopwatch->results(TRUE));
    	$results['stopwatch']=$menu->imap->stopwatch->json();
		$results['elapsed']="Elapsed: <b>" . number_format($menu->imap->stopwatch->elapsed,2) . "</b>";
	  } else {
        $results['stopwatch_html']=" ";
	}
	$json=json_encode($results);
    if (json_last_error()) $json=json_encode( array("message"=>"JSON error: " . json_last_error_msg(),"elapsed"=>""));
    die($json);
  case ($_REQUEST['action']=="view_message"):
	if ( (!isset($_SESSION['imap_toolbox'])) || ($_SESSION['imap_toolbox'] != $_REQUEST['access']) ) die("Done");
    if ( ($menu->imap->read($_REQUEST['msg'])) ? print $menu->imap->content : print $menu->imap->meta->error);
	die();
}
$_SESSION['imap_toolbox']=strtotime("now");
$forms->message[]="Revised: " . key($menu->imap->scs_table_version());
$menu->head();
print $forms->message();
?>
<script>
$( document ).ready(function() {

});
function fn_action(action) {
	$("#message").html("Action: " + action);
	$("#elapsed").html("Waiting ...");
	data=new Object();
    data['access']=$("#access").val();
    data['action']=action;
    data['connection_server']=$("#connection_server").val();
    data['connection_mailbox']=$("#connection_mailbox").val();
    data['connection_sent']=$("#connection_sent").val();
    data['connection_email']=$("#connection_email").val();
    data['connection_password']=$("#connection_password").val();
    for (var i=0; i < 5; i++) {
	    data["search_criteria" + i]=$("#search_criteria" + i).val();
	    data["search_text" + i]=$("#search_text" + i).val();
	}
    data['search_msg']=$("#search_msg").val();
    data['flag_name']=$("#flag_name").val();
    data['flag_checked']=$("#flag_checked").val();
    data['mail_to']=$("#mail_to").val();
    data['mail_cc']=$("#mail_cc").val();
    data['mail_bcc']=$("#mail_bcc").val();
    data['mail_subject']=$("#mail_subject").val();
    data['mail_body']=$("#mail_body").val();
	console.log(data);
	jQuery.ajax({
    	data: data,
        method: 'POST',
	    dataType: 'json',
	    success: function(data){
			$("#message").html(data['message']);
			$("#elapsed").html(data['elapsed']);
			$("#stopwatch_html").html(data['stopwatch_html']);
			console.log(data);
			switch (true) {
              case (action == "connection"):
              	break;
              case (action == "reset"):
                $("#connection_server").val(data['connection_server']);
                $("#connection_mailbox").val(data['connection_mailbox']);
                $("#connection_sent").val(data['connection_sent']);
                $("#connection_email").val(data['connection_email']);
                $("#connection_password").val(data['connection_password']);
              	break;
              case (action.substr(0,15) == "search_criteria"):
				var i=action.substr(-1);;
                if (data["search_type"]) {
                	$("#search_label" + i).html(data["search_type"] + ": ");
                	$("#search_label" + i).show();
                	$("#search_text" + i).show();
                  } else {
                	$("#search_label" + i).hide();
                	$("#search_text" + i).hide();
                }
              	break;
              case (action == "search"):
                $("#search_match").html(data["search_match"]);
				$("#view_message").hide();
              	break;
              case (action == "mailbox"):
                $("#mailbox_html").html(data["mailbox_html"]);
              	break;
            }
		},
		error: function(xhr) {
			console.log(xhr);
			$("#message").html( ((xhr['status'] == "200") ? xhr['responseText'] : xhr['statusText']));
			$("#elapsed").html("Return code: " + xhr['status']);
		}
	});
}
function fn_view_msg(msg) {
	$("#message").html("Viewing message #" + msg);
	$("#elapsed").html(" ");
	$("#view_message").show();
	$('#scs_jquery_div').tabs( { active: 2 } );
	var query=[];
    query.push("action=view_message");
    query.push("access=" + $("#access").val());
    query.push("msg=" + msg);
	$("#view_message").attr("src", window.location.pathname + "?" + query.join("&"));
}
function fn_flag(msg,flag_name,flag_checked) {
	$("#search_msg").val(msg);
	$("#flag_name").val(flag_name);
	$("#flag_checked").val(flag_checked);
    fn_action("flag_set");
}
</script>
<?php
print $forms->open();
print $forms->hidden("action");
print $forms->hidden("access",$_SESSION['imap_toolbox']);
print $forms->hidden("search_msg");
print $forms->hidden("flag_name");
print $forms->hidden("flag_checked");
$text=array("Status:");
$text[]="<b>" . $forms->span("",array("id"=>"message")) . "</b>";
$text[]=$forms->span("",array("id"=>"elapsed"));
print "<p>" . implode(" ",$text) . "</p>";
$menu->tabs=new jquery_tab_class();
$menu->tabs->item("Connection",tab_connection());
$menu->tabs->item("Search",tab_search());
$menu->tabs->item("View Message",tab_view_message());
$menu->tabs->item("Mail Boxes",tab_mailbox());
$menu->tabs->item("Mail",tab_mail());
$menu->tabs->item("Stopwatch",$forms->div("",array("id"=>"stopwatch_html")));
$menu->tabs->output();
print $forms->div("",array("id"=>"view_message"));
print $forms->close();
$menu->copyright();
function tab_connection() {
global $database, $forms, $menu;
	$results=array();
    $results[]="<table class='standard noborder'>";
    $results[]="<tr>";
    $results[]="<td width='20%'>Server</td>";
    $results[]="<td width='80%'>" . $forms->text("connection_server",$database->registry->imap->server,80) . "</td>";
    $results[]="</tr>";
    $results[]="<tr>";
    $results[]="<td>Mailbox</td>";
    $results[]="<td>" . $forms->text("connection_mailbox",$database->registry->imap->mailbox,80) . "</td>";
    $results[]="</tr>";
    $results[]="<tr>";
    $results[]="<td>Mailbox (Sent)</td>";
    $results[]="<td>" . $forms->text("connection_sent",$database->registry->imap->sent,80) . "</td>";
    $results[]="</tr>";
    $results[]="<tr>";
    $results[]="<tr>";
    $results[]="<td>Email</td>";
    $results[]="<td>" . $forms->text("connection_email",$database->registry->imap->email,80) . "</td>";
    $results[]="</tr>";
    $results[]="<tr>";
    $results[]="<tr>";
    $results[]="<td>Password</td>";
    $results[]="<td>" . $forms->text("connection_password",$database->registry->imap->password,80) . "</td>";
    $results[]="</tr>";
    $results[]="<tr>";
    $results[]="</table>";
    $buttons=array();
    $buttons[]=$forms->button("Test Connection",array("onclick"=>"fn_action('connection');"));
    $buttons[]=$forms->button("Reset Connection",array("onclick"=>"fn_action('reset');"));
    $results[]="<p>" . implode(" ",$buttons) . "</td>";
    return $results;
}
function tab_search() {
global $database, $forms, $menu;
	$results=array();
    $results[]="<table class='standard noborder'>";
    for ($i=0; $i < 5; $i++) {
	    $results[]="<tr>";
    	$results[]="<td width='20%'>Search criteria {$i}</td>";
        $text=array();
        $text[]=$forms->optgroup("search_criteria{$i}",$menu->imap->constant->search(),"",TRUE,array("onchange"=>"fn_action('search_criteria{$i}');"));
        $text[]=" ";
        $text[]=$forms->span("hi",array("id"=>"search_label{$i}","style"=>"display: none;"));
        $text[]=$forms->text("search_text{$i}","",60,0,"",array("style"=>"display: none;"));
        $results[]="<td width='80%'>" . implode("",$text) . "</td>";
    	$results[]="</tr>";
    }
    $results[]="</table>";
    $results[]="<p>" . $forms->button("Search",array("onclick"=>"fn_action('search');")) . "</p>";
    $results[]=$forms->div("",array("id"=>"search_match"));
    return $results;
}
function tab_view_message() {
global $database, $forms, $menu;
	return "<iframe id='view_message' width='100%' height='600px' style='border:none;'></iframe>";
}
function tab_mail() {
global $database, $forms, $menu;
	$results=array();
    $results[]="<table class='standard noborder'>";
    $results[]="<tr>";
    $results[]="<td width='20%'>From:</td>";
    $results[]="<td width='80%'><b>" . $database->registry->imap->email . "</b></td>";
    $results[]="</tr>";
    $results[]="<tr>";
    $results[]="<td width='20%'>To:</td>";
    $results[]="<td width='80%'>" . $forms->text("mail_to","tom@suburbancomputer.com",60) . "</td>";
    $results[]="</tr>";
    $results[]="<tr>";
    $results[]="<td width='20%'>CC:</td>";
    $results[]="<td width='80%'>" . $forms->text("mail_cc","",60) . "</td>";
    $results[]="</tr>";
    $results[]="<tr>";
    $results[]="<td width='20%'>BCC:</td>";
    $results[]="<td width='80%'>" . $forms->text("mail_bcc","",60) . "</td>";
    $results[]="</tr>";
    $results[]="<tr>";
    $results[]="<td width='20%'>Subject:</td>";
    $results[]="<td width='80%'>" . $forms->text("mail_subject","IMAP Test",60) . "</td>";
    $results[]="</tr>";
    $results[]="<tr>";
    $results[]="<td width='20%'>Body:</td>";
    $results[]="<td width='80%'>" . $forms->textarea("mail_body","",6,60) . "</td>";
    $results[]="</tr>";
    $results[]="</table>";
    $results[]="<p>" . $forms->button("Send Email",array("onclick"=>"fn_action('mail');")) . "</p>";
    return $results;
}
function tab_mailbox() {
global $database, $forms, $menu;
	$results=array();
    $results[]="<p>" . $forms->button("Mailbox List",array("onclick"=>"fn_action('mailbox');")) . "</p>";
    $results[]=$forms->div("",array("id"=>"mailbox_html"));
    return $results;
}
?>