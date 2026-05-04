<?php
include "/Webpages/Header_Footer/scs_header.php";
include_once "classes/user.php";
$menu->access();
$menu->title="Administrator Menu";
$database->connect();
$menu->messages[]=$menu->title;
$menu->search=TRUE;

$menu->head();
$menu->message();
?>
<script type="text/javascript">
function fn_user(id) {
	document.maintain_form.record_id.value=id;
	document.maintain_form.submit();
}
</script>
<?php
print "<form name=maintain_form method=post action='user.php'>\n";
print "<input type=hidden name=action value='maintain'>\n";
print "<input type=hidden name=return_url value='" . $_SERVER['PHP_SELF'] . "'>\n";
print "<input type=hidden name=record_id>\n";
print "</form>\n";
print "<p class='large center'>";
print "<a href='user.php'>User Manager</a> | \n";
print "<a href='user_impersonate.php'>User Impersonate</a> | \n";
print "<a href='document.php'>Document Manager</a> | \n";
print "<a href='document_utility.php'>Document Viewer Utility</a>\n";
print "</p>\n";

print "<p class='large center'>";
print "<a href='campaign_manager.php'>Email Campaign Manager</a>\n";
print "</p>\n";

print "<p class='standard center'>";
print "<a href='category.php'>Category Codes</a> |\n";
print "<a href='subcategory.php'>Subcategory Codes</a> |\n";
print "<a href='inventory.php'>Inventory Manager</a> |\n";
print "<a href='scs_utility.php'>Competitor SKU Utility</a> |\n";
print "<a href='log.php'>Access Log</a>\n";

print "</p>\n";

print "<p class='standard center'>";
print "<a href='cookie_reset.php'>Reset Cookie</a> |\n";
print "<a href='php_status.php'>PHP Status</a> |\n";
print "<a href='php_info.php'>PHP Info</a> |\n";
print "<a href='table_status.php'>Table Status</a>\n";
print "</p>\n";

$query =
	"select * from user \n" .
    "where status='Pending'" .
    "order by company";
$database->user->query($query);
if ($database->user->meta->rows) {
	print "<table class='standard border'>\n";
    print "<caption><b>Pending login requests</b></caption>\n";
    print "<tr>\n";
    print "<th>IP</th>\n";
    print "<th>DNS</th>\n";
    print "<th>Company</th>\n";
    print "<th>Name</th>\n";
    print "<th>Date/Time</th>\n";
    print "</tr>\n";
    while ($database->user->fetch = mysql_fetch_array($database->user->meta->handle)) {
        $database->user->fetch();
		print "<tr>\n";
        print "<td><a href=\"javascript:fn_user('" . $database->user->data->id . "');\">" . $database->user->data->ip . "</a></td>\n";
        print "<td>";
        if (!fn_development_server()) gethostbyaddr($database->user->data->ip);
		print "</td>\n";
        print "<td>" . $database->user->data->company . "</td>\n";
        print "<td>" . $database->user->data->name . "</td>\n";
        print "<td class=center>";
        if ($database->user->data->last_login) {
			print date("m/d/Y H:i",$database->user->data->last_login);
          } else {
          	print "&nbsp;";
        }
        print "</td>\n";
        print "</tr>\n";
    }
    print "</table>\n";
}

$query =
	"select * from user \n" .
    "where last_login >= " . strtotime("-7 days") . "\n" .
    "order by last_login desc";
$database->user->query($query);
if ($database->user->meta->rows) {
	print "<table class='standard border'>\n";
    print "<caption><br><b>Login in the past 7 days</b></caption>\n";
    print "<tr>\n";
    print "<th>IP</th>\n";
    print "<th>DNS</th>\n";
    print "<th>Company</th>\n";
    print "<th>Name</th>\n";
    print "<th>Date/Time</th>\n";
    print "</tr>\n";
    while ($database->user->fetch = mysql_fetch_array($database->user->meta->handle)) {
        $database->user->fetch();
        $url_query=array();
        $url_query['action']="login";
        $url_query['ip']=$database->user->data->ip;
		print "<tr>\n";
        print "<td><a href=\"" . fn_url("/log.php",FALSE,$url_query) . "\">" . $database->user->data->ip . "</a></td>\n";
        print "<td>";
        if (!fn_development_server()) print gethostbyaddr($database->user->data->ip);
		print "</td>\n";
        print "<td>" . $database->user->data->company . "</td>\n";
        print "<td>" . $database->user->data->name . "</td>\n";
        print "<td class=center>";
        if ($database->user->data->last_login) {
			print date("m/d/Y H:i",$database->user->data->last_login);
          } else {
          	print "&nbsp;";
        }
        print "</td>\n";
        print "</tr>\n";
    }
    print "</table>\n";
}

$database->disconnect();
$menu->copyright();
?>