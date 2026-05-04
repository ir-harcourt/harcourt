<?php
require_once "scs_header.php";
require_once "classes/category.php";
require_once "classes/subcategory.php";
require_once "classes/inventory.php";
$database->user->access("Administrator");
$forms->title("Inventory Manager");
$forms->report['action']=$_POST['action'];
$forms->report['page']=$_POST['page'];
$forms->report['report_subcategory_id']=$_POST['report_subcategory_id'];
list($forms->report['ajax_inventory_sku'],$null)=preg_split("/\|/",strtoupper(trim($_POST['ajax_inventory_sku'])),2);
if ($forms->report['ajax_inventory_sku']) {
	$database->inventory->read($forms->report['ajax_inventory_sku'],"sku");
    if ($database->inventory->meta->rows) {
		$forms->report['action']="maintain";
		$forms->report['record_id']=$database->inventory->data->id;
		$forms->report['report_subcategory_id']==$database->inventory->data->subcategory_id;
	  } else {
      	$forms->error('ajax_inventory_sku');
    }
  } else {
	$forms->report['record_id']=$_POST['record_id'];
}
$content=array();
if ($forms->report['record_id']) {
	$database->inventory->read($forms->report['record_id']);
  } else {
   	$database->inventory->data=new inventory_data_class();
}
switch (TRUE) {
  case (!$forms->report['action']):
  case ($forms->report['action'] == "list"):
    break;
  case ($forms->report['action'] =="maintain"):
    if (!$database->inventory->data->id) {
        $database->inventory->data->subcategory_id=$forms->report['report_subcategory_id'];
    }
    $_SESSION['maintain_inventory']=array();
	$_SESSION['maintain_inventory']['EN']=array();
    $query=
        "select * from content \n" .
        "where record_type='inventory' \n" .
        "and record_id=" . fn_escape($forms->report['record_id']);
    $database->content->query($query);
    while ($database->content->fetch = $database->content->fetch_array() ) {
        $database->content->fetch();
        if (!array_key_exists($database->content->data->language_code,$_SESSION['maintain_inventory'])) $_SESSION['maintain_inventory'][ $database->content->data->language_code ]=array();
		$_SESSION['maintain_inventory'][ $database->content->data->language_code ][ $database->content->data->record_item ]=$database->content->data;
    }
    break;
  case ($forms->report['action'] == "delete"):
    $database->inventory->delete($forms->report['record_id']);
    $query_where=array();
    $query_where[]=new query_where("record_type","=","inventory");
    $query_where[]=new query_where("record_id","=",$forms->report['record_id']);
    $query=
    	"delete from content \n" .
        $database->where($query_where);
	$database->temp->query($query);
    $forms->message[]="SKU " . $database->inventory->data->sku . " deleted";
    break;
  case ($forms->report['action'] == "cancel"):
	unset($_SESSION['maintain_inventory']);
    $forms->message[]="Request cancelled";
    break;
  case (!isset($_SESSION['maintain_inventory'])):
    $forms->message[]="Refresh not allowed";
	break;
  case ($forms->report['action'] == "subcategory"):
  case ($forms->report['action'] == "update"):
	$database->inventory->data->subcategory_id=$_POST['subcategory_id'];
    if (!$database->inventory->data->subcategory_id) $forms->error('subcategory_id');
	$database->subcategory->read($database->inventory->data->subcategory_id);
	$database->inventory->data->category_id=$database->subcategory->data->category_id;
	$database->inventory->data->sku=fn_text($_POST['sku'],TRUE);
	$database->inventory->data->sort_order=fn_number($_POST['sort_order']);
	$database->inventory->data->new_date=fn_date($_POST['new_date'],"mdy");
    if ( ($database->inventory->data->new_date) && ($database->inventory->data->new_date > date("Y/m/d",strtotime("now"))) ) $forms->error('new_date');
    $database->inventory->data->leadtime_option=$_POST['leadtime_option'];
    if ($database->inventory->data->leadtime_option == "Weeks") {
    	$database->inventory->data->leadtime_text=trim($_POST['leadtime_text']);
        if (!strlen($database->inventory->data->leadtime_text)) $forms->error("leadtime_text");
	  } else {
    	$database->inventory->data->leadtime_text="";
	}
	$database->inventory->data->active=$_POST['active'];
    $errors=$database->inventory->competitor_sku($_POST['competitor_sku'], FALSE);
    if (strlen($errors)) $forms->error("competitor_sku", $errors);
	$database->inventory->data->pricing=array();
	for ($i=0; $i < 10; $i++) {
		$field_qty_break=base64_encode("qty_break" . "\t" . $i);
        $field_price=base64_encode("price" . "\t" . $i);
        $price=fn_number($_POST[$field_price],2);
		if ($price) {
    		if (sizeof($database->inventory->data->pricing)) {
				$qty_break=fn_number($_POST[$field_qty_break],0);
                if ($qty_break <= $last_qty_break) $forms->error($field_qty_break);
                if ($price > $last_price) $forms->error($field_price);
			  } else {
              	$qty_break=0;
        	}
        	$database->inventory->data->pricing[]=new inventory_pricing_class($qty_break,$price);
            $last_qty_break=$qty_break;
            $last_price=$price;
		}
    }
    $database->inventory->data->documents=array();
    foreach ($menu->language as $language) {
		if (!array_key_exists($language->code,$_SESSION['maintain_inventory'])) $_SESSION['maintain_inventory'][$language->code]=array();
		foreach ($database->inventory->constant->documents as  $document => $document_name) {
	        $field_description=base64_encode($document . "\t" . "description" . "\t" . $language->code);
	        $field_document_id=base64_encode($document . "\t" . "document_id");
			if (!array_key_exists($document,$_SESSION['maintain_inventory'][$language->code])) {
            	$content=new content_data_class();
				$content->language_code=$language->code;
			  } else {
				$content=$_SESSION['maintain_inventory'][$language->code][$document];
			}
			$content->description=trim($_POST[$field_description]);
            if ($language->code == "EN") $database->inventory->data->documents[$document]=$_POST[$field_document_id];
			$_SESSION['maintain_inventory'][$language->code][$document]=$content;
		}
    }
    $database->inventory->data->parameter=array();
    $database->inventory->data->nparameter=array();
	if ($forms->report['action'] == "subcategory") break;
    if (!$database->inventory->data->sku) $forms->error('sku');
    if (sizeof($database->subcategory->data->parameters)) {
        foreach ($database->subcategory->data->parameters as $parameter) {
			$database->inventory->data->parameter[$parameter->id]=trim($_POST[base64_encode("parameter" . "\t" . $parameter->id)]);
			if ($parameter->numeric) $database->inventory->data->nparameter[$parameter->id]=$parameter->output(trim($_POST[base64_encode("nparameter" . "\t" . $parameter->id)]));
        }
    }
	for ($i=0; $i < $database->subcategory->constant->download_count; $i++) {
		$field_name=base64_encode("download" . "\t" . "name" . "\t" . $i);
		$field_description=base64_encode("download" . "\t" . "description" . "\t" . $i);
		$field_file=base64_encode("download" . "\t" . "file" . "\t" . $i);
		$field_delete=base64_encode("download" . "\t" . "delete" . "\t" . $i);
        $record_item="download:{$i}";
        if (array_key_exists($record_item,$_SESSION['maintain_inventory']['EN'])) {
            $content=$_SESSION['maintain_inventory']['EN'][$record_item];
          } else {
            $content=new content_data_class("EN");
        }
        $content->record_item=$record_item;
		$content->name=$database->subcategory->data->download[$i];
        switch (TRUE) {
		  case (!$database->subcategory->data->download[$i]):
          case ($_POST[$field_delete]):
			$content->name="";
			break;
          case (!$_FILES[$field_file]['name']):
            break;
          case (!intval($_FILES[$field_file]['size'])):
            $forms->error($field_file,"Empty file");
            break;
          case ($_FILES[$field_file]['error']):
            $forms->error($field_file,"Error: " . $_FILES[$field_file]["error"]);
            break;
          default:
            $tempname=tmpfile();
            if (file_exists("scs.tmp")) unlink("scs.tmp");
            move_uploaded_file($_FILES[$field_file]['tmp_name'],"scs.tmp");
            $content->content=file_get_contents("scs.tmp");
            unlink("scs.tmp");
            $database->content->fileinfo($content,$_FILES[$field_file]['name']);
            if (!$content->meta->mime) $forms->error($field_file,"File type not supported");
        }
        $_SESSION['maintain_inventory']['EN'][$record_item]=$content;
	}
	if (sizeof($forms->error)) break;
	$database->inventory->update($database->inventory->meta->rows);
	if ($database->inventory->meta->error) $forms->error('sku',"",$database->inventory->meta->error);
	if (sizeof($forms->error)) break;
    foreach ($_SESSION['maintain_inventory'] as $language_item) {
		foreach ($language_item as $record_item => $database->content->data) {
		  switch (TRUE) {
	          case ( ($database->content->data->name) || ($database->content->data->description) ):
	            $database->content->data->record_type="inventory";
	            $database->content->data->record_id=$database->inventory->data->id;
	            $database->content->data->record_item=$record_item;
	            $database->content->data->revised=strtotime("now");
	            $database->content->update($database->content->data->id);
	            break;
	          case ($database->content->data->id):
	            $database->content->delete($database->content->data->id);
                break;
	        }
	    }
	}
	$forms->message[]="Record maintained";
	unset($_SESSION['maintain_inventory']);
	break;
}
$menu->head();
print $forms->message();
?>
<script>
jQuery(document).ready(function() {
    fn_leadtime_option();
});
function fn_leadtime_option() {
    if (jQuery("#leadtime_option").val() == "Weeks") {
    	jQuery("#leadtime_text_span").show();
	  } else {
    	jQuery("#leadtime_text_span").hide();
	}
}
function fn_action(obj,action,id,name) {
	if (action=='delete') {
	    if (!confirm("Delete: SKU# " + name)) {
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
print $forms->open(array("data"=>TRUE));
print $forms->hidden("action");
print $forms->hidden("record_id");
print $forms->hidden("page");
switch (TRUE) {
  case ($forms->report['action'] == "maintain"):
  case ($forms->report['action'] == "subcategory"):
  case (($forms->report['action'] == "update") && (sizeof($forms->error))):
	print $forms->hidden("report_subcategory_id",$forms->report['report_subcategory_id']);
	$menu->tabs=new jquery_tab_class();
	if (isset($_SESSION['scscpq_wp'])) $menu->tabs->meta->options->lineheight="auto";
    $menu->tabs->item("SKU",sku_tab());
    $menu->tabs->item("Pricing",pricing_tab());
    $menu->tabs->item("Competitors",competitor_sku_tab());
	foreach ($menu->language as $language) {
		$menu->tabs->item($language->name,language_tab($language));
	}
    $menu->tabs->item("Download",download_tab());
    $menu->tabs->output();
    $text=array();
    $text[]=$forms->button("Update",array("onclick"=>"fn_action(this,'update'," . fn_escape($database->inventory->data->id) . ",'');"));
    $text[]=$forms->button("Cancel",array("onclick"=>"fn_action(this,'cancel','','');"));
	print "<p class='standard'>" . implode(" ",$text) .  "</p>";
  	break;
  default:
	print "<table class='large noborder'>";
    print "<tr>";
    print "<td width='30%'>SKU</td>";
    $text=array();
    $text[]=$forms->text("ajax_inventory_sku",$forms->report['ajax_inventory_sku'],40,40);
    $text[]=$forms->button("Go!",array("onclick"=>"fn_action(this,'list','','');"));
    print "<td width='70%'>" . implode(" ",$text) . "</td>";
    print "</tr>";
    print "<tr>";
    print "<td>Category/Subcategory</td>";
    print "<td>" . $database->subcategory->select($forms->report['report_subcategory_id'],array(),array("name"=>"report_subcategory_id","onchange"=>"fn_action(this,'list','','');")) . "</td>";
    print "</tr>";
    print "</table>";
    if (!$forms->report['report_subcategory_id']) break;
    $database->subcategory->read($forms->report['report_subcategory_id']);
    $colspan=2 + sizeof($database->subcategory->data->parameters);
	print "<table class='small border'>";
    print $forms->caption();
    print "<tr>";
    print "<th>SKU</th>";
    if (sizeof($database->subcategory->data->parameters)) {
		foreach ($database->subcategory->data->parameters as $parameter_id => $parameter) {
        	print "<th>" . $parameter->name . "</th>";
        }
    }
    print "<th>Del?</th>";
    print "</tr>";
    print "<tr>";
    print "<td colspan=$colspan>" . fn_href("Add new","javascript:fn_action(this,'maintain','0','');") . "</td>";
    print "</tr>";
    $query_where=array();
    if ($forms->report['report_subcategory_id']) $query_where[] = new query_where("inventory.subcategory_id","=",$forms->report['report_subcategory_id']);
    $query =
        "select \n" .
        "inventory.* from inventory \n" .
        $database->where($query_where) .
        "order by inventory.sort_order,inventory.sku";
    $database->inventory->query($query);
    while ($database->inventory->fetch = $database->inventory->fetch_array() ) {
        $database->inventory->fetch();
        print "<tr>";
        print "<td>" . fn_href($database->inventory->data->sku ,"javascript:fn_action(this,'maintain'," . fn_escape($database->inventory->data->id) . ",'');") . "</td>";
	    if (sizeof($database->subcategory->data->parameters)) {
	        foreach ($database->subcategory->data->parameters as $parameter_id => $parameter) {
	            print "<td>" . $database->inventory->data->parameter[$parameter_id] . "</td>";
	        }
	    }
        print "<td class=center>";
        if (!$database->inventory->data->active) {
        	print $forms->radio("delete",$database->inventory->data->id,0,array("onclick"=>"fn_action(this,'delete'," . fn_escape($database->inventory->data->id) . "," . fn_escape($database->inventory->data->sku) . ");"));
        }
        print "</td>";
        print "</tr>";
    }
    print "</table>";
	$database->inventory->free_result();
}
print $forms->close();
$menu->copyright();
function sku_tab() {
global $forms, $database;
	$database->subcategory2=new subcategory_class();
	$database->subcategory->read($database->inventory->data->subcategory_id);
	$database->category->read($database->subcategory->data->category_id);
	$results=array();
	$results[]="<table class='standard noborder'>";
    $results[]="<tr>";
    $results[]="<td width=30%>Category/Subcategory</td>";
    $results[]="<td width=70%>" . $database->subcategory2->select($database->inventory->data->subcategory_id,array(),array("onchange"=>"fn_action(this,'subcategory'," . fn_escape($_POST['record_id']) . ",'');")) . "</td>";
    $results[]="</tr>";
    $results[]="<tr>";
    $results[]="<td>SKU</td>";
    $results[]="<td>" . $forms->text("sku",$database->inventory->data->sku,40,40) . "</td>";
    $results[]="</tr>";
    $results[]="<tr>";
    $results[]="<td>Sort Order (0-999999)</td>";
    $results[]="<td>" . $forms->text("sort_order",$database->inventory->data->sort_order,6,6) . "</td>";
    $results[]="</tr>";
    $results[]="<tr>";
    $results[]="<td>New Date</td>";
    $results[]="<td>" . $forms->text("new_date",$database->inventory->data->new_date,10,10,"date:ymd") . "</td>";
    $results[]="</tr>";
    $results[]="<tr>";
    $results[]="<td>Lead Time</td>";
    $text=array();
    $text[]=$forms->select("leadtime_option",$database->inventory->constant->leadtime_option,$database->inventory->data->leadtime_option,FALSE,array("onchange"=>"fn_leadtime_option();"));
    $text[]=$forms->span($forms->text("leadtime_text",$database->inventory->data->leadtime_text,8,8),array("id"=>"leadtime_text_span"));
    $results[]="<td>" . implode(" ",$text) . "</td>";
    $results[]="</tr>";
    $results[]="<tr>";
    $results[]="<td>Active?</td>";
    $results[]="<td>" . $forms->checkbox("active",1,$database->inventory->data->active) . "</td>";
    $results[]="</tr>";
    $results[]="</table>";
    if (sizeof($database->subcategory->data->parameters)) {
	    $results[]="<table class='standard noborder'>";
	    $results[]=$forms->caption($database->category->data->name . ": " . $database->subcategory->data->name,array("class"=>"left"));
	    $results[]="<tr>";
	    $results[]="<td width='30%'>Parameter</td>";
	    $results[]="<td width='40%'>Alpha value</td>";
	    $results[]="<td width='30%'>Numeric value</td>";
	    $results[]="</tr>";
        foreach ($database->subcategory->data->parameters as $parameter) {
        	$results[]="<tr>";
            $results[]="<td>" . $parameter->name . "</td>";
            $results[]="<td>" . $forms->text(base64_encode("parameter" . "\t" . $parameter->id),$database->inventory->data->parameter[$parameter->id],40,80) . "</td>";
            if ($parameter->numeric) {
            	$text=$forms->text(base64_encode("nparameter" . "\t" . $parameter->id),$database->inventory->data->nparameter[$parameter->id],20,20,"numeric");
              } else {
              	$text="&nbsp;";
            }
			$results[]="<td>$text</td>";
            $results[]="</tr>";
        }
    	$results[]="</table>";
    }
	return $results;
}
function pricing_tab() {
global $forms, $database;
	$results=array();
	$results[]="<table class='standard border'>";
    $results[]="<tr>";
    $results[]="<th>Break Point</th>";
    $results[]="<th>Quantity</th>";
    $results[]="<th>Price</th>";
    $results[]="</tr>";
	for ($i=0; $i < 10; $i++) {
	    $results[]="<tr>";
	    $results[]="<td class=center>" . (($i) ? "{$i}." : " Base Price") . "</td>";
	    $results[]="<td class=center>" . (($i) ? $forms->text(base64_encode("qty_break" . "\t" . $i),$database->inventory->data->pricing[$i]->qty_break,10,10,"decimal:0") : "") . "</td>";
	    $results[]="<td class=center>$" . $forms->text(base64_encode("price" . "\t" . $i),$database->inventory->data->pricing[$i]->price,10,10,"decimal:4") . "</td>";
	    $results[]="</tr>";
        next($database->inventory->data->pricing);
	}
    $results[]="</table>";
	return $results;
}
function competitor_sku_tab() {
   global $database, $forms;
   $results=array();
   $results[]="<p>Enter each SKU on a separate line.<br>SKU must be at least 3 characters long and not contain a comma</p>";
   if ($forms->error_text['competitor_sku']) $results[]="<p>" . $forms->error_text['competitor_sku'] . "</p>";
   $results[]=$forms->textarea("competitor_sku", $database->inventory->data->competitor_sku, 10, 40);
   return $results;
}
function language_tab($language) {
global $forms, $database;
	$results=array();
	$results[]="<table class='standard noborder'>";
    foreach ($database->inventory->constant->documents as $document => $document_name) {
	    $field_description=base64_encode($document . "\t" . "description" . "\t" . $language->code);
	    $field_document_id=base64_encode($document . "\t" . "document_id");
        $results[]="<tr><td colspan=2><br><b>$document_name</b></td></tr>";
        if ($language->code == "EN") {
            $results[]="<tr>";
            $results[]="<td width='30%'>Document ID</td>";
			$results[]="<td width='70%'>" . $database->document->select($database->inventory->data->documents[$document],array("field"=>$field_document_id)) . "</td>";
            $results[]="</tr>";
        }
        $results[]="<tr>";
        $results[]="<td width='30%'>Description</td>";
		$results[]="<td width='70%'>" . $forms->textarea($field_description,$_SESSION['maintain_inventory'][ $language->code ][$document]->description,6,60) . "</td>";
        $results[]="</tr>";
	}
    $results[]="</table>";
	return $results;
}
function download_tab() {
global $forms, $database;
	if (!sizeof($database->subcategory->data->download)) return;
	$results=array();
    $results[]="<table class='standard noborder'>";
	for ($i=0; $i < $database->subcategory->constant->download_count; $i++) {
		if (!$database->subcategory->data->download[$i]) continue;
		$field_name=base64_encode("download" . "\t" . "name" . "\t" . $i);
		$field_description=base64_encode("download" . "\t" . "description" . "\t" . $i);
		$field_delete=base64_encode("download" . "\t" . "delete" . "\t" . $i);
		$field_file=base64_encode("download" . "\t" . "file" . "\t" . $i);
        $record_item="download:{$i}";
        if (array_key_exists($record_item,$_SESSION['maintain_inventory']['EN'])) {
            $content=$_SESSION['maintain_inventory']['EN'][$record_item];
          } else {
            $content=new content_data_class();
        }
        $text=array();
        $text[]=$forms->file($field_file,40);
        if (array_key_exists($field_file,$forms->error_text)) $text[]=$forms->error->text[$field_file];
        if ($content->name) {
            $text[]="Name: " .  $content->meta->name;
            $text[]="File Type: " .  $content->filetype;
            $text[]="Mime: " .  $content->meta->mime;
            $text[]="Revised: " .  date("m/d/Y h:iA",$content->revised);
        	$text[]="Delete? "  . $forms->checkbox($field_delete,1,0);
        }
		$results[]="<tr>";
        $results[]="<td width='40%'>" . ($i +1) . ". " . $database->subcategory->data->download[$i] . "</td>";
        $results[]="<td width='60%'>" . implode("<br>",$text) . "</td>";
		$results[]="</tr>";
	}
    $results[]="</table>";
    return $results;
}
?>