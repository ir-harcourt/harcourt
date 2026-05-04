<?php
include "scs_header.php";
include_once "classes/document.php";
$database->connect();
$menu->title="Harcourt Products";
switch (TRUE) {
  case (!sizeof($_SESSION['document'])):
	break;
  case (intval($_GET['page'])):
  	$report['page']=intval($_GET['page']);
    break;
  case ((sizeof($_SESSION['document']) == 1) && (!$_SESSION['user']->landing_document_id)):
	$report['page']=$_SESSION['document'][0];
    break;
}
if ($report['page']) {
	$database->document->read($report['page']);
    if ($database->document->results->error) $report['error']=$database->document->results->error;
}
if ($report['page'] != 171) $menu->search=TRUE;
switch (TRUE) {
  case ($report['error']):
	$menu->messages[]=$menu->title;
  	$menu->messages[]=$report['error'];
    unset ($report['page']);
	break;
  case ($report['page']):
    $menu->title="Harcourt: " . $database->document->data->name;
	break;
  case ($_SESSION['user']->landing_document_id):
	break;
  case ($_SESSION['user']->name):
	$menu->messages[]="Welcome " . $_SESSION['user']->company;
    break;
  default:
	$menu->messages[]=$menu->title;
}
ob_start();
if (!$_COOKIE['email']) $database->document->login_verify();
switch (TRUE) {
  case (!$_COOKIE['email']):
	$database->document->login_document();
	break;
  case (!sizeof($_SESSION['document'])):
  case ($report['error']):
	$menu->document_viewer(157,0,TRUE);
    break;
  case ((!$report['page']) && ($_SESSION['user']->landing_document_id)):
	$message="";
	$menu->document_viewer($_SESSION['user']->landing_document_id,-1,TRUE);
	break;
  case (!$report['page']):
	$message="Hello " . $_SESSION['user']->company . ". Welcome to our product website.";
	print "<div class=center>\n";
	print "<table width=800 style=\"border:solid;border-color: #dddddd #aaaaaa #aaaaaa #dddddd;border-width: 1px 2px 2px 1px;background-color:white;\">\n";
    print "<tr>\n";
    print "<td width=40>&nbsp;</td>\n";
    print "<td width=720 class=left>&nbsp;</td>\n";
    print "<td width=40>&nbsp;</td>\n";
    $query_where=array();
    $query_where[] = new query_where("id","in",$_SESSION['document']);
    $query_where[] = new query_where("file","<>","");
    $query =
    	"select * from document " .
        $database->where($query_where) .
        "order by id";
    $database->document->query($query);
    while ($database->document->fetch = mysql_fetch_array($database->document->meta->handle)) {
        $database->document->fetch();
		print "<tr><td colspan=3>&nbsp;</td></tr>\n";
    	print "<tr>\n";
        print "<td class=right><img src='" . $database->document->results->icon . "' border=0>&nbsp;&nbsp;</td>\n";
        print "<td class=left>";
        print "<a href='" . $menu->page["products"]->page . "?page=" . $database->document->data->id . "'>" . $database->document->data->name . "</a>";
        if ($database->document->data->description) print "<br>" . $database->document->data->description;
        print "</td>\n";
        print "<td>&nbsp;</td>\n";
        print "</tr>\n";
	}
    $database->document->free_result();
	print "<tr><td colspan=3>&nbsp;</td></tr>\n";
	print "</table>\n";
    print "</div>\n";
	break;
  case ($database->document->data->mime == "text/html"):
	print file_get_contents($database->document->results->file);
    break;
  case (!headers_sent()):
	header("Content-type: " . $database->document->data->mime);
	header("Content-Disposition: attachment; filename=\"" . $database->document->data->file ."\"");
	readfile($database->document->results->file);
	exit;
  default:
	print "<p class='standard center'><b>Headers already sent</b></p>\n";
    break;
}
$contents=ob_get_contents();
ob_clean();
$menu->head();
$menu->message();
print $contents;
$database->disconnect();
$menu->copyright();
?>