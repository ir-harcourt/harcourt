<?php
$forms->title("Browser Compatibility");
$forms->message[]="Revised 01/23/2018";
$forms->report['action']=$_REQUEST['action'];
$forms->report['_login']=$_REQUEST['_login'];
list($forms->report['user_id'],$forms->report['login_timestamp'],$forms->report['url'])=preg_split("/\t/",base64_decode($_REQUEST['_login']));
$database->user->read($forms->report['user_id']);
switch (TRUE) {
  case (!$database->user->meta->rows):
  case ($database->user->data->login_timestamp != $forms->report['login_timestamp']):
    die;
  case ($forms->report['action']=="cookie"):
	$database->user->session(TRUE);
    $database->log->update("User: Compatibility","",$database->user->data->id);
	print "<script type='text/javascript'>\n";
	print "window.close();\n";
	print "</script> \n";
	die;
  case (!isset($_SESSION['user'])):
    break;
  case ($forms->report['url']):
    fn_url_redirect($forms->report['url']);
    break;
  default:
    print "<script type='text/javascript'>\n";
    print "window.close();\n";
    print "</script> \n";
    die;
}
$head=TRUE;
switch ($forms->report['action']) {
  case "popup":
	$forms->message[]="Step #1 - Set Cookie";
	$head=FALSE;
	break;
  case "verify":
	$forms->message[]="Verify failed";
	break;
}
$menu->head($head);
$forms->message();

print "<script type='text/javascript'>\n";
print "function fn_action(action) { \n";
print "    if (action=='popup') {\n";
print "        window.open('" . $menu->page->login_verify->page . "?_login=" . $forms->report['_login'] . "&action=popup','_blank');\n";
print "      } else {\n";
print "        document.scs_form.action.value=action;\n";
print "        document.scs_form.submit();\n";
print "    }\n";
print "} \n";
print "</script>\n";

print $forms->open();
print $forms->hidden("action");
foreach ($_REQUEST as $key => $value) {
	if ($key != "action") print $forms->hidden($key,$value);
}
print "<p class=standard>Welcome: " . $database->user->data->name . "</p>\n";
switch ($forms->report['action']) {
  case "popup":
	print "<p class=standard>" . $forms->button("Set cookie and close window",array("onclick"=>"fn_action('cookie');")) . "</p>\n";
	break;
  default:
	print "<p class=standard>You have landed on the pages because your browser in not fully compatible with this portion of our web site.";
	print "This situation often occurs with mobile devices and the Safari browser.";
	print "<br>Your browser: " . $_SERVER['HTTP_USER_AGENT'];
	print "</p>\n";
	print "<p class=standard>Please follow the two step process to resolve the issue.</p>\n";
	print
	    "<p class=standard>" .
	    $forms->button("Step #1 Set cookie in popup",array("onclick"=>"fn_action('popup');")) .
	    " " . $forms->button("Step #2 Verify compatibility",array("onclick"=>"fn_action('verify');")) .
	    "</p>\n";
	print "<p class=standard>Your browser must allow for popups.</p>\n";
	break;
}
print $forms->close();
$menu->copyright();
?>