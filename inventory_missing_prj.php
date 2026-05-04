<?php
require_once "scs_header.php";
require_once "classes/inventory.php";

$database->user->access("Administrator");
$forms->title("Inventory Missing PRJ");
$menu->head();
print $forms->message();
print $forms->open();

$contents=array();
$query=
	"select \n" .
	"inventory.subcategory_id as 'subcategory_id', \n" .
    "if (isnull(category.id),concat('Missing Category #',subcategory.category_id),category.name) as category_name, \n" .
	"subcategory.category_id as 'category_id', \n" .
    "if (isnull(subcategory.id),concat('Missing Subcategory #',inventory.subcategory_id),subcategory.name) as subcategory_name, \n" .
	"subcategory.name as 'subcategory_name', \n" .
	"inventory.sku as sku \n" .
	"from inventory \n" .
	"left join subcategory on subcategory.id=inventory.subcategory_id \n" .
	"left join category on category.id=subcategory.category_id \n" .
	"where inventory.prj='' \n" .
	"order by category.sort_order,subcategory.sort_order,inventory.sku";
$database->temp->query($query);
while ($database->temp->fetch = $database->temp->fetch_array() ) {
	if (!array_key_exists($database->temp->fetch['subcategory_id'],$contents)) $contents[$database->temp->fetch['subcategory_id']]=new missing_prj_class($database->temp->fetch);
	$contents[$database->temp->fetch['subcategory_id']]->sku[]=$database->temp->fetch['sku'];
}
$database->temp->free_result();
print "<div class='accordion'>\n";
foreach ($contents as $subcategory_id => $item) {
    print "<h3>" . $item->category_name . ": " . $item->subcategory_name . " (" . sizeof($item->sku). ")</h3>\n";
    print "<div>\n";
    print implode("<br>\n",$item->sku);
    print "</div>\n";
}
print "</div>\n";
print $forms->close();
$menu->copyright();
class missing_prj_class {
	var $category_id=0;
	var $category_name;
	var $subcategory_id=0;
	var $subcategory_name;
    var $sku=array();
    function __construct($fetch) {
		$this->category_id=$fetch['category_id'];
		$this->category_name=$fetch['category_name'];
		$this->subcategory_id=$fetch['subcategory_id'];
		$this->subcategory_name=$fetch['subcategory_name'];
    }
}
?>