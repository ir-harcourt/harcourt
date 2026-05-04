<?php
if (!$database->registry->cms->frame) fn_url_redirect($_SERVER['HTTP_REFERER']);
$forms->title("Content Management - Date Spoofing");
$forms->message[]="Revised 01/10/2013";
$forms->report['action']=$_POST['action'];
if (!$forms->report['action']) {
	$forms->report['date']=$_SESSION['cmsdate'];
	$forms->report['debug']=$_SESSION['cmsdebug'];
  } else {
	$forms->report['date']=fn_date($_POST['date'],"mdy");
	$forms->report['debug']=$_POST['debug'];
    switch (TRUE) {
      case (!$date_ok):
      	$forms->error('date');
        break;
      case ($forms->report['date']):
		$_SESSION['cmsdate']=$forms->report['date'];
		$_SESSION['cmsdebug']=$forms->report['debug'];
        $forms->message[]="Session date updated";
        break;
	  default:
		$forms->report['debug']=FALSE;
		unset($_SESSION['cmsdate']);
		unset($_SESSION['cmsdebug']);
        $forms->message[]="Session date removed";
	}
}
if (isset($_SESSION['cmsdate'])) $forms->message[]="Session date: " . fn_date($_SESSION['cmsdate'],"ymd");
$menu->head();
$forms->message();

print $forms->open();
print $forms->hidden("action","update");

print "<p>Spoof date: " . $forms->text("date",$forms->report['date'],10,10,"date:ymd");
print "<br>Include meta information on CMS pages? " . $forms->checkbox("debug",1,$forms->report['debug']);
print "<br>" . $forms->submit("Go!");
print "</p>\n";

print $forms->close();
$menu->copyright();
?>