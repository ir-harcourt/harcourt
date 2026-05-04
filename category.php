<?php
require_once "scs_header.php";
require_once "classes/category.php";
$database->user->access("Administrator");
$forms->title("Category Manager");
$forms->report['action']=$_POST['action'];
$forms->report['record_id']=$_POST['record_id'];
if ($forms->report['record_id']) {
	$database->category->read($forms->report['record_id']);
  } else {
    $database->category->data=new category_data_class();
}
switch (TRUE) {
  case (!$forms->report['action']):
    break;
  case ($forms->report['action'] == "cancel"):
	unset($_SESSION['maintain_category']);
    $forms->message[]="Request cancelled";
    break;
  case ($forms->report['action'] =="maintain"):
	$_SESSION['maintain_category']=array();
	$query=
	    "select * from content \n" .
	    "where record_type='category' \n" .
        "and record_id=" . fn_escape($forms->report['record_id']);
	$database->content->query($query);
    while ($database->content->fetch = $database->content->fetch_array() ) {
		$database->content->fetch();
        $_SESSION['maintain_category'][ $database->content->data->language_code ]=$database->content->data;
    }
	$database->content->free_result();

    break;
  case ($forms->report['action'] == "delete"):
    $database->category->delete($forms->report['record_id']);
    $forms->message[]="Category " . $database->category->data->name . " deleted";
    $query_where=array();
    $query_where[]=new query_where("record_type","=","category");
    $query_where[]=new query_where("record_id","=",$forms->report['record_id']);
    $query=array("delete from content");
    $query[]=$database->where($query_where);
	$database->temp->query($query);
    $query=array("delete from subcategory where category_id=" . fn_escape($forms->report['record_id']));
	$database->temp->query($query);
    break;
  case ($forms->report['action'] == "update"):
	foreach ($menu->language as $language_code => $language) {
		if (array_key_exists($language_code,$_SESSION['maintain_category'])) {
			$content=$_SESSION['maintain_category'][$language_code];
		  } else {
			$content=new content_data_class();
            $content->language_code=$language_code;
		}
		$field_name=base64_encode("name" . "\t" . $language_code);
		$field_description=base64_encode("description" . "\t" . $language_code);
		$content->name=trim($_POST[$field_name]);
		$content->description=trim($_POST[$field_description]);
		$_SESSION['maintain_category'][$language_code]=$content;
        switch (TRUE) {
          case ($language_code=="EN"):
            $database->category->data->name=$content->name;
			if (!$database->category->data->name) $forms->error($field_name);
            break;
		  case ( (!$content->name) && ($content->description) ):
			$forms->error($field_name,"Name cannot be blank when description provided");
            break;
        }
    }
	$database->category->data->sort_order=$_POST['sort_order'];
	$database->category->data->active=$_POST['active'];
	if (sizeof($forms->error)) break;
    $database->category->update($database->category->meta->rows);
    if ($database->category->meta->error) $forms->error('name',"",$database->category->meta->error);
	if (sizeof($forms->error)) break;
    foreach ($_SESSION['maintain_category'] as $database->content->data) {
        switch (TRUE) {
          case ($database->content->data->name):
            $database->content->data->record_type="category";
            $database->content->data->record_id=$database->category->data->id;
	        $database->content->data->revised=strtotime("now");
            $database->content->update($database->content->data->id);
            break;
          case ($database->content->data->id):
            $database->content->delete($database->content->data->id);
        }
    }
	unset($_SESSION['maintain_category']);
    $forms->message[]="Record maintained";
	break;
}
$menu->head();
print $forms->message();
?>
<script type='text/javascript'>
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
print $forms->open(array("data"=>TRUE));
print $forms->hidden("action");
print $forms->hidden("record_id");

switch (TRUE) {
  case ($forms->report['action'] == "maintain"):
  case (($forms->report['action'] == "update") && (sizeof($forms->error))):
	$menu->tabs=new jquery_tab_class();
	if (isset($_SESSION['scscpq_wp'])) $menu->tabs->meta->options->lineheight="auto";    
	foreach ($menu->language as $language) {
		$menu->tabs->item($language->name,document_tab($language));
	}
    $menu->tabs->output();
	print
    	"<p class='standard center'>" .
        $forms->button("Update",array("onclick"=>"fn_action(this,'update'," . fn_escape($database->category->data->id) . ",'');")) .
        " " . $forms->button("Cancel",array("onclick"=>"fn_action(this,'cancel','','');")) . "</p>\n";
  	break;
  default:
	print "<table class='standard border'>\n";
    print "<caption>&nbsp;</caption>\n";
    print "<tr>\n";
    print "<th>Name</th>\n";
    print "<th>Order</th>\n";
    print "<th>Description</th>\n";
    print "<th>Del?</th>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td colspan=4>" . fn_href("Add new","javascript:fn_action(this,'maintain','0','');") ."</td>\n";
    print "</tr>\n";
    $query =
        "select \n" .
        "content.description as 'content.description', \n" .
        "category.* from category \n" .
        "left join content on content.record_type='category' and content.record_id=category.id and content.record_item='' and content.language_code='en' \n" .
        "order by category.sort_order,category.name";
    $database->category->query($query);
    while ($database->category->fetch = $database->category->fetch_array()) {
	    $database->category->fetch();
	    print "<tr>\n";
	    print "<td>" . fn_href($database->category->data->id . ". " . $database->category->data->name,"javascript:fn_action(this,'maintain'," . fn_escape($database->category->data->id) . ",'');") . "</td>\n";
	    print "<td class=center>" . $database->category->data->sort_order . "</td>\n";
	    print "<td>" . $database->category->fetch['content.description'] . "</td>\n";
	    if ($database->category->data->active) {
			$text="";
		  } else {
	        $text=$forms->radio("delete",$database->category->data->id,0,array("onclick"=>"fn_action(this,'delete'," . fn_escape($database->category->data->id) . "," . fn_escape($database->category->data->name) . ");"));
	    }
	    print "<td class=center>$text</td>\n";
	    print "</tr>\n";
	}
	$database->category->free_result();
	print "</table>\n";
}
print $forms->close();
$menu->copyright();
function document_tab($language) {
global $database, $forms;
	$results=array();
    $content=$_SESSION['maintain_category'][$language->code] ;
    $field_name=base64_encode("name" . "\t" . $language->code);
    $field_description=base64_encode("description" . "\t" . $language->code);
	$results[]="<table class='large noborder'>";
    $results[]="<tr>";
    $results[]="<td width=30%>Name</td>";
    $results[]="<td width=70%>" . $forms->text($field_name,$content->name,60,60);
    if ($forms->error_text[$field_name]) $results[]="<br>" . $forms->error_text[$field_name];
    $results[]="</td>";
    $results[]="</tr>";
    $results[]="<tr>";
    $results[]="<td>HTML Text</td>";
    $results[]="<td>" . $forms->textarea($field_description,$content->description,6,60);
    if ($forms->error_text[$field_description]) $results[]="<br>" . $forms->error_text[$field_description];
    $results[]="</td>";
    $results[]="</tr>";
	if ($language->code=="EN") {
	    $results[]="<tr>";
	    $results[]="<td>Sort Order</td>";
	    $results[]="<td>" . $forms->select_number("sort_order",$database->category->data->sort_order,0,99) . "</td>";
	    $results[]="</tr>";
	    $results[]="<tr>";
	    $results[]="<td>Active?</td>";
	    $results[]="<td>" . $forms->checkbox("active",1,$database->category->data->active) . "</td>";
	    $results[]="</tr>";
    }
	$results[]="</table>";
    return $results;
}
?>