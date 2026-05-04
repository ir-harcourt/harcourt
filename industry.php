<?php
require_once "scs_header.php";
require_once "classes/industry.php";
require_once "classes/category.php";
require_once "classes/subcategory.php";
$database->user->access("Executive");
$forms->title("Industry");

$forms->report['action']=$_POST['action'];
$forms->report['record_id']=$_POST['record_id'];
if ($forms->report['record_id']) {
	$database->industry->read($forms->report['record_id']);
  } else {
	$database->industry->data=new industry_data_class();
}
switch (TRUE) {
  case (!$forms->report['action']):
    break;
  case ($forms->report['action']=="cancel"):
    $forms->message[]="Request cancelled";
    break;
  case ($forms->report['action']=="delete"):
    $database->subcategory->access_delete("industry",$database->industry->data->id);
    $database->industry->delete($forms->report['record_id']);
    $forms->message[]="Industry " . $database->industry->data->name . " deleted";
    break;
  case ($forms->report['action']=="maintain"):
	break;
  case ($forms->report['action']=="update"):
	$database->industry->data->name=fn_text($_POST['name']);
    if (!$database->industry->data->name) $forms->error('name');
	$database->industry->data->active=$_POST['active'];
    $forms->constant->subcategory=array();
    foreach ($_POST as $key => $value) {
		$field=preg_split("/_/",$key);
        if ($field[0]=="subcategory") $forms->constant->subcategory[]=$value;
    }
	if (!sizeof($forms->constant->subcategory)) $forms->error('all','','No subcategories selected');
	if (sizeof($forms->error)) break;
	$database->industry->update($database->industry->meta->rows);
    switch (TRUE) {
      case (!$database->industry->meta->error):
		$database->subcategory->access_update("industry",$database->industry->data->id,$forms->constant->subcategory);
		$forms->message[]="Record maintained";
        break;
      default:
		$forms->error('name','',$database->industry->meta->error);
	}
}
$menu->head();
$forms->message();
?>
<script type='text/javascript'>
var category=[];
var subcategory=[];
function fn_action(obj,action,id,name) {
	if (action=='delete') {
	    if (!confirm("Delete: " + name)) {
	        obj.checked=false;
            return;
	    }
    }
	document.scs_form.action.value=action;
	document.scs_form.record_id.value=id;
	document.scs_form.submit();
}
function fn_subcategory(obj) {
	field=obj.id.split("_");
    switch (field[0]) {
	  case "all":
		$('input.subcategory').prop('checked',obj.checked);
        break;
      case "category":
		$('input.' + obj.id).prop('checked',obj.checked);
        break;
      case "subcategory":
// reserved
        break;
	}
}
$(document).ready(function() {
// reserved
});
function indeterminate_checkbox(field) {
/* this works!!!
	$('#' + field).prop('indeterminate', true);
*/
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
    $menu->tabs->item("Name",name_tab());
    $menu->tabs->item("Subcategory",subcategory_tab());
    print $menu->tabs->output();
    $buttons=array();
    if ($database->user->access("Administrator",FALSE)) $buttons[]=$forms->button("Update",array("onclick"=>"fn_action(this,'update'," . fn_escape($forms->report['record_id']) . ",'');"));
	$buttons[]=$forms->button("Cancel",array("onclick"=>"fn_action(this,'cancel','','');"));
	print "<p>" . implode(" ",$buttons) . "</p>\n";
	break;
  default:
  	$colspan=1;
	print "<table class='standard border'>\n";
    print "<caption>&nbsp</caption>\n";
	print "<tr>\n";
	print "<th>Name</th>\n";
	if ($database->user->access("Administrator",FALSE)) {
		print "<th width='10%'>Del?</th>\n";
		$colspan++;
	}
	print "</tr>\n";
	if ($database->user->access("Administrator",FALSE)) {
	    print "<tr>\n";
        print "<td colspan=$colspan>" . fn_href("Add new","javascript:fn_action(this,'maintain','','');") . "</td>\n";
	    print "</tr>\n";
    }
	$database->industry->query("select * from industry order by name");
	while ($database->industry->fetch = $database->industry->fetch_array()) {
		$database->industry->fetch();
	    if (!$database->industry->data->active) {
	        $background=$forms->background->yellow;
	      } else {
	        $background="";
	      }
	      print "<tr $background>\n";
	      print "<td>" . fn_href($database->industry->data->name,"javascript:fn_action(this,'maintain'," . fn_escape($database->industry->data->id) . ",'');") . "</td>\n";
		  if ($database->user->access("Administrator",FALSE)) {
	      	if ($database->industry->data->active) {
	        	$text="&nbsp;";
	          } else {
	            $text=$forms->radio('delete',1,0,array("onclick"=>"fn_action(this,'delete'," . fn_escape($database->industry->data->id) . "," . fn_escape($database->industry->data->name) . ");"));
	        }
	         print "<td class=center>$text</td>\n";
		}
		print "</tr>\n";
	}
	$database->industry->free_result();
	print "</table>\n";
}
print $forms->close();
$menu->copyright();
function name_tab() {
global $database, $forms, $menu;
	$results=array();
    $results[]="<table class='standard noborder'>";
    $results[]="<tr>";
    $results[]="<td width='30%'>Name</td>";
    $results[]="<td width='70%'>" . $forms->text("name",$database->industry->data->name,60,60) . "</td>";
    $results[]="</tr>";
    $results[]="<tr>";
    $results[]="<td>Active?</td>";
    $results[]="<td>" . $forms->checkbox("active",1,$database->industry->data->active) . "</td>";
    $results[]="</tr>";
    $results[]="</table>";
	return $results;
}
function subcategory_tab() {
global $database, $forms, $menu;
	$query_where=array();
	$query_where[]=new query_where("category.active","=",1);
	$query_where[]=new query_where("subcategory.status","<>","Inactive");
	$query_where[]=new query_where("category.name is not null","","");
	$query=array();
    $query[]="select";
    $query[]="category.name as 'category.name',";
    $query[]="subcategory.* from subcategory";
    $query[]="left join category on category.id=subcategory.category_id";
    $query[]=trim($database->where($query_where));
    $query[]="order by category.sort_order,category.name,subcategory.sort_order,subcategory.name";
	$database->subcategory->query($query);
    $categories=array();
    $subcategories=array();
    while ($database->subcategory->fetch = $database->subcategory->fetch_array() ) {
		$database->subcategory->fetch();
        if (!array_key_exists($database->subcategory->data->category_id,$categories)) {
        	$category=new stdClass();
            $category->id=$database->subcategory->data->category_id;
            $category->name=$database->subcategory->fetch['category.name'];
            $category->count=0;
            $category->found=0;
        	$categories[$database->subcategory->data->category_id]=$category;
        	$subcategories[$database->subcategory->data->category_id]=array();
		}
        $subcategory=new stdClass();
        $subcategory->id=$database->subcategory->data->id;
        $subcategory->name=$database->subcategory->data->name;
        switch (TRUE) {
          case (isset($forms->constant->subcategory)):
	        $subcategory->found=in_array($database->subcategory->data->id,$forms->constant->subcategory);
            break;
          case (!$forms->report['record_id']):
	        $subcategory->found=TRUE;
            break;
		  default:
	        $subcategory->found=in_array($forms->report['record_id'],$database->subcategory->data->industry);
        }
        $subcategories[ $database->subcategory->data->category_id ][ $database->subcategory->data->id ]=$subcategory;
		$categories[$database->subcategory->data->category_id]->count++;
		$categories[$database->subcategory->data->category_id]->found += $subcategory->found;
    }
	$database->subcategory->free_result();
	$results=array();
    $results[]="<table class='standard border'>";
	$results[]="<tr><th style='text-align: left;'>" . $forms->checkbox("all",1,0,array("onclick"=>"fn_subcategory(this);")) . " All subcategories</th></tr>";
	$results[]="<tr><td>&nbsp;</td></tr>";
    foreach ($categories as $category) {
		$results[]="<tr><th style='text-align: left;'>" . $forms->checkbox("category_" . $category->id,1,(($category->count == $category->found) ? 1 : 0),array("onclick"=>"fn_subcategory(this);","class"=>"subcategory")) . " " . $category->name . "</th></tr>";
        $content=array();
        foreach ($subcategories[$category->id] as $subcategory) {
			$content[]=$forms->checkbox("subcategory_" . $category->id . "_" . $subcategory->id,$subcategory->id,$subcategory->found,array("onclick"=>"fn_subcategory(this);","class"=>"subcategory category_" . $category->id)) . " " . $subcategory->name;
        }
		$results[]="<tr><td><div class=newspaper2>" . implode("<br>\n",$content) . "</div></td></tr>";
	}
    $results[]="</table>";
	return $results;
}
?>