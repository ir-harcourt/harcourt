<?php
include "/Webpages/Header_Footer/scs_header.php";
echo "<pre>\n";
print "<p>Session data</p>";
print_r($_SESSION);
print "<p>Cookie data</p>";
print_r($_COOKIE);
print "<p>Request data</p>";
print_r($_REQUEST);
print "<p>Server data</p>";
print_r($_SERVER);
echo "</pre>\n";
if ($_SERVER['HTTP_REFERER']) {
	print
    	"<p align=center>" .
        "<input type=button value='Return' onclick=\"javascript:location.href=('" . $_SERVER['HTTP_REFERER'] . "');\">" .
        "</p>\n";
}
?>