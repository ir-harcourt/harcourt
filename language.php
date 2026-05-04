<?php
require_once "scs_header.php";
require_once "classes/language.php";
$database->user->access("Administrator");
$forms->title("Language Manager");
/*
https://translate.google.com/
*/
$forms->report['action']=$_POST['action'];
$forms->report['record_id']=$_POST['record_id'];
if ($forms->report['record_id']) {
	$database->language->read($forms->report['record_id']);
  } else {
	$database->language->data=new language_data_class();
}
$document=array();
switch ($forms->report['action']) {
  case "":
    break;
  case "cancel":
    $forms->message[]="Request cancelled";
    break;
  case "maintain":
    break;
  case "delete":
    $database->language->delete($forms->report['record_id']);
    $forms->message[]=$database->language->data->name . " deleted";
    $query="select * from content where language_code=" . fn_escape($database->language->data->code);
    $database->temp->query($query);
    break;
  case "update":
	$database->language->data->code=fn_text($_POST['code'],TRUE);
	if (!$database->language->data->code) $forms->error('code');
	$database->language->data->country_code=fn_text($_POST['country_code'],TRUE);
	$database->language->data->name=fn_text($_POST['name']);
	if (!$database->language->data->name) $forms->error('name');
	$database->language->data->local_name=fn_text($_POST['local_name']);
	if (!$database->language->data->local_name) $forms->error('local_name');
	$database->language->data->ps_name=fn_text($_POST['ps_name']);
	$database->language->data->priority=$_POST['priority'];
    if ($_FILES['image_url']['tmp_name']) {
    	move_uploaded_file($_FILES['image_url']['tmp_name'],'scs.tmp');
        $image=getimagesize('scs.tmp');
	}
    switch (TRUE) {
	  case (!$_FILES['image_url']['tmp_name']):
		break;
	  case (intval($_FILES['image_url']['error'])):
		$forms->error('image_url',"Error #" . $_FILES['image_url']['error']);
		break;
	  case (!is_array($image)):
		$forms->error('image_url',"Not a valid image");
		break;
	  case ($image['mime'] != "image/jpeg"):
		$forms->error('image_url',"Image size must be .jpg");
		break;
	  default:
		if ($database->language->data->image_url) unlink($_SERVER['DOCUMENT_ROOT'] . $database->language->data->image_url);
		$database->language->data->image_url="/scs_images/language_" . $database->language->data->id . ".jpg";
        rename('scs.tmp',$_SERVER['DOCUMENT_ROOT'] . $database->language->data->image_url);
	}
	if (file_exists('scs.tmp')) unlink('scs.tmp');
	$database->language->data->active=$_POST['active'];
	$database->language->data->menu=array();
    foreach ($database->language->constant->menu as $menu_code => $menu_name) {
		$field=base64_encode("menu" . "\t" . $menu_code);
		$database->language->data->menu[$menu_code]=fn_text($_POST[$field]);
		if (!$database->language->data->menu[$menu_code]) $forms->error($field);
	}
	if (sizeof($forms->error)) break;
    $database->language->update($forms->report['record_id']);
	switch (TRUE) {
      case (!$database->language->meta->error):
      	break;
      case (preg_match("/code_key/i",$database->language->meta->error)):
		$forms->error('code','',$database->language->meta->error);
		break;
      default:
		$forms->error('name','',$database->language->meta->error);
	}
	if (sizeof($forms->error)) break;

    if (!$forms->report['record_id']) {
        $image_url=$database->language->data->image_url;
        $database->language->data->image_url="/scs_images/language_" . $database->language->data->id . ".jpg";
        unlink($database->language->data->image_url);
        rename($_SERVER['DOCUMENT_ROOT'] . $image_url,$_SERVER['DOCUMENT_ROOT'] . $database->language->data->image_url);
        $database->language->update(TRUE);
    }
    $forms->message[]="Record maintained";
	break;
}
$menu->head();
print $forms->message();
?>
<script>
function fn_action(obj,action,id,name) {
	if ( (action=='delete') && (!confirm('Delete: #' + id + ' ' + name)) ) {
	    obj.checked=false;
	    return;
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
switch (TRUE) {
  case ($forms->report['action'] == "maintain"):
  case (($forms->report['action'] == "update") && (sizeof($forms->error))):
	print $forms->hidden("report_status",$forms->report['report_status']);
	print $forms->hidden("report_type",$forms->report['report_type']);
	print $forms->hidden("report_company_name",$forms->report['report_company_name']);
	$menu->tabs=new jquery_tab_class();
	if (isset($_SESSION['scscpq_wp'])) $menu->tabs->meta->options->lineheight="auto";
    $menu->tabs->item("Status",status_tab());
    $menu->tabs->item("Menu",program_tab("menu"));
    $menu->tabs->item("Catalog",program_tab("catalog"));
    $menu->tabs->item("Cart",program_tab("cart"));
    $menu->tabs->output();
    $buttons=array();
	$buttons[]=$forms->button("Update",array("onclick"=>"fn_action(this,'update',"  . fn_escape($forms->report['record_id']) . ",'');"));
	$buttons[]=$forms->button("Cancel",array("onclick"=>"fn_action(this,'cancel','','');"));
	print "<p>" . implode(" ",$buttons) . "</p>";
  	break;
  default:
	print "<table class='standard border'>";
    print "<tr>";
    print "<th>Code</th>";
    print "<th>Flag</th>";
    print "<th>Name</th>";
    print "<th>Local Language</th>";
    print "<th>Priority</th>";
    print "<th>Del?</th>";
    print "</tr>";
    print "<tr>";
    print "<td colspan=6>" . fn_href("Add new","javascript:fn_action(this,'maintain',0,'');") . "</td>";
    print "</tr>";
    foreach ($menu->language as $language_code => $obj) {
        print "<tr>";
        $text=$obj->code;
        if ($obj->country_code) $text .= "-" . $obj->country_code;
        print "<td class=center>" . fn_href($text,"javascript:fn_action(this,'maintain'," . fn_escape($obj->id) . ",'');") . "</td>";
        print "<td class=center>" . $forms->img($obj->image_url) . "</td>";
        $text=$obj->name;
        if ($database->language->fetch['country.name']) $text .= " (" . $database->language->fetch['country.name'] . ")";
        print "<td>$text</td>";
        print "<td>" . $obj->local_name . "</td>";
        print "<td class=center>" . $obj->priority . "</td>";
        if ($obj->active) {
            $text="&nbsp;";
          } else {
            $text=$forms->radio("delete",$obj->id,0,array("onclick"=>"fn_action(this,'delete'," . fn_escape($obj->id) . "," . fn_escape($obj->name) . ");"));
        }
        print "<td class=center>" . $text . "</td>";
        print "</td>";
        print "</tr>";
	}
    print "</table>";
	$database->language->free_result();
}
print $forms->close();
$menu->copyright();
function status_tab() {
    global $database, $forms, $menu;
	$results=array();
	$results[]="<table class='standard noborder'>";
    $results[]="<tr>";
    $results[]="<td width=30%>Code (ISO 639-1)</td>";
    $results[]="<td width=70%>" . $forms->text("code",$database->language->data->code,2,2) . "</td>";
    $results[]="</tr>";
    $results[]="<tr>";
    $results[]="<td>Name</td>";
    $results[]="<td>" . $forms->text("name",$database->language->data->name,45,45) . "</td>";
    $results[]="</tr>";
    $results[]="<tr>";
    $results[]="<td>Local Language Name</td>";
	$results[]="<td>" . $forms->text("local_name",$database->language->data->local_name,45,45) . "</td>";
    $results[]="</tr>";
    $results[]="<tr>";
    $results[]="<td>PARTsolutions code</td>";
	$results[]="<td>" . $forms->text("ps_name",$database->language->data->ps_name,45,45) . "</td>";
    $results[]="</tr>";
    $results[]="<tr>";
    $results[]="<td>Priority</td>";
    $results[]="<td>" . $forms->select_number("priority",$database->language->data->priority,0,9) . "</td>";
    $results[]="</tr>";
    $results[]="<tr>";
    if ($database->language->data->image_url) {
    	$text="Replace image";
	  } else {
      	$text="Add image";
	}
    $results[]="<td>$text</td>";
    $results[]="</td>";
    $results[]="<td>" . $forms->img($database->language->data->image_url) . " " . $forms->file("image_url",40);
    if ($forms->error_text['image_url']) $results[]=" <br>" . $forms->error_text['image_url'];
    $results[]="</td>";
    $results[]="<tr>";
    $results[]="<td>Active?</td>";
    $results[]="<td>" . $forms->checkbox("active",1,$database->language->data->active) . "</td>";
    $results[]="</tr>";
    $results[]="</table>";
    return $results;
}
function program_tab($type) {
    global $database, $forms, $menu;
	$results=array();
	$results[]="<table class='standard noborder'>";
    foreach ($database->language->constant->menu as $menu_code => $menu_name) {
		if (preg_match("/^" . $type . "/",$menu_code)) {
	        $results[]="<tr>";
	        $results[]="<td width='30%'>" . $menu_name . "</td>";
	        if (array_key_exists($menu_code,$database->language->data->menu)) {
	            $text=$database->language->data->menu[$menu_code];
	          } else {
	            $text=$menu_name;
	        }
	        $results[]="<td>" . $forms->text(base64_encode("menu" . "\t" . $menu_code),$text,30,30) . "</td>";
	        $results[]="</tr>";
		}
    }
    $results[]="</table>";
    return $results;
}
?>