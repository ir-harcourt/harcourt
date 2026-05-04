<?php
include_once "/Webpages/Header_Footer/scs_header.php";
include_once "classes/category.php";
include_once "classes/subcategory.php";
include_once "classes/document.php";
include_once "classes/inventory.php";
$menu->access();
$menu->title="Inventory Manager";
$database->connect();
$menu->messages[]=$menu->title;
$report['action']=$_POST['action'];
$report['report_subcategory_id']=$_POST['report_subcategory_id'];
$report['report_sku']=strtoupper(trim($_POST['report_sku']));
if ($report['report_sku']) {
	$database->inventory->read($report['report_sku'],"sku");
    if ($database->inventory->meta->rows) {
		$report['action']="maintain";
		$report['record_id']=$database->inventory->data->id;
		$report['report_subcategory_id']==$database->inventory->data->subcategory_id;
	  } else {
      	$menu->errors['report_sku']=$menu->background['red'];
    }
  } else {
	$report['record_id']=$_POST['record_id'];
	if ($report['record_id']) $database->inventory->read($report['record_id']);
}
switch (TRUE) {
  case ($report['action'] == ""):
    break;
  case ($report['action'] =="maintain"):
    if (!$database->inventory->data->id) {
    	$database->inventory->data=new inventory_data_class();
        $database->inventory->data->subcategory_id=$report['report_subcategory_id'];
    }
    $_SESSION['inventory']=TRUE;
    break;
  case ($report['action'] == "delete"):
    $database->inventory->delete($report['record_id']);
    $menu->messages[]="Category " . $database->category->data->code . " deleted";
    break;
  case (!isset($_SESSION['inventory'])):
	break;
  case ($report['action'] == "cancel"):
    $menu->messages[]="Request cancelled";
    unset($_SESSION['inventory']);
    break;
  case ($report['action'] == "subcategory"):
  case ($report['action'] == "update"):
	$database->inventory->data->subcategory_id=$_POST['subcategory_id'];
    if (!$database->inventory->data->subcategory_id) $menu->errors['subcategory_id']=$menu->background['red'];
	$database->subcategory->read($database->inventory->data->subcategory_id);
	$database->inventory->data->category_id=$database->subcategory->data->category_id;
	$database->inventory->data->sku=rtrim(strtoupper($_POST['sku']));
	$database->inventory->data->name=$_POST['name'];
	$database->inventory->data->search_words=trim($_POST['search_words']);
	$database->inventory->data->prj=trim($_POST['prj']);
	$database->inventory->data->active=intval($_POST['active']);
    foreach ($database->inventory->constant->documents as $key => $value) {
		if (!isset($database->inventory->data->documents[$key])) $database->inventory->data->documents[$key]=new document_inventory_class();
	    $field_document_id=base64_encode($key . "\t" . "document_id");
	    $field_html=base64_encode($key . "\t" . "html");
		switch (TRUE) {
		  case ($_POST[$field_document_id]):
			$database->inventory->data->documents[$key]->document_id=$_POST[$field_document_id];
			$database->inventory->data->documents[$key]->html="";
			break;
		  case ($_POST[$field_html]):
			$database->inventory->data->documents[$key]->html=$_POST[$field_html];
			$database->inventory->data->documents[$key]->document_id=0;
			break;
          default:
			$database->inventory->data->documents[$key]->document_id=0;
			$database->inventory->data->documents[$key]->html="";
        }
    }
    $database->inventory->data->parameters=array();
	if ($report['action'] == "subcategory") break;
    if (sizeof($database->subcategory->data->parameters)) {
        foreach ($database->subcategory->data->parameters as $key => $value) {
			$field=base64_encode("parameter" . "\t" . $key);
			$database->inventory->data->parameters[$key]=$_POST[$field];
        }
    }
    if (!$database->inventory->data->sku) $menu->errors['sku']=$menu->background['red'];
	if (sizeof($menu->errors)) break;
	$database->inventory->update($database->inventory->meta->rows);
	if (mysql_errno()) {
	    $menu->errors['sku']=$menu->background['red'];
	    $menu->messages[]=mysql_error();
	  } else {
    	$menu->messages[]="Record maintained";
		unset($_SESSION['inventory']);
	}
	break;
}
$menu->head();
$menu->message();
?>
<script type="text/javascript">
function fn_action(obj,action,id,name) {
	if (action=='delete') {
	    if (!confirm("Delete: #" + id + " " + name)) {
	        obj.checked=false;
            return;
	    }
    }
	document.scs_form.action.value=action;
	document.scs_form.record_id.value=id;
	document.scs_form.submit();
}
</script>
<?php
print "<form name=scs_form method=post action='" . $_SERVER['PHP_SELF'] . "'>\n";
print "<input type=hidden name=action>\n";
print "<input type=hidden name=record_id>\n";

switch (TRUE) {
  case ($report['action'] == "maintain"):
  case ($report['action'] == "subcategory"):
  case (($report['action'] == "update") && (sizeof($menu->errors))):
	print "<input type=hidden name=report_subcategory_id " . fn_html_value($report['report_subcategory_id']) . ">\n";
	print "<table class='standard noborder'>\n";
    print "<tr>\n";
    print "<td width=30%>Category/Subcategory</td>\n";
    print "<td width=70%>";
	$database->subcategory->select_option=new subcategory_select_option($database->inventory->data->subcategory_id);
	$database->subcategory->select_option->script="fn_action(this,'subcategory','" . $_POST['record_id'] . "','');";
	$database->subcategory->select();
	print "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>SKU</td>\n";
    print "<td><input type=text name=sku size=20 " . fn_html_value($database->inventory->data->sku,"","",$menu->errors['sku']) . "></td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Partsolutions PRJ</td>\n";
    print "<td>" . $menu->textarea("prj",$database->inventory->data->prj,6,60) . "</td>\n";
    print "</tr>\n";
    if (sizeof($database->subcategory->data->parameters)) {
		$database->subcategory->read($database->inventory->data->subcategory_id);
		$database->category->read($database->subcategory->data->category_id);
    	print "<tr><td colspan=2><br><b>" . $database->category->data->name . ": " . $database->subcategory->data->name . "</b></td></tr>\n";
        foreach ($database->subcategory->data->parameters as $key => $value) {
			$field=base64_encode("parameter" . "\t" . $key);
        	print "<tr>\n";
            print "<td>$value</td>\n";
            print "<td><input type=text name=$field size=30 " . fn_html_value($database->inventory->data->parameters[$key]) . "></td>\n";
            print "</tr>\n";
        }
    }
    foreach ($database->inventory->constant->documents as $key => $value) {
	    $field_document_id=base64_encode($key . "\t" . "document_id");
	    $field_html=base64_encode($key . "\t" . "html");
    	print "<tr><td colspan=2><br><b>$value</b></td></tr>\n";
        if ($key != "description") {
	        print "<tr>\n";
	        print "<td>Document ID</td>\n";
	        print "<td>\n";
	        print $database->document->select($database->inventory->data->documents[$key]->document_id,$field_document_id);
	        print "</td>\n";
	        print "</tr>\n";
		}
    	print "<tr>\n";
        print "<td>HTML Text</td>\n";
		print "<td>" . $menu->textarea($field_html,$database->inventory->data->documents[$key]->html,6,60) . "</td>\n";
        print "</tr>\n";
    }
    print "<tr>\n";
    print "<td>Search words</td>\n";
    print "<td>" . $menu->textarea("search_words",$database->inventory->data->search_words,6,60) . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Active?</td>\n";
    print "<td>" . $menu->checkbox("active", $database->inventory->data->active) . "</td>\n";
    print "</tr>\n";
    print "</table>\n";
	print "<p class='standard center'>\n";
	print "<input class=fbutton type=button value='Update' onclick=\"javascript:fn_action(this,'update','" . $database->inventory->data->id . "','');\"> \n";
	print "<input class=fbutton type=button value='Cancel' onclick=\"javascript:fn_action(this,'cancel','','');\">\n";
	print "</p>\n";
  	break;
  default:
	print "<table class='large noborder'>\n";
    print "<tr>\n";
    print "<th>SKU</th>\n";
    print "<th>Category/Subcategory</th>\n";
    print "<th>&nbsp;</th>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td class=center><input type=text name='report_sku' size=20 " . fn_html_value($report['report_sku'],"","",$menu->errors['report_sku']) . "></td>\n";
    print "<td class=center>\n";
	$database->subcategory->select_option=new subcategory_select_option($report['report_subcategory_id']);
	$database->subcategory->select_option->field="report_subcategory_id";
	$database->subcategory->select_option->script="fn_action(this,'list','','');";
	if (!$report['report_subcategory_id']) $database->subcategory->select_option->blank=TRUE;
	$database->subcategory->select();
    print "</td>\n";
    print "<td class=center><input class=fbutton type=button value='List' onclick=\"javascript:fn_action(this,'list','','');\">\n";
    print "</tr>\n";
    print "</table>\n";
    if (!$report['report_subcategory_id']) break;
    $database->subcategory->read($report['report_subcategory_id']);
    $colspan=2 + sizeof($database->subcategory->data->parameters);
	print "<table class='small border'>\n";
    print "<caption>&nbsp;</caption>\n";
    print "<tr>\n";
    print "<th>SKU</th>\n";
    if (sizeof($database->subcategory->data->parameters)) {
		foreach ($database->subcategory->data->parameters as $key => $value) {
        	print "<th>$value</th>\n";
        }
    }
    print "<th>Del?</th>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td colspan=$colspan><a href=\"javascript:fn_action(this,'maintain','0','');\">Add new</a></td>\n";
    print "</tr>\n";
    $query_where=array();
    if ($report['report_subcategory_id']) $query_where[] = new query_where("inventory.subcategory_id","=",$report['report_subcategory_id']);
    $query =
        "select \n" .
        "inventory.* from inventory \n" .
        $database->where($query_where) .
        "order by inventory.sku";
    $database->inventory->query($query);
    while ($database->inventory->fetch = mysql_fetch_array($database->inventory->meta->handle)) {
        $database->inventory->fetch();
        print "<tr>\n";
        print "<td>" . "<a href=\"javascript:fn_action(this,'maintain','" . $database->inventory->data->id . "','');\">" . $database->inventory->data->sku . "</a></td>\n";
	    if (sizeof($database->subcategory->data->parameters)) {
	        foreach ($database->subcategory->data->parameters as $key => $value) {
	            print "<td class=center>" . $database->inventory->data->parameters[$key] . "</td>\n";
	        }
	    }
        print "<td class=center>";
        if (!$database->inventory->data->active) {
            print "<input type=radio name='delete' onclick=\"javascript:fn_action(this,'delete','" . $database->inventory->data->id . "','" . $database->inventory->data->sku . "');\">\n";
        }
        print "</td>\n";
        print "</tr>\n";
    }
    print "</table>\n";
	$database->inventory->free_result();
}
print "</form>\n";
$database->disconnect();
$menu->copyright();
?>