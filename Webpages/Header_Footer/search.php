<?php
include "scs_header.php";
include_once "classes/inventory.php";
switch (TRUE) {
  case (!$_SESSION['user']->id):
  case ($_SESSION['user']->status == "Pending"):
  case ($_SESSION['user']->status == "Denied"):
	fn_url_redirect($menu->page['home']);
}
$database->connect();
$menu->search=TRUE;
$menu->title="Search Results";
$menu->messages[]=$menu->title;
$query_imput=strip_tags($_REQUEST['q']);
if ($query_imput) {
	$q=split(" ",$query_imput);
	$query_where=array();
	$query_where[] = new query_where("inventory.active","=","1");
    $item_list="";
	$database->inventory->meta->search_words=array();
    foreach ($q as $item) {
	    $item=strtolower($item);
        $database->inventory->search_words_load($item,TRUE);
    }
	if (sizeof($database->inventory->meta->search_words)) $query_where[] = new query_where("inventory.search_words","match",$database->inventory->meta->search_words,"","in boolean mode");
    $query =
    	"select \n" .
        "category.major as 'category.major', \n" .
        "category.minor as 'category.minor', \n" .
        "inventory.* from inventory \n" .
 		"left join category on category.code=inventory.category_code \n" .
        $database->where($query_where,TRUE) .
        "or (inventory.sku like " . fn_escape("%" . $q[0] ."%") . ") \n" .
        "order by category.major, category.minor, inventory.description, inventory.sku";
	$database->inventory->query($query);
}
switch (TRUE) {
  case (!$query_imput):
  	$menu->messages[]="Search terms required";
	break;
  case (!$database->inventory->meta->rows):
	$menu->messages[]="No items found";
	break;
  default:
	$menu->messages[]=number_format($database->inventory->meta->rows) . " items found for <b>$query_imput</b>";
}
$menu->head();
$menu->message();
?>
<script langauge=javascript>
<?
print "email='" . $_COOKIE['email'] . "';\n";
?>
function configurator(action,data) {
	obj=document.getElementById('search_configurator');
    switch (true) {
	  case (action == "tree"):
		obj.src="/psconfig/v1/harcourt2/configurator.php?uemail=" + email + "&tree=" + data;
    	break;
	  case (action == "prj"):
		obj.src="/psconfig/v1/harcourt2/configurator.php?uemail=" + email + "&prj=" + data;
    	break;
      default:
    	return;
    }
    obj.style.height=550;
    obj.focus();
}
</script>
<?php
if ($database->inventory->meta->rows) {
    print "<iframe id=search_configurator style=\"height: 0px; width: 1050px; border: 0px; frameborder: 0px;\" frameBorder=\"No\"></iframe>\n";
	print "<table class='border standard'>\n";
    print "<tr>\n";
    print "<th colspan=2>Category</th>\n";
    print "<th>Stock#</th>\n";
    print "<th>Description</th>\n";
	print "</tr>\n";
    while ($database->inventory->fetch = mysql_fetch_array($database->inventory->meta->handle)) {
		$database->inventory->fetch();
        $href=array();
        switch (TRUE) {
          case (!$_COOKIE['email']):
          case (!$database->inventory->data->category_code):
          case (!in_array(13,$_SESSION['document'])):
        	break;
          default:
        	$href['tree']=split("/",$database->inventory->data->category_code);
            if ($href['tree'][0]) {
	            $href['major'][0]="<a href=\"javascript:configurator('tree','" . $href['tree'][0] . "');\">";
	            $href['major'][1]="</a>";
            }
            if ($href['tree'][1]) {
	            $href['minor'][0]="<a href=\"javascript:configurator('prj','" . $database->inventory->data->category_code . "');\">";
	            $href['minor'][1]="</a>";
            }
		}
    	print "<tr>\n";
        print "<td>" . $href['major'][0] . $database->inventory->fetch['category.major'] . $href['major'][1] . "</td>\n";
        print "<td>" . $href['minor'][0] . $database->inventory->fetch['category.minor'] . $href['minor'][1] . "</td>\n";
        print "<td>" . $database->inventory->data->sku . "</td>\n";
        print "<td>" . $database->inventory->data->description . "</td>\n";
    }
    print "</table>\n";
	print "<p>&nbsp;</p>\n";
}
print "<p class=standard><b>This is a boolean search</b> where plus (+) indicates include and minus (-) indicates exclude<br>\n";
print "Omitted operators are assumed to mean include (+)<br>\n";
print "Minimimum search term length is 4 characters<br>\n";
print "<b>+L-PINS +METRIC</b> yields all L-Pins that are metric<br>\n";
print "<b>+L-PINS -METRIC</b> yields all L-Pins that are not metric<br>\n";
print "<b>HLPM-20</b> yields all SKU's that contain HLPM-20</p>\n";
$database->disconnect();
$menu->copyright();
?>