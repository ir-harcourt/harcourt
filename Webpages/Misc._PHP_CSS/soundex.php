<?php
include_once "/Webpages/Header_Footer/scs_header.php";
include_once "classes/inventory.php";
$menu->access();
$menu->title="Rebuild Keywords Soundex";
$database->connect();
$menu->messages[]=$menu->title;
$report['action']=$_POST['action'];
if ($report['action']) {
	$query="select * from inventory order by sku";
    $database->inventory->query($query);
    while ($database->inventory->fetch = mysql_fetch_array($database->inventory->meta->handle)) {
        $database->inventory->fetch();
        $inventory[$database->inventory->data->id]=$database->inventory->data->sku;
	}
    $database->inventory->free_result();
    foreach ($inventory as $id => $sku) {
		$database->inventory->read($sku,TRUE);
		$database->inventory->search_words();
		$database->inventory->update(TRUE);
    }
}
$menu->head();
$menu->message();
if ($report['action']) {
	print "<p class='standard center'>" . number_format(sizeof($inventory)) . " keywords soundex records updated</p>\n";
  } else {
	print "<form name=scs_form method=post action='" . $_SERVER['PHP_SELF'] . "'>\n";
	print "<input type=hidden name=action value='update'>\n";
	print "<p class='standard center'>Update keywords? ";
	print "<input type=submit value='Update'></p>\n";
	print "</form>\n";
}
$database->disconnect();
$menu->copyright();
?>