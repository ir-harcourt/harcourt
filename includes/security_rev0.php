<?php
$forms->title("Security Test (06/12/2015)");
$forms->constant->role=array("type"=>"Type","role"=>"Role");

$menu->head();
$forms->message();
print $forms->open();

if (isset($database->user->constant->role)) {
	$role="role";
  } else {
	$role="type";
}
$text=array();
if (isset($_SESSION['impersonate'])) $text[]="User impersonation";
if ($_SESSION['user']->id) {
	$text[]="Logged in";
	$text[]=$forms->constant->role[$role] . ": " .  $_SESSION['user']->$role;
  } else {
	$text[]="Not logged in";
}
$text[]="";
foreach ($database->user->constant->$role as $value) {
	$text[]=$value . " ... " . ($database->user->access($value,FALSE) ? "Allowed" : "Denied");
}
print "<p>" . implode("<br>\n",$text) . "</p>\n";
$forms->constant->scs=array();
$forms->constant->php=array();
$forms->constant->html=array();

$dir = dir($_SERVER['DOCUMENT_ROOT']);
while (false !== ($item = $dir->read())) {
	switch (TRUE) {
	  case (preg_match("/^scs\_/i",$item)):
		$forms->constant->scs[]=fn_href($item,$item);
		break;
	  case (preg_match("/\.php$/i",$item)):
		$forms->constant->php[]=fn_href($item,$item);
		break;
	  case (preg_match("/\.htm$/i",$item)):
	  case (preg_match("/\.html$/i",$item)):
		$forms->constant->html[]=fn_href($item,$item);
		break;
    }
}
$dir->close();
if (sizeof($forms->constant->php)) {
	print "<hr>\n";
	print "<p><b>PHP Programs</b>\n<br>" . implode("\n<br>",$forms->constant->php) . "\n</p>\n";
}
if (sizeof($forms->constant->php)) {
	print "<hr>\n";
	print "<p><b>SCS Programs</b>\n<br>" . implode("\n<br>",$forms->constant->scs) . "\n</p>\n";
}
if (sizeof($forms->constant->html)) {
	print "<hr>\n";
	print "<p><b>HTML Programs</b>\n<br>" . implode("\n<br>",$forms->constant->html) . "\n</p>\n";
}
print $forms->close();
$menu->copyright();

?>