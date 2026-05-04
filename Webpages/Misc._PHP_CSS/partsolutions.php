<?php
include_once "/Webpages/Header_Footer/scs_header.php";
include_once "classes/log.php";
$menu->access(13);
$database->log->create("Configurator");
switch (TRUE) {
  case (!$_SESSION['uemail']):
	break;
  case ($_GET['beta']):
	fn_url_redirect("/psconfig/v1/harcourt2/configurator.php");
    break;
  default:
	fn_url_redirect("http://configurator.partsolutions.com/v1/harcourt/configurator.php?uemail=" . $_COOKIE['email']);
}
/*
print "<html>\n";
print "<head>\n";
print "</head>\n";
print "<body>\n";
print "<form action=\"/psconfig/v1/harcourt2/configurator.php\" name=\"login\" method=\"post\">\n";
print "<input type=\"hidden\" name=\"email\" value=\"" . $_COOKIE['email'] . "\">\n";
print "</form>\n";
print "<script>\n";
print "document.login.submit();\n";
print "</script>\n";
print "</body>\n";
print "</html>\n";
*/
?>