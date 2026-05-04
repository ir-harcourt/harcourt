<?php
include_once "/Webpages/Header_Footer/scs_header.php";
include_once "classes/category.php";
include_once "classes/document.php";
$menu->access();
$menu->title="Category Manager";
$database->connect();
$menu->messages[]=$menu->title;
$report['action']=$_POST['action'];
$report['record_id']=$_POST['record_id'];
if ($report['record_id']) $database->category->read($report['record_id']);

switch (TRUE) {
  case ($report['action'] == ""):
    break;
  case ($report['action'] =="maintain"):
    if (!$database->category->data->id) {
    	$database->category->data=new category_data_class();
    }
    $_SESSION['category']=TRUE;
    break;
  case ($report['action'] == "delete"):
    $database->category->delete($report['record_id']);
    $menu->messages[]="Category " . $database->category->data->name . " deleted";
    break;
  case (!isset($_SESSION['category'])):
	break;
  case ($report['action'] == "cancel"):
    $menu->messages[]="Request cancelled";
    unset($_SESSION['category']);
    break;
  case ($report['action'] == "update"):
	$database->category->data->name=trim($_POST['name']);
	$database->category->data->image_url=trim($_POST['image_url']);
	$database->category->data->active=intval($_POST['active']);
    foreach ($database->category->constant->documents as $key => $value) {
		if (!isset($database->category->data->documents[$key])) $database->category->data->documents[$key]=new document_inventory_class();
	    $field_document_id=base64_encode($key . "\t" . "document_id");
	    $field_html=base64_encode($key . "\t" . "html");
		switch (TRUE) {
		  case ($_POST[$field_document_id]):
			$database->category->data->documents[$key]->document_id=$_POST[$field_document_id];
			$database->category->data->documents[$key]->html="";
			break;
		  case ($_POST[$field_html]):
			$database->category->data->documents[$key]->html=$_POST[$field_html];
			$database->category->data->documents[$key]->document_id=0;
			break;
          default:
			$database->category->data->documents[$key]->document_id=0;
			$database->category->data->documents[$key]->html="";
        }
    }
    if (!$database->category->data->name) $menu->errors['name']=$menu->background['red'];
    if (($database->category->data->image_url) && (!file_exists($database->category->data->image_url))) $menu->errors['image_url']=$menu->background['red'];
	if (sizeof($menu->errors)) break;
    $database->category->update($database->category->meta->rows);
    if (mysql_errno()) {
        $menu->errors['name']=$menu->background['red'];
        $menu->messages[]=mysql_error();
	  } else {
    	$menu->messages[]="Record maintained";
		unset($_SESSION['category']);
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
print "<form name=scs_form enctype='multipart/form-data' method=post action='" . $_SERVER['PHP_SELF'] . "'>\n";
print "<input type=hidden name=action>\n";
print "<input type=hidden name=record_id>\n";

switch (TRUE) {
  case ($report['action'] == "maintain"):
  case (($report['action'] == "update") && (sizeof($menu->errors))):
	print "<table class='large noborder'>\n";
    print "<tr>\n";
    print "<td width=30%>Name</td>\n";
    print "<td width=70%><input type=text name=name size=60 " . fn_html_value($database->category->data->name,"","",$menu->errors['name']) . "></td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Image URL</td>\n";
    print "<td>";
    print "<input type=text name=image_url size=60 " . fn_html_value($database->category->data->image_url,"","",$menu->errors['image_url']) . "></td>\n";
    print "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Active?</td>\n";
    print "<td>" . $menu->checkbox("active", $database->category->data->active) . "</td>\n";
    print "</tr>\n";
    foreach ($database->category->constant->documents as $key => $value) {
	    $field_document_id=base64_encode($key . "\t" . "document_id");
	    $field_html=base64_encode($key . "\t" . "html");
    	print "<tr><td colspan=2><br><b>$value</b></td></tr>\n";
        if ($key != "description") {
	        print "<tr>\n";
	        print "<td>Document ID</td>\n";
	        print "<td>\n";
	        print $database->document->select($database->category->data->documents[$key]->document_id,$field_document_id);
	        print "</td>\n";
	        print "</tr>\n";
		}
    	print "<tr>\n";
        print "<td>HTML Text</td>\n";
		print "<td>" . $menu->textarea($field_html,$database->category->data->documents[$key]->html,6,60) . "</td>\n";
        print "</tr>\n";
    }
    print "</table>\n";
	print "<p class='standard center'>\n";
	print "<input class=fbutton type=button value='Update' onclick=\"javascript:fn_action(this,'update','" . $database->category->data->id . "','');\"> \n";
	print "<input class=fbutton type=button value='Cancel' onclick=\"javascript:fn_action(this,'cancel','','');\">\n";
	print "</p>\n";
  	break;
  default:
	print "<table class='standard border'>\n";
    print "<caption>&nbsp;</caption>\n";
    print "<tr>\n";
    print "<th width=100>Thumbnail</th>\n";
    print "<th width=40%>Name</th>\n";
    print "<th width=50%>Name</th>\n";
    print "<th width=10%>Del?</th>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td colspan=3><a href=\"javascript:fn_action(this,'maintain','0','');\">Add new</a></td>\n";
    print "</tr>\n";
    $query =
        "select * from category \n" .
        "order by name";
    $database->category->query($query);
    while ($database->category->fetch = mysql_fetch_array($database->category->meta->handle)) {
	    $database->category->fetch();
	    print "<tr>\n";
	    print "<td class=center>" . $menu->image($database->category->data->image_url,$database->category->data->name,100,100) . "</td>\n";
	    print
	        "<td>" .
	        "<a href=\"javascript:fn_action(this,'maintain','" . $database->category->data->id . "','');\">" .
	        $database->category->data->name .
	        "</a></td>\n";
	    print "<td>" . $database->category->data->documents['description']->html . "</td>\n";
	    print "<td class=center>";
	    if (!$database->category->data->active) {
	        print "<input type=radio name='delete' onclick=\"javascript:fn_action(this,'delete','" . $database->category->data->id . "','" . $database->category->data->code . "');\">\n";
	    }
	    print "</td>\n";
	    print "</tr>\n";
	}
	$database->category->free_result();
	print "</table>\n";
}
print "</form>\n";
$database->disconnect();
$menu->copyright();
class document_select {
	var $id;
    var $name;
    function __construct($id,$name) {
    	$this->id=$id;
        $this->name=$name;
    }
}
?>