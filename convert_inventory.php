<?php
require_once "scs_header.php";
require_once "classes/inventory.php";
$database->user->access("Super User");
$forms->title("Inventory Price Conversion");
$forms->report['action']=$_POST['action'];

// $database->inventory->read('HBH-8B','sku');

$update_timestamp=strtotime("now");
switch (TRUE) {
  case ($forms->report['action']):
	set_time_limit(300);
    $database->temp->query("select id, xpricing from inventory where isnull(price) order by sku");
    while ($database->temp->fetch = $database->temp->fetch_array()) {
    	$items=unserialize($database->temp->fetch['xpricing']);
		$obj=array();
        $obj['USD']=array();
        foreach ($items as $i => $item) {
            $item=(array) $item;
            $qty_break=$item['qty_break'];
        	$obj['USD'][$qty_break]=$item['price'];
        }
		$query=array("update inventory");
        $query[]="set price=" . fn_escape(json_encode($obj));
        $query[]="where id=" . fn_escape($database->temp->fetch['id']);
        $database->inventory->query($query);
	}
    $forms->message[]=number_format($database->temp->meta->rows) . " Records converted";
}
$menu->head();
print $forms->message();

print $forms->open();
print $forms->hidden("action","update");

print $forms->submit("Go");


print $forms->close();
$menu->copyright();
?>