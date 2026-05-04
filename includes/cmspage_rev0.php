<?php
if (!$database->registry->cms->frame) fn_url_redirect($_SERVER['HTTP_REFERER']);
require_once "classes/cmscontent.php";
require_once "classes/cmspage.php";
$forms->title("CMS Page Definitions (Revised 01/10/2013)");
$forms->report['action']=$_REQUEST['action'];
$forms->report['record_id']=$_REQUEST['record_id'];

if ($forms->report['record_id']) {
	$database->cmspage->read($forms->report['record_id']);
  } else {
	$database->cmspage->data=new cmspage_data_class();
}

switch ($forms->report['action']) {
  case "":
	break;
  case "cancel":
    $forms->message[]="Request cancelled";
    break;
  case "delete":
	$database->cmspage->delete($forms->report['record_id']);
	$query="delete from cmscontent where page=" . fn_escape($database->cmspage->data->page);
    $database->temp->query($query);
    $forms->message[]=("CMS Page " . $database->cmspage->data->page . " deleted");
    break;
  case "maintain":
    break;
  case "update":
	$database->cmspage->data->page=trim($_POST['page']);
    switch (TRUE) {
      case (!$database->cmspage->data->page):
      	$forms->error('page');
        break;
	  case (substr($database->cmspage->data->page,0,1) != "/"):
    	$database->cmspage->data->page = "/" . $database->cmspage->data->page;
        break;
	}
	$database->cmspage->data->name=trim($_POST['name']);
    if (!$database->cmspage->data->name) $forms->error('name');
	$database->cmspage->data->meta_title=trim($_POST['meta_title']);
	$database->cmspage->data->meta_description=trim($_POST['meta_description']);
	$database->cmspage->data->active=$_POST['active'];
    if (sizeof($forms->error)) break;
	$database->cmspage->update($forms->report['record_id']);
    if ($database->cmspage->meta->error) {
    	$forms->error("page",'',$database->cmspage->meta->error);
	  } else {
		switch (TRUE) {
		  case (!$forms->report['record_id']):
		  case ($database->cmspage->data->page==$database->cmspage->fetch['page']):
			$forms->message[]="Record maintained";
          	break;
		  default:
          	$query =
            	"update cmscontent \n" .
                "set page=" . fn_escape($database->cmspage->data->page) .
                " where page=" . fn_escape($database->cmspage->fetch['page']);
			$database->temp->query($query);
			$forms->message[]="Name changed from " . $database->cmspage->fetch['page'] . " to " . $database->cmspage->data->page;
        }
	}
}

$menu->head();
$forms->message();
?>
<script type="text/javascript">
function fn_action(obj,action,id,page) {
	if (action=='delete') {
	    if (!confirm("Delete " + page + "?")) {
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
  case (($forms->report['action'] == "update") && (sizeof($forms->error))):
  case ($forms->report['action'] == "maintain"):
    print "<table class='standard noborder'>\n";
    print "<tr>\n";
    print "<td width=30%>Page</td>\n";
	print "<td width=70%>" . $forms->text("page",$database->cmspage->data->page,40) . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Name</td>\n";
	print "<td>" . $forms->text("name",$database->cmspage->data->name,40) . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Meta Title</td>\n";
	print "<td>" . $forms->text("meta_title",$database->cmspage->data->meta_title,60) . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Meta Description</td>\n";
	print "<td>" . $forms->textarea("meta_description",$database->cmspage->data->meta_description,4,60) . "</td>\n";
    print "</tr>\n";
	print "<tr>\n";
    print "<td>Active?</td>\n";
    print "<td>" . $forms->checkbox("active",1,$database->cmspage->data->active) . "</td>\n";
    print "</tr>\n";
    print "</table>\n";
    print
    	"<p class=center>" . $forms->button("Update",array("onclick"=>"fn_action(this,'update'," . fn_escape($forms->report['record_id']) . ",'');")) . " " .
        $forms->button("Cancel",array("onclick"=>"fn_action(this,'cancel','0','');")) . "</p>\n";
 	break;
  default:
    print "<table class='standard border'>\n";
	print $forms->caption();
    print "<tr>\n";
	print "<th>Page</th>\n";
	print "<th>Name</th>\n";
	print "<th>Del?</th>\n";
    print "</tr>\n";
    print "<tr><td colspan=3>" . fn_href("Add new","javascript:fn_action(this,'maintain','0','');") . "</td></tr>\n";
    $query =
        "select * from cmspage\n" .
        "order by cmspage.name";
    $database->cmspage->query($query);
    while ($database->cmspage->fetch = $database->cmspage->fetch_array()) {
        $database->cmspage->fetch();
		if (!$database->cmspage->data->active) {
        	$bg=$forms->background->yellow;
		  } else {
            $bg="";
		}
	    print "<tr $bg>\n";
	    print "<td >" . fn_href($database->cmspage->data->page,"javascript:fn_action(this,'maintain'," . fn_escape($database->cmspage->data->id) . ",'');") . "</td>\n";
		print "<td>" . $database->cmspage->data->name . "</td>\n";
	    print "<td class=center>";
		if (!$database->cmspage->data->active) {
            print $forms->radio("delete",1,0,array("onclick"=>"fn_action(this,'delete'," . fn_escape($database->cmspage->data->id) . "," . fn_escape($database->cmspage->data->page) . ");"));
          } else {
            print "&nbsp;";
        }
        print "</td>\n";
        print "</tr>\n";
	}
    print "</table>\n";
	$database->cmspage->free_result();
}
print $forms->close();
$menu->copyright();
?>