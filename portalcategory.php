<?php
require_once "scs_header.php";
require_once "classes/portal.php";
require_once "classes/portalcategory.php";
require_once "classes/portalcontent.php";
require_once "classes/subcategory.php";
$database->user->access("Administrator");
$forms->title("Customer Portal Category Manager");

$forms->report['action']=$_POST['action'];
$forms->report['record_id']=$_POST['record_id'];
$forms->report['report_portal_id']=$_POST['report_portal_id'];

if ($forms->report['record_id']) {
	$database->portalcategory->read($forms->report['record_id']);
  } else {
	$database->portalcategory->data=new portalcategory_data_class();
}
switch ($forms->report['action']) {
  case "":
    break;
  case "cancel":
    $forms->message[]="Request cancelled";
    unset($_SESSION['maintain_portalcategory']);
    break;
  case "delete":
    $database->portalcategory->delete($forms->report['record_id']);
    $query=
		"delete from portalitem \n" .
        "where portal_id=" . fn_escape($forms->report['record_id']);
	$database->temp->query($query);
	$query=
    	"delete from content \n" .
	    "where record_type='portalcategory' \n" .
        "and record_id=" . fn_escape($forms->report['record_id']);
	$database->temp->query($query);
    $forms->message[]="Portal Category " . $database->portalcategory->data->name . " deleted";
    break;
  case "maintain":
	if (!$forms->report['record_id']) {
		$database->portalcategory->data->portal_id=$forms->report['report_portal_id'];
    }
    $_SESSION['maintain_portalcategory']=array();
	$query=
	    "select * from content \n" .
	    "where record_type='portalcategory' \n" .
        "and record_id=" . fn_escape($forms->report['record_id']);
	$database->content->query($query);
    while ($database->content->fetch = $database->content->fetch_array() ) {
		$database->content->fetch();
        $_SESSION['maintain_portalcategory'][ $database->content->data->language_code ]=$database->content->data;
    }
	$database->content->free_result();
    break;
  case (!isset($_SESSION['maintain_portalcategory'])):
	$forms->message[]="Refresh not allowed";
 	break;
  case "update":
	foreach ($menu->language as $language) {
	    $field_name=base64_encode("name" . "\t" . $language->code);
	    $field_description=base64_encode("description" . "\t" . $language->code);
        if (array_key_exists($language->code,$_SESSION['maintain_portalcategory'])) {
        	$content=$_SESSION['maintain_portalcategory'][$language->code];
		  } else {
          	$content=new content_data_class($language->code);
		}
        $content->name=trim($_POST[$field_name]);
        $content->description=trim($_POST[$field_description]);
		if ($language->code == "EN") {
			$database->portalcategory->data->name=$content->name;
			$database->portalcategory->data->headline=$content->description;
            if (!$database->portalcategory->data->name) $forms->error($field_name);
		  } else {
			if ( (!$content->name) && ($content->description) ) $forms->error($field_description,"Name required when headline populated");
        }
		$_SESSION['maintain_portalcategory'][$language->code]=$content;
    }
	$database->portalcategory->data->portal_id=$forms->report['report_portal_id'];
	$database->portalcategory->data->output_order=$_POST['output_order'];
	$database->portalcategory->data->headline_portalcontent_id=$_POST['headline_portalcontent_id'];
	$database->portalcategory->data->image_portalcontent_id=$_POST['image_portalcontent_id'];
	$database->portalcategory->data->subcategory_id=$_POST['subcategory_id'];
	$database->portalcategory->data->active=$_POST['active'];
	if (sizeof($forms->error)) break;
    $database->portalcategory->update($database->portalcategory->meta->rows);
    if ($database->portalcategory->meta->error) {
    	$forms->error("name","",$database->portalcategory->meta->error);
        break;
	}
    foreach ($_SESSION['maintain_portalcategory'] as $language_code => $database->content->data) {
		switch (TRUE) {
		  case ($database->content->data->name):
			$database->content->data->record_type="portalcategory";
			$database->content->data->record_id=$database->portalcategory->data->id;
			$database->content->update($database->content->data->id);
          	break;
		  case ($database->content->data->id):
			$database->content->delete($database->content->data->id);
        }
    }
    unset($_SESSION['maintain_portalcategory']);
	$forms->message[]="Record maintained";
	break;
}
$menu->head();
print $forms->message();
?>
<script type='text/javascript'>
function fn_action(obj,action,id,name) {
	if (action == 'delete') {
	    if (!confirm('Delete? ' + name)) {
	        obj.checked=false;
            return;
	    }
	  } else {
		document.scs_form.report_portal_id.value=name;
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
print $forms->hidden('report_portal_id',$forms->report['report_portal_id']);
switch (TRUE) {
  case ($forms->report['action'] == "maintain"):
  case (($forms->report['action'] == "update") && (sizeof($forms->error))):
    $database->portal->read($forms->report['report_portal_id']);
	$menu->tabs=new jquery_tab_class();
	if (isset($_SESSION['scscpq_wp'])) $menu->tabs->meta->options->lineheight="auto";    
	foreach ($menu->language as $language) {
		$menu->tabs->item($language->name,document_tab($language));
	}
    $menu->tabs->output();
	print "<p class='standard'>\n";
	print $forms->button("Update",array("onclick"=>"fn_action(this,'update',"  . fn_escape($forms->report['record_id']) . "," . fn_escape($forms->report['report_portal_id']) . ");"));
	print $forms->button("Cancel",array("onclick"=>"fn_action(this,'cancel','','');"));
	print "</p>\n";
  	break;
  default:
	$results=array();
    $query =
        "select \n" .
        "subcategory.name as 'subcategory_name',\n" .
        "portalcategory.* from portalcategory \n" .
        "left join subcategory on subcategory.id=portalcategory.subcategory_id \n" .
        "order by portalcategory.portal_id,portalcategory.output_order,portalcategory.name";
	$database->portalcategory->query($query);
    while ($database->portalcategory->fetch = $database->portalcategory->fetch_array() ) {
        $database->portalcategory->fetch();
        $database->portalcategory->data->subcategory_name=$database->portalcategory->fetch['subcategory_name'];
        if (!array_key_exists($database->portalcategory->data->portal_id,$results)) $results[$database->portalcategory->data->portal_id]=array();
		$results[$database->portalcategory->data->portal_id][]=$database->portalcategory->data;
	}
	$database->portalcategory->free_result();
    print "<div class='accordion'>\n";
    $query =
        "select * from portal \n" .
        "order by name";
	$database->portal->query($query);
    while ($database->portal->fetch = $database->portal->fetch_array() ) {
        $database->portal->fetch();
        $contents=array();
	    $contents[]="<table class='standard border'>";
	    $contents[]="<tr>";
	    $contents[]="<th width='45%'>Category Name</th>";
	    $contents[]="<th width='45%'>Subcategory Name</th>";
	    $contents[]="<th width='10%'>Del?</th>";
	    $contents[]="</tr>";
	    $contents[]="<tr><td colspan=2>" . fn_href("Add New","javascript:fn_action(this,'maintain','0'," . fn_escape($database->portal->data->id) . ");") . "</td></tr>";
        if (array_key_exists($database->portal->data->id,$results)) {
	        foreach ($results[$database->portal->data->id] as $item) {
	            $contents[]="<tr>";
	            $contents[]="<td>" . fn_href($item->name,"javascript:fn_action(this,'maintain'," . fn_escape($item->id) . "," . fn_escape($database->portal->data->id) . ");") . "</td>\n";
	            $contents[]="<td>" . $item->subcategory_name . "</td>\n";
	            if ($item->active) {
	                $text="&nbsp;";
	              } else {
	                $text=$forms->radio("delete",$item->id,0,array("onclick"=>"fn_action(this,'delete'," . fn_escape($item->id) . "," . fn_escape($item->name) . ");"));
	            }
	            $contents[]="<td class=center>$text</td>";
	            $contents[]="</tr>";
	        }
		}
        $contents[]="</table>";
	    print "<h3>" . $database->portal->data->name . "</h3>\n";
		print "<div>" . implode("\n",$contents) . "</div>";
	}
	$database->portal->free_result();
    print "</div>\n";
}
print $forms->close();
$menu->copyright();
function document_tab($language) {
global $database, $forms;
	$field_name=base64_encode("name" . "\t" . $language->code);
	$field_description=base64_encode("description" . "\t" . $language->code);
	$results=array();
	$results[]="<table class='standard noborder'>";
    $results[]="<tr>";
    $results[]="<td width='30%'>Portal name</td>";
    $results[]="<td width='70%'><b>" . $database->portal->data->name . "</b></td>";
    $results[]="</tr>";
    $results[]="<tr>";
    $results[]="<td>Category name</td>";
    $results[]="<td>" . $forms->text($field_name,$_SESSION['maintain_portalcategory'][$language->code]->name,60) . "</td>";
    $results[]="</tr>";
    $results[]="<tr>";
    $results[]="<td>Headline</td>";
    $results[]="<td>" . $forms->textarea($field_description,$_SESSION['maintain_portalcategory'][$language->code]->description,4,60) . "</td>";
    $results[]="</tr>";
    if ($language->code == "EN") {
	    $results[]="<tr>";
	    $results[]="<td>Subcategory</td>";
	    $results[]="<td>" . $database->subcategory->select($database->portalcategory->data->subcategory_id) . "</td>";
	    $results[]="</tr>";
	    $results[]="<tr>";
	    $results[]="<td>Output order</td>";
	    $results[]="<td>" . $forms->select_number("output_order",$database->portalcategory->data->output_order,0,99) . "</td>";
	    $results[]="</tr>";
	    $options=array();
	    $options['portal_id']=$forms->report['report_portal_id'];
	    $options['form']="Category HTML";
	    $options['field']="headline_portalcontent_id";
	    $results[]="<tr>";
	    $results[]="<td>Category headline</td>";
		$results[]="<td>" . $database->portalcontent->select($database->portalcategory->data->headline_portalcontent_id,$options) . "</td>";
	    $results[]="</tr>";
	    $options=array();
	    $options['portal_id']=$forms->report['report_portal_id'];
	    $options['form']="Category Image";
	    $options['field']="image_portalcontent_id";
	    $results[]="<tr>";
	    $results[]="<td>Category image</td>";
        $results[]="<td>" . $database->portalcontent->select($database->portalcategory->data->image_portalcontent_id,$options) . "</td>";
		$results[]="</tr>";
	    $results[]="<tr>";
	    $results[]="<td>Active?</td>";
	    $results[]="<td>" . $forms->checkbox("active",1,$database->portalcategory->data->active) . "</td>";
	    $results[]="</tr>";
    }
    $results[]="</table>";
    return $results;
}
?>