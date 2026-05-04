<?php
require_once "scs_header.php";
require_once "classes/portal.php";
require_once "classes/portalcategory.php";
require_once "classes/portalitem.php";
require_once "classes/portalcontent.php";
require_once "classes/subcategory.php";
$database->user->access("Administrator");
$forms->title("Portal Item Manager");

$forms->report['action']=$_POST['action'];
$forms->report['record_id']=$_POST['record_id'];
$database->portalitem->read($forms->report['record_id']);
$forms->report['report_portal_id']=$database->portalitem->data->portal_id;
switch ($forms->report['action']) {
  case "":
    break;
  case "cancel":
	unset($_SESSION['maintain_portalitem']);
    $forms->message[]="Request cancelled";
    break;
  case "delete":
    $database->portalitem->delete($forms->report['record_id']);
    $forms->message[]="Portal Item " . $database->portalitem->data->name . " deleted";
    break;
  case "maintain":
	if (!$database->portalitem->data->id) {
		$database->portalitem->data->portal_id=$forms->report['report_portal_id'];
    }
    $_SESSION['maintain_portalitem']=array();
	$query=
	    "select * from content \n" .
	    "where record_type='portalitem' \n" .
        "and record_id=" . fn_escape($forms->report['record_id']);
	$database->content->query($query);
    while ($database->content->fetch = $database->content->fetch_array() ) {
		$database->content->fetch();
        $_SESSION['maintain_portalitem'][ $database->content->data->language_code ]=$database->content->data;
    }
	$database->content->free_result();
    break;
  case (!isset($_SESSION['maintain_portalitem'])):
	$forms->message[]="Refresh not allowed";
 	break;
  case "update":
	foreach ($menu->language as $language) {
		$field_name=base64_encode("name" . "\t" . $language->code);
        if (array_key_exists($language->code,$_SESSION['maintain_portalitem'])) {
        	$content=$_SESSION['maintain_portalitem'][$language->code];
		  } else {
          	$content=new content_data_class($language->code);
		}
        $content->name=trim($_POST[$field_name]);
		if ($language->code=="EN") {
	        $database->portalitem->data->name=$content->name;
	        if (!$database->portalitem->data->name) $forms->error($field_name);
        }
		$_SESSION['maintain_portalitem'][$language->code]=$content;
	}
	$database->portalitem->data->portal_id=$_POST['report_portal_id'];
	$database->portalitem->data->portalcategory_id=$_POST['portalcategory_id'];
    if (!$database->portalitem->data->portalcategory_id) $forms->error('portalcategory_id');
	$database->portalitem->data->output_order=$_POST['output_order'];
	$database->portalitem->data->subcategory_id=$_POST['subcategory_id'];
	$database->portalitem->data->active=$_POST['active'];
    if ($database->portalitem->data->subcategory_id) {
	    $database->portalitem->data->pdf_portalcontent_id=0;
	    $database->portalitem->data->cad_portalcontent_id=0;
	    $database->portalitem->data->hyperlink_portalcontent_id=0;
	    $database->portalitem->data->other_portalcontent_id=0;
	    $database->portalitem->data->other_name="";
      } else {
	    $database->portalitem->data->pdf_portalcontent_id=$_POST['pdf_portalcontent_id'];
	    $database->portalitem->data->cad_portalcontent_id=$_POST['cad_portalcontent_id'];
	    $database->portalitem->data->hyperlink_portalcontent_id=$_POST['hyperlink_portalcontent_id'];
	    $database->portalitem->data->other_portalcontent_id=$_POST['other_portalcontent_id'];
	    $database->portalitem->data->other_name=trim($_POST['other_name']);
	    if (($database->portalitem->data->other_portalcontent_id) && (!$database->portalitem->data->other_name)) $forms->error('other_name');
	}
	if (sizeof($forms->error)) break;
    $database->portalitem->update($database->portalitem->meta->rows);
    if ($database->portalitem->meta->error) {
    	$forms->error("name","",$database->portalitem->meta->error);
        break;
	}
    foreach ($_SESSION['maintain_portalitem'] as $language_code => $database->content->data) {
		switch (TRUE) {
		  case ($database->content->data->name):
			$database->content->data->record_type="portalitem";
			$database->content->data->record_id=$database->portalitem->data->id;
			$database->content->update($database->content->data->id);
          	break;
		  case ($database->content->data->id):
			$database->content->delete($database->content->data->id);
        }
    }
	unset($_SESSION['maintain_portalitem']);
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
        "portalcategory.name as 'portalcategory_name', \n" .
        "subcategory.name as 'subcategory_name', \n" .
        "portalitem.* from portalitem \n" .
        "left join portalcategory on portalcategory.id=portalitem.portalcategory_id \n" .
        "left join subcategory on subcategory.id=portalitem.subcategory_id \n" .
        "order by portalitem.output_order,portalitem.name";
	$database->portalitem->query($query);
    while ($database->portalitem->fetch = $database->portalitem->fetch_array() ) {
        $database->portalitem->fetch();
        if (!array_key_exists($database->portalitem->data->portal_id,$results)) $results[$database->portalitem->data->portal_id]=array();
        if (!array_key_exists($database->portalitem->fetch['portalcategory_name'],$results[$database->portalitem->data->portal_id])) $results[$database->portalitem->data->portal_id][$database->portalitem->fetch['portalcategory_name']]=array();
        $database->portalitem->data->subcategory_name=$database->portalitem->fetch['subcategory_name'];
		$results[$database->portalitem->data->portal_id][$database->portalitem->fetch['portalcategory_name']][]=$database->portalitem->data;
	}
	$database->portalitem->free_result();
    print "<div class='accordion'>\n";
    $query =
        "select * from portal \n" .
        "order by name";
	$database->portal->query($query);
    while ($database->portal->fetch = $database->portal->fetch_array() ) {
        $database->portal->fetch();
	    print "<h3>" . $database->portal->data->name . "</h3>\n";
        print "<div>\n";
	    print "<table class='standard border'>\n";
	    print "<tr>\n";
	    print "<th>Item Name</th>\n";
	    print "<th>PDF</th>\n";
	    print "<th>CAD</th>\n";
	    print "<th>Subcategory</th>\n";
	    print "<th>Other</th>\n";
	    print "<th>Del?</th>\n";
	    print "</tr>\n";
	    print "<tr><td colspan=6>" . fn_href("Add New","javascript:fn_action(this,'maintain','0'," . fn_escape($database->portal->data->id) . ");") . "</td></tr>\n";
        if (array_key_exists($database->portal->data->id,$results)) {
	        foreach ($results[$database->portal->data->id] as $category_name => $category) {
	            print "<tr><td colspan=6><b>{$category_name}</b></td></tr>\n";
	            foreach ($category as $data) {
	                switch (TRUE) {
	                  case (!$data->active):
	                    $bg=$forms->background->yellow;
	                    break;
	                  case ($data->pdf_portalcontent_id):
	                  case ($data->cad_portalcontent_id):
	                  case ($data->subcategory_id):
	                  case ($data->other_portalcontent_id):
	                    $bg="";
	                    break;
	                  default:
	                    $bg=$forms->background->red;
	                }
	                print "<tr $bg>\n";
	                print "<td>" . fn_href($data->name,"javascript:fn_action(this,'maintain'," . fn_escape($data->id) . "," . fn_escape($database->portal->data->id) . ");") . "</td>\n";
	                print "<td>" . portalitem_view($data->pdf_portalcontent_id) . "</td>\n";
	                print "<td>" . portalitem_view($data->cad_portalcontent_id) . "</td>\n";
	                print "<td>{$data->subcategory_name}</td>\n";
	                print "<td>" . portalitem_view($data->other_portalcontent_id) . "</td>\n";
	                if (!$data->active) {
	                    $text=$forms->radio("delete",$data->id,0,array("onclick"=>"fn_action(this,'delete'," . fn_escape($data->id) . "," . fn_escape($data->name) . ");"));
	                  } else {
	                    $text="&nbsp;";
	                }
	                print "<td class=center>$text</td>\n";
	                print "</tr>\n";
	            }
			}
        }
	    print "</table>\n";

		print "</div>";
	}
    print "</div>\n";
	$database->portalitem->free_result();
}
print $forms->close();
$menu->copyright();
function portalitem_view($id) {
    global $database;
	$url_options=array('content_id'=>$database->content->encode_page("portalcontent",$id));
	if ($database->content->meta->rows) {
		return fn_href($database->content->data->name,fn_url("/scs_file.php",$url_options),array(),array("target"=>"portal_preview"));
	  } else {
		return "&nbsp;";
	}
}
function document_tab($language) {
    global $database, $forms;
	$field_name=base64_encode("name" . "\t" . $language->code);
	$results=array();
    $database->portal->read($forms->report['report_portal_id']);
	$results[]="<table class='standard noborder'>";
    $results[]="<tr>";
    $results[]="<td width='20%'>Portal name</td>";
    $results[]="<td width='80%'><b>" . $database->portal->data->name . "</b></td>";
    $results[]="</tr>";
    $results[]="<tr>";
    $results[]="<td>Name</td>";
    $results[]="<td>" . $forms->text($field_name,$_SESSION['maintain_portalitem'][$language->code]->name,40,40) . "</td>";
    $results[]="</tr>";
    if ($language->code == "EN") {
?>
<script type='text/javascript'>
function fn_subcategory() {
	if ($('#subcategory_id').val()) {
		$('#pdf_row').hide();
		$('#cad_row').hide();
		$('#other_row').hide();
	  } else {
		$('#pdf_row').show();
		$('#cad_row').show();
		$('#other_row').show();
    }
}
$(document).ready(function() {
	fn_subcategory();
});
</script>
<?php
	    $results[]="<tr>";
	    $results[]="<td width='20%'>Category</td>";
	    $results[]="<td width='80%'>" . $database->portalcategory->select($database->portalitem->data->portalcategory_id,$forms->report['report_portal_id'],array("subcategory_id"=>FALSE)) . "</td>";
	    $results[]="</tr>";
	    $results[]="<tr>";
	    $results[]="<td>Subcategory</td>";
		$results[]="<td>" . $database->subcategory->select($database->portalitem->data->subcategory_id,array(),array("onchange"=>"fn_subcategory();")) . "</td>";
	    $results[]="</tr>";
	    $results[]="<tr id=pdf_row>";
	    $results[]="<td>PDF</td>";
	    $options=array();
	    $options['portal_id']=$forms->report['report_portal_id'];
	    $options['form']="Link";
	    $options['field']="pdf_portalcontent_id";
		$results[]="<td>" . $database->portalcontent->select($database->portalitem->data->pdf_portalcontent_id, $options) . "</td>";
	    $results[]="</tr>";
	    $results[]="<tr id=cad_row>";
	    $results[]="<td>CAD</td>";
	    $options['form']="cad";
	    $options['form']="Link";
	    $options['field']="cad_portalcontent_id";
		$results[]="<td>" . $database->portalcontent->select($database->portalitem->data->cad_portalcontent_id, $options) . "</td>";
	    $results[]="</tr>";
	    $results[]="<tr>";
	    $results[]="<td>Hyperlink</td>";
	    $options['portal_id']=$forms->report['report_portal_id'];
	    $options['form']="Hyperlink";
	    $options['field']="hyperlink_portalcontent_id";
		$results[]="<td>" . $database->portalcontent->select($database->portalitem->data->hyperlink_portalcontent_id, $options) . "</td>";
	    $results[]="</tr>";
	    $results[]="<tr id=other_row>";
	    $results[]="<td>Other: " . $forms->text("other_name",$database->portalitem->data->other_name,15,30) . "</td>";
	    $options['field']="other_portalcontent_id";
	    $options['form']="Link";
		$results[]="<td>" . $database->portalcontent->select($database->portalitem->data->other_portalcontent_id,$options) . "</td>";
	    $results[]="</tr>";
	    $results[]="<tr>";
	    $results[]="<td>Output order</td>";
	    $results[]="<td>" . $forms->select_number("output_order",$database->portalitem->data->output_order,0,99) . "</td>";
	    $results[]="</tr>";
	    $results[]="<tr>";
	    $results[]="<td>Active?</td>";
	    $results[]="<td>" . $forms->checkbox("active",1,$database->portalitem->data->active) . "</td>";
	    $results[]="</tr>";
	}
    $results[]="</table>";
	return $results;
}
?>