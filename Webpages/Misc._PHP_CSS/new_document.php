<?php
include "/Webpages/Header_Footer/scs_header.php";
include_once "classes/document.php";
include_once "classes/userdocument.php";
$menu->access();
$menu->title="Harcourt Industrial: Document Manager";
$database->connect();
$menu->messages[]=$menu->title;
$_POST=fn_stripslashes($_POST);
$report['action']=$_POST['action'];
$report['record_id']=$_POST['record_id'];
if ($report['record_id']) $database->document->read($report['record_id']);
switch ($report['action']) {
  case "":
    break;
  case "cancel":
    $menu->messages[]="Request cancelled";
    break;
  case "delete":
    $database->document->delete($report['record_id']);
    $query =
        "delete from userdocument \n" .
        "where document_id=" . fn_escape($database->document->data->id);
    $database->userdocument->query($query);
    $menu->messages[]="Document #" . $database->document->data->id . " deleted";
	if (file_exists($database->document->results->file)) unlink($database->document->results->file);
    break;
  case "maintain":
  	if (!$report['record_id']) {
	    $database->document->data=new document_data_class();
        $database->document->data->parent_id=$_POST['record_parent_id'];
    }
	break;
  case "update":
	$database->document->data->name=$_POST['name'];
	if (!$database->document->data->name) $menu->errors['name']=$menu->background['red'];
	$database->document->data->description=$_POST['description'];
	$database->document->data->parent_id=$_POST['parent_id'];
	$database->document->data->active=$_POST['active'];
    switch (TRUE) {
      case (!$_FILES['source']['size']):
      	if (!$database->document->data->file) $menu->errors_text['source']="File upload required";
		break;
      case ($_FILES['source']['error']):
		$menu->errors_text['source']="Error: " . $_FILES["source"]["error"];
      	break;
      case (!$_FILES['source']['type']):
		$menu->errors_text['source']="Unknown file type";
      	break;
      default:
		$database->document->data->mime=$_FILES['source']['type'];
        $database->document->data->file=$_FILES['source']['name'];
        $database->document->data->upload_date=strtotime("now");
        break;
    }
    if ($menu->errors_text['source']) $menu->errors['source']=$menu->background['red'];
	if (!sizeof($menu->errors)) {
        $database->document->update($database->document->meta->rows);
        if (mysql_errno()) {
			$menu->errors['name']=$menu->background['red'];
            $menu->messages[]=mysql_error();
          } else {
			if ((!$_FILES['source']['error']) && ($_FILES['source']['size'])) {
            	if (file_exists($database->document->results->file)) unlink($database->document->results->file);
				move_uploaded_file($_FILES['source']['tmp_name'],$database->document->results->file);
            }
        }
    }
	if (!sizeof($menu->errors)) $menu->messages[]="Record maintained";
	break;
}
$menu->head();
$menu->message();
?>
<script type="text/javascript">
function fn_action(obj,action,id,parent_id,name) {
	if (action=='delete') {
	    if (!confirm("Delete: Document#" + id + " " + name)) {
	        obj.checked=false;
            return;
	    }
    }
	document.maintain_form.action.value=action;
	document.maintain_form.record_id.value=id;
	document.maintain_form.record_parent_id.value=parent_id;
	document.maintain_form.submit();
}
function fn_hide(id,hide) {
	if (hide) {
		document.getElementById(id).style.display = "none";
	  } else {
		document.getElementById(id).style.display = "block";
    }
}
</script>
<?php
print "<form name=maintain_form enctype='multipart/form-data' method=post action='" . $_SERVER['PHP_SELF'] . "'>\n";
print "<input type=hidden name=action>\n";
print "<input type=hidden name=record_id>\n";
print "<input type=hidden name=record_parent_id value='47'>\n";
switch (TRUE) {
  case ($report['action'] == "maintain"):
  case (($report['action'] == "update") && (sizeof($menu->errors))):
	print "<table class='standard noborder'>\n";
    if ($database->document->data->id) {
	    print "<tr>\n";
	    print "<td width=30%>Document ID</td>\n";
	    print "<td width=70%><b>" .  $database->document->data->id . "</b></td>\n";
	    print "</tr>\n";
	    print "<tr>\n";
	    print "<td>URL</td>\n";
	    print "<td><b>products.php?page=" .  $database->document->data->id . "</b></td>\n";
	    print "</tr>\n";
    }
    print "<tr>\n";
    print "<td width=30%>Name</td>\n";
    print "<td width=70%><input type=text name=name size=60 " . fn_html_value($database->document->data->name,"","",$menu->errors['name']) . "></td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Description</td>\n";
    print "<td><input type=text name=description size=60 " . fn_html_value($database->document->data->description,"","",$menu->errors['description']) . "></td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Parent Document</td>\n";
    print "<td>";
	$database->temp=new database_template_class();
	$query_where=array();
	$query_where[] = new query_where("parent_id","=",0);
	if ($database->document->data->id) $query_where[] = new query_where("id","<>",$database->document->data->id);
    $query =
    	"select * from document \n" .
		$database->where($query_where) .
        "order by name,id";
    $database->temp->query($query);
    print "<select name=parent_id>\n";
    print "<option value='0'>0. Parent</option>\n";
    while ($database->temp->fetch = mysql_fetch_array($database->temp->meta->handle)) {
    	if ($database->temp->fetch['id'] == $database->document->data->parent_id) {
        	$selected="selected";
          } else {
          	$selected="";
        }
        print "<option value='" . $database->temp->fetch['id'] . "' $selected>" . $database->temp->fetch['id'] . ". " . $database->temp->fetch['name'] . "</option>\n";
    }
    print "</select>\n";
    $database->temp->free_result();
    print "</td>\n";
    print "</tr>\n";
	if ($database->document->data->file) {
	    print "<tr>\n";
	    print "<td>Source file</td>\n";
	    print "<td><a href='file_upload.php?page=" . $database->document->data->id . "' title='Upload file'>" . $database->document->data->file . "</a></td>\n";
	    print "</tr>\n";
	    print "<tr>\n";
	    print "<td>Mime</td>\n";
	    print "<td><b>" . $database->document->data->mime . "</b></td>\n";
	    print "</tr>\n";
	    print "<tr>\n";
	    print "<td>Upload date/time</td>\n";
	    print "<td><b>" . date("m/d/Y H:i A",$database->document->data->upload_date) . "</b></td>\n";
	    print "</tr>\n";
	}
	print "<tr>";
	print "<td>Upload file</td>";
	print "<td><input type=file name=source size=40 " . $menu->errors['source'] . ">\n";
    if ($menu->errors_text['source']) print "<br><b>" . $menu->errors_text['source'] . "</b>";
    print "</td>";
	print "</tr>";
    print "<tr>\n";
    print "<td>Active</td>\n";
    if ($database->document->data->active) {
    	$checked="checked";
      } else {
      	$checked="";
    }
    print "<td><input type=checkbox name=active value='1' $checked></td>\n";
    print "</tr>\n";
    print "</table>\n";
	print "<p class='standard center'>\n";
	print "<input class=fbutton type=button value='Update' onclick=\"javascript:fn_action(this,'update','" . $database->document->data->id . "','','');\"> \n";
	print "<input class=fbutton type=button value='Cancel' onclick=\"javascript:fn_action(this,'cancel','','','');\">\n";
	print "</p>\n";
    break;
  default:
  	$document=array();
	$query =
    	"select \n" .
        "(case \n" .
        "when parent_id=1 then 9999 \n" .
        "when id=1 then 9999 \n" .
        "when parent_id=0 then id \n" .
        "else parent_id \n" .
        "end) as sort_key, \n" .
        "document.* from document \n" .
        "order by sort_key,parent_id,id";
    $database->document->query($query);
    print "<table class='border small'>\n";
    print "<tr>\n";
    print "<th width=50% colspan=2>Document</th>\n";
    print "<th width=30%>File</th>\n";
    print "<th>Mime</th>\n";
    print "<th>Date/Time</th>\n";
    print "<th>Del?</th>\n";
    print "</tr>\n";
    $sort_key=0;
    while ($database->document->fetch = mysql_fetch_array($database->document->meta->handle)) {
		$database->document->fetch();
        if ($sort_key != $database->document->fetch['sort_key']) {
	        if ($sort_key) {
            	print "<tr><td colspan=7>&nbsp;</td></tr>\n";
            }
	        $sort_key=$database->document->fetch['sort_key'];
        }
	    print "<tr>\n";
        $href=
        	"<a href=\"javascript:fn_action(this,'maintain','" . $database->document->data->id . "','','');\">" .
			$database->document->data->id . ". " .
			$database->document->data->name . "</a>";
		if ($database->document->data->parent_id) {
			print "<td>&nbsp;</td>\n";
            print "<td>$href</td>\n";
          } else {
			print
            	"<td colspan=2>$href" .
				" (<a href=\"javascript:fn_action(this,'maintain','0','" . $database->document->data->id . "','');\">Add new child</a>)" .
				"</td>\n";
        }
        print "<td>" . $database->document->data->file . "</td>\n";
        print "<td class=center>" . $database->document->data->mime . "</td>\n";
        print "<td class=center><nobr>";
        if ($database->document->data->upload_date) {
            print date("m/d/Y H:i A",$database->document->data->upload_date);
          } else {
            print "&nbsp;";
        }
        print "</td></nobr>\n";
		print "<td class=center>";
        if ($database->document->data->active) {
        	print "&nbsp;";
          } else {
			print "<input type=radio name='delete' onclick=\"javascript:fn_action(this,'delete','" . $database->document->data->id . "','','" . $database->document->data->name . "');\">\n";
		}
	    print "</tr>\n";
	}
	$database->document->free_result();
	print "<tr><td colspan=7>&nbsp;</td></tr>\n";
    print "<tr><td colspan=7><a href=\"javascript:fn_action(this,'maintain','0','0','');\">Add new parent</a></td></tr>\n";
    print "</table>\n";
}
?>
<script>
//obj=document.images[].property;
alert("# images " + document.images.length);
alert("# forms " + document.forms.length);
alert("# anchors " + document.anchors.length);
alert("# links " + document.links.length);
var i=0;
while (i < document.forms.length) {
	alert (document.forms[i].name);
    i++;
}
</script>
<?php
$database->disconnect();
$menu->copyright();
?>