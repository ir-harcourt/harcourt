<?php
require_once "scs_header.php";
require_once "classes/subcategory.php";
require_once "classes/inventory.php";
require_once "classes/supplier.php";
require_once "classes/suppliersku.php";
$database->user->access("Administrator");
$forms->title("Supplier SKU's");
$forms->report['action']=$_POST['action'];
$forms->report['record_id']=$_POST['record_id'];
$forms->report['report_supplier_id']=intval($_POST['report_supplier_id']);
$forms->report['report_category_subcategory']=$_POST['report_category_subcategory'];
list($forms->report['category_id'],$forms->report['subcategory_id'])=explode(":",$forms->report['report_category_subcategory']);
$forms->report['ajax_suppliersku_sku']=$_POST['ajax_suppliersku_sku'];
$forms->report['page']=intval($_POST['page']);
if ($forms->report['record_id']) {
	$database->suppliersku->read($forms->report['record_id']);
  } else {
    $database->suppliersku->data=new suppliersku_data_class();
}
switch (TRUE) {
  case (!$forms->report['action']):
    break;
  case ($forms->report['action'] == "cancel"):
    $forms->message[]="Request cancelled";
    break;
  case ($forms->report['action'] =="maintain"):
  	if (!$forms->report['record_id']) {
  		list($database->suppliersku->data->category_id,$database->suppliersku->data->subcategory_id)=explode(":",$_POST['report_category_subcategory']);
	}
    break;
  case ($forms->report['action'] == "delete"):
    $database->suppliersku->delete($forms->report['record_id']);
    $forms->message[]="Supplier SKU " . $database->suppliersku->data->suppliersku . " deleted";
    break;
  case ($forms->report['action'] == "update"):
	$database->suppliersku->data->supplier_id=$_POST['supplier_id'];
	$database->suppliersku->data->sku=fn_text($_POST['sku'],TRUE);
    if (!strlen($database->suppliersku->data->sku)) $forms->error('sku');
	$database->suppliersku->data->description=fn_text($_POST['description']);
    if (!strlen($database->suppliersku->data->description)) $forms->error("description");
    list($database->suppliersku->data->category_id,$database->suppliersku->data->subcategory_id)=explode(":",$_POST['category_subcategory']);
	$database->suppliersku->data->harcourt_sku=fn_text($_POST['harcourt_sku'],TRUE);
    if (strlen($database->suppliersku->data->harcourt_sku)) {
    	$database->inventory->read($database->suppliersku->data->harcourt_sku,"sku");
        if (!$database->inventory->meta->rows) $forms->error('harcourt_sku');
    }
	$database->suppliersku->data->price=fn_number($_POST['price'],2);
	$database->suppliersku->data->unit_measure=fn_text($_POST['unit_measure'],TRUE);
	$database->suppliersku->data->cost=fn_number($_POST['cost'],2);
	$database->suppliersku->data->comment=fn_text($_POST['comment']);
	$database->suppliersku->data->thru_timestamp=strtotime($_POST['thru_timestamp']);
	$database->suppliersku->data->active=$_POST['active'];
	if (sizeof($forms->error)) break;
    $database->suppliersku->update($database->suppliersku->meta->rows);
    if (!$database->suppliersku->meta->error) {
		$forms->message[]="Record maintained";
	  } else {
		$forms->error('sku',$database->suppliersku->meta->error);
    }
	break;
}
$menu->head();
print $forms->message();
?>
<script type='text/javascript'>
$( document ).ready(function() {
	$("#suppliersku_div").hide();
});
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
$(function() {
    $('#harcourt_sku').autocomplete({
        source: '/scs_ajax.php?table=inventory&detail=sku',
        minLength: 2,
        select: function(event, ui) {
			$("#category_subcategory").val(ui.item['category_subcategory_id']);
        },
        html: true,
        open: function(event, ui) {
            $('.ui-autocomplete').css('z-index', 1000);
        }
    });
});
</script>
<?php
print $forms->open();
print $forms->hidden("action");
print $forms->hidden("record_id");
print $forms->hidden("page",$forms->report['page']);

switch (TRUE) {
  case ($forms->report['action'] == "maintain"):
  case (($forms->report['action'] == "update") && (sizeof($forms->error))):
	print "<table class='large noborder'>";
    print "<tr>";
    print "<td width='30%'>Supplier</td>";
    print "<td width='70%'>" . $database->supplier->select($database->suppliersku->data->supplier_id) .  "</td>";
    print "</tr>";
    print "<tr>";
    print "<td width='30%'>Supplier SKU</td>";
    $text=array($forms->text("sku",$database->suppliersku->data->sku,60,60));
    if (array_key_exists("sku",$forms->error)) $text[]="SKU already assigned to supplier";
    print "<td width='70%'>" . implode("<br>",$text) .  "</td>";
    print "</tr>";
    print "<tr>";
    print "<td>Description</td>";
    print "<td>" . $forms->text("description",$database->suppliersku->data->description,60,80) .  "</td>";
    print "</tr>";
    print "<tr>";
    print "<td>Harcourt SKU</td>";
    print "<td>" . $forms->text("harcourt_sku",$database->suppliersku->data->harcourt_sku,45,45) .  "</td>";
    print "</tr>";
    print "<tr>";
    print "<td>Category/Subcategory</td>";
    $key=array($database->suppliersku->data->category_id);
    if ($database->suppliersku->data->subcategory_id) $key[]=$database->suppliersku->data->subcategory_id;
    print "<td>" . $database->subcategory->select(implode(":",$key),array("combined"=>"Unknown")) .  "</td>";
    print "</tr>";
    print "<tr>";
    print "<td>Unit Price</td>";
    print "<td>" . $forms->text("price",$database->suppliersku->data->price,11,11,"decimal:2") . " / " . $forms->text("unit_measure",$database->suppliersku->data->unit_measure,12,12) . "</td>";
    print "</tr>";
    print "<tr>";
    print "<td>Unit cost</td>";
    print "<td>" . $forms->text("cost",$database->suppliersku->data->cost,11,11,"decimal:2") .  "</td>";
    print "</tr>";
    print "<tr>";
    print "<td>Comment</td>";
    print "<td>" . $forms->textarea("comment",$database->suppliersku->data->comment,3,60) .  "</td>";
    print "</tr>";
    print "<tr>";
    print "<td>Price/cost thru date</td>";
    print "<td>" . $forms->text("thru_timestamp",(($database->suppliersku->data->thru_timestamp) ? date("Y/m/d",$database->suppliersku->data->thru_timestamp) : ""),10,10,"date") .  "</td>";
    print "</tr>";
	print "<tr>";
	print "<td>Active?</td>";
	print "<td>" . $forms->checkbox("active",1,$database->suppliersku->data->active) . "</td>";
	print "</tr>";
	print "</table>";
    $buttons=array();
    $buttons[]=$forms->button("Update",array("onclick"=>"fn_action(this,'update'," . fn_escape($forms->report['record_id']) . ",'');"));
    $buttons[]=$forms->button("Cancel",array("onclick"=>"fn_action(this,'cancel','','');"));
	print "<p class='standard'>" . implode("\n",$buttons) . "</p>\n";
  	break;
  default:
	print "<table class='standard noborder'>";
    print "<tr>";
    print "<td width='30%'>Supplier</td>";
    print "<td width='70%'>" . $database->supplier->select($forms->report['report_supplier_id'],array("field"=>"report_supplier_id"),array("onchange"=>"$('#page').val('0');")) . "</td>";
    print "</tr>";
    print "<tr>";
    print "<td>Category/Subcategory</td>";
    print "<td>" . $database->subcategory->select($forms->report['report_category_subcategory'],array("combined"=>"All"),array("name"=>"report_category_subcategory")) . "</td>";
    print "</tr>";
    print "<tr>";
    print "<td>Supplier SKU</td>";
    $text=array($forms->text("ajax_suppliersku_sku",$forms->report['ajax_suppliersku_sku'],60,60));
    $text[]="<div id='suppliersku_div'>";
    $text[]="Description: <span id=ajax_suppliersku_description></span>";
    $text[]="Category: <span id=ajax_suppliersku_category_name></span>";
    $text[]="Subcategory: <span id=ajax_suppliersku_subcategory_name></span>";
    $text[]="Supplier: <span id=ajax_suppliersku_supplier_name></span>";
    $text[]="Harcourt SKU: <span id=ajax_suppliersku_harcourt_sku></span>";
    $text[]="</div>";
    print "<td>" . implode("<br>",$text) .  "</td>";
    print "</tr>";
    print "</table>";
    print "<p>" . $forms->button("List",array("onclick"=>"fn_action(this,'list','0','0');")) . "</p>";

    if ($forms->report['action'] != "list") break;
    $query_where=array();
    if (strlen($forms->report['ajax_suppliersku_sku'])) {
    	$query_where[]=new query_where("suppliersku.sku","=",$forms->report['ajax_suppliersku_sku']);
	  } else {
	    if ($forms->report['report_supplier_id']) $query_where[]=new query_where("suppliersku.supplier_id","=",$forms->report['report_supplier_id']);
	    if ($forms->report['category_id']) $query_where[]=new query_where("suppliersku.category_id","=",$forms->report['category_id']);
	    if ($forms->report['subcategory_id']) $query_where[]=new query_where("suppliersku.subcategory_id","=",$forms->report['subcategory_id']);
    }
	$query=array("select");
    $query[]="ifnull(supplier.name,'None') as 'supplier.name',";
    $query[]="category.name as 'category.name',";
    $query[]="subcategory.name as 'subcategory.name',";
    $query[]="suppliersku.* from suppliersku";
    $query[]="left join supplier on supplier.id=suppliersku.supplier_id";
    $query[]="left join category on category.id=suppliersku.category_id";
    $query[]="left join subcategory on subcategory.id=suppliersku.subcategory_id";
    $query[]=$database->where($query_where);
    $query[]="order by suppliersku.sku, supplier.name";
    $query[]=$database->page_limit($forms->report['page']);
    $database->suppliersku->query($query,TRUE);
	print "<table class='small border tablesorter'>";
    print "<caption>&nbsp;</caption>";
    print "<thead>";
    print "<tr>";
    print "<th>Supplier SKU</th>";
    print "<th>Supplier</th>";
    print "<th>Description</th>";
    print "<th>Category: Subcategory</th>";
    print "<th>Price</th>";
    print "<th>Per</th>";
    print "<th>Cost</th>";
    print "<th>Del?</th>";
    print "</tr>";
    print "</thead>";
    print "<tbody>";
    print "<tr>";
    print "<td colspan=8>" . fn_href("Add new","javascript:fn_action(this,'maintain','0','');") ."</td>";
    print "</tr>";
    while ($database->suppliersku->fetch = $database->suppliersku->fetch_array()) {
	    $database->suppliersku->fetch();
	    print "<tr>";
	    print "<td>" . fn_href($database->suppliersku->data->sku,"javascript:fn_action(this,'maintain'," . fn_escape($database->suppliersku->data->id) . ",'');") . "</td>";
	    print "<td>" . $database->suppliersku->fetch['supplier.name'] . "</td>";
	    print "<td>" . $database->suppliersku->data->description . "</td>";
        $text=array();
        if ($database->suppliersku->fetch['category.name']) $text[]=$database->suppliersku->fetch['category.name'];
        if ($database->suppliersku->fetch['subcategory.name']) $text[]=$database->suppliersku->fetch['subcategory.name'];
	    print "<td>" . implode(": ",$text) . "</td>";
	    print "<td class=right>" . number_format($database->suppliersku->data->price,2) . "</td>";
	    print "<td>" . $database->suppliersku->data->unit_measure . "</td>";
	    print "<td class=right>" . number_format($database->suppliersku->data->cost,2) . "</td>";
	    if ($database->suppliersku->data->active) {
			$text="";
		  } else {
	        $text=$forms->radio("delete",$database->suppliersku->data->id,0,array("onclick"=>"fn_action(this,'delete'," . fn_escape($database->suppliersku->data->id) . "," . fn_escape($database->suppliersku->data->suppliersku) . ");"));
	    }
	    print "<td class=center>$text</td>";
	    print "</tr>";
	}
    print "</tbody>";
	$database->supplier->free_result();
	print "</table>";

    $page_break=new page_item_break($database->suppliersku->meta->found_rows);
    print $page_break->page($forms->report['page']);

}
$fields=array();
$fields['report_supplier_id']=$forms->report['report_supplier_id'];
$fields['report_category_subcategory']=$forms->report['report_category_subcategory'];
print $forms->hidden_fields($fields);
print $forms->close();
$menu->copyright();
?>