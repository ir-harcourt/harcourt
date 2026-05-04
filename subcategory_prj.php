<?php
require_once "scs_header.php";
require_once "classes/subcategory.php";
$database->user->access("Administrator");
$forms->title("Subcategory PRJ Review");

$items=array();
$query=array("select * from subcategory");
$query[]="where not isnull(prj)";
$query[]="order by name";
$database->subcategory->query($query);
while ($database->subcategory->fetch = $database->subcategory->fetch_array()) {
    $database->subcategory->fetch();
    $prj=str_replace("info=", "", $database->subcategory->data->prj);
    $code=end(explode("/", $prj));
    if (!array_key_exists($code, $items)) $items[$code]=array();
    $obj=new stdClass();
    $obj->prj=$prj;
    $obj->name=$database->subcategory->data->name;
    $items[$code][$database->subcategory->data->id]=$obj;
}
ksort($items);
$menu->head();
print $forms->message();
print $forms->open();
print "<table class='border'>";
print "<thead>";
print "<tr>";
print "<th>Suffix</th>";
print "<th>Subcategory ID</th>";
print "<th>Name</th>";
print "<th>SKU</th>";
print "<th>Project</th>";
print "</tr>";
print "</thead>";
print "<tbody>";
foreach ($items as $code => $item) {
    if (sizeof($item) == 1) continue;
    $i=0;
    foreach ($item as $id => $obj) {
        print "<tr>";
        if (!$i) print "<td rowspan=" . sizeof($item) . ">{$code}</td>";
        print "<td class=right>{$id}</td>";
        print "<td>{$obj->name}</td>";
        $query=array("select sku from inventory");
        $query[]="where subcategory_id=" . fn_escape($id);
        $query[]="limit 1";
        $database->temp->query($query);
        $database->temp->fetch();
        print "<td>{$database->temp->fetch['sku']}</td>";
        print "<td>{$obj->prj}</td>";
        print "</tr>";
        $i++;
    }
    print "</tbody>";
}
print "</table>";
print $forms->close();
$menu->copyright();
?>