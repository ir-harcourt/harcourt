<?php
include "/Webpages/Header_Footer/scs_header.php";
$menu->access();

$menu->title="Table Status";
$menu->messages[]=$menu->title;
$menu->messages[]=date("F j, Y G:i A");
$database->connect();
$table_array=array();
$database->temp=new database_template_class();
$query="show table status";
$database->temp->query($query);
while ($database->temp->fetch = mysql_fetch_array($database->temp->meta->handle)) {
	$table_array[$database->temp->fetch['Name']]= new table_item($database->temp->fetch);
}
$database->temp->free_result();
$menu->head();
$menu->message();
print "<table class='small border'>\n";
print "<tr>\n";
print "<th>Name</th>\n";
print "<th>Description</th>\n";
print "<th>Update</th>\n";
print "<th>Total #records</th>\n";
print "</tr>\n";
foreach ($table_array as $key => $item) {
	print "<tr>\n";
    print "<td>";
    if ($_GET['table'] == $key) {
    	print "<b>$key</b><br>" . $item->create . "</td>";
      } else {
		print "<a href=\"table_status.php?table=" . $key . "\">$key</a>";
    }
    print "</td>\n";
    print "<td>" . $item->comment . "</td>\n";
    print "<td class=center>" . $item->update_time . "</td>\n";
    print "<td class=right>" . number_format($item->rows) . "</td>\n";
    print "</tr>\n";
}
print "</table>\n";

$database->disconnect();
$menu->copyright();

class table_item {
	var $rows;
    var $create_time;
    var $update_time;
    var $comment;
    var $create;
    function table_item($temp) {
	global $database;
	    $this->rows=$temp['Rows'];
	    $this->create_time=$temp['Create_time'];
	    $this->update_time=fn_date("datetime",$temp['Update_time']);
	    $this->comment=$temp['Comment'];
        $database->create= new database_template_class();
		$database->create->query("show create table " . $temp['Name']);
		$database->create->fetch = mysql_fetch_array($database->create->meta->handle);
		$database->create->free_result();
	    $this->create=str_replace("\n","<br>",$database->create->fetch['Create Table']);
    }
}
?>