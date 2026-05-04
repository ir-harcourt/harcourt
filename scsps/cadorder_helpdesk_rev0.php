<?php
require_once "classes/cadorder.php";
$forms->title("CAD Cart Helpdesk");
$forms->message[]="Revised " . key( $database->cadorder->scs_table_version() );

$menu->head();
$forms->message();

$results=array();
foreach ($database->cadorder->constant->message as $code => $message) {
	$results[$message->status][$code]=$message;
}
print "<table class='standard border'>\n";
print $forms->caption("<br><b>Error Codes</b>");
print "<tr>\n";
print "<th>Code</th>\n";
print "<th>Log Code</th>\n";
print "<th>Customer Error</th>\n";
print "<th>Log Error</th>\n";
print "</tr>\n";
foreach ($results as $status => $status_results) {
	print "<tr>\n";
    print "<td colspan=4><b>Status $status:</b> " . $database->cadorder->constant->status_description[$status] . "</td>\n";
    print "</tr>\n";
    foreach ($status_results as $code => $message) {
	    print "<tr>\n";
	    print "<td class=center>$code</td>\n";
	    print "<td>" . $message->log_subtype . "</td>\n";
	    print "<td>" . $message->external . "</td>\n";
	    print "<td>" . $message->internal . "</td>\n";
	    print "</tr>\n";
	}
}
print "</table>\n";
$menu->copyright();
?>