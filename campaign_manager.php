<?php
require_once "scs_header.php";
require_once "classes/category.php";
require_once "classes/subcategory.php";

if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
	$results=array();
    switch ($_REQUEST['ajax_action']) {
      case "add":
	    $campaign=new campaign_content_class();
	    $results['row']=campaign_row($_REQUEST['ajax_row'],$campaign);
        $results['status']="Add row " . $_REQUEST['ajax_row'];
        break;
      case "delete":
		if (!isset($_SESSION['campaign_content'])) break;
		$menu->campaign_content=unserialize($_SESSION['campaign_content']);
        unset($menu->campaign_content[intval($_REQUEST['ajax_content_id'])]);
        $_SESSION['campaign_content']=serialize($menu->campaign_content);
        $results['status']="Delete content id " . $_REQUEST['ajax_content_id'];
        break;
      default:
        $results['status']="Unknown action: " . $_REQUEST['ajax_action'];
	}
    print json_encode($results);
    die();
}
$database->user->access("Administrator");
$forms->title("Campaign Manager");
$forms->report['action']=$_POST['action'];
$forms->report['record_id']=$_POST['record_id'];
$forms->constant->content=array();
if ($forms->report['record_id']) {
	$database->campaign->read($forms->report['record_id']);
  } else {
   	$database->campaign->data=new campaign_data_class();
}
switch (TRUE) {
  case (!$forms->report['action']):
    break;
  case ($forms->report['action'] == "cancel"):
	unset($_SESSION['campaign_content']);
    $forms->message[]="Request cancelled";
    break;
  case ($forms->report['action'] =="maintain"):
	$menu->campaign_content=array();
    $query_where=array();
    $query_where[]=new query_where("record_type","=","campaign");
    $query_where[]=new query_where("record_id","=",$forms->report['record_id']);
    $query=array("select");
	$query[]="case";
	$query[]="when record_item like 'public%' then 1";
	$query[]="when record_item like 'semi-private%' then 2";
	$query[]="else 3";
	$query[]="end as sort_order,";
	$query[]="content.* from content";
    $query[]=trim($database->where($query_where));
	$query[]="order by sort_order,record_item";
    $database->content->query($query);
    while ($database->content->fetch = $database->content->fetch_array() ) {
		$database->content->fetch();
		$data=preg_split("/\:/",$database->content->data->record_item);
        switch (TRUE) {
          case (sizeof($data) <> 2):
          case (!in_array($data[0],$database->campaign->constant->access)):
          case (!preg_match("/[0-9()]$/",$data[1]));
            continue;
          default:
	        $content=new campaign_content_class;
	        $content->file($database->content->data);
	        $menu->campaign_content[$database->content->data->id]=$content;
		}
    }
	$_SESSION['campaign_content']=serialize($menu->campaign_content);
    break;
  case ($forms->report['action'] == "delete"):
    $database->campaign->delete($forms->report['record_id']);
    $forms->message[]="Campaign " . $database->campaign->data->code . " deleted";
    break;
  case ($forms->report['action'] == "update"):
	if (!isset($_SESSION['campaign_content'])) break;
    $menu->campaign_content=array();
    $campaign_content=unserialize($_SESSION['campaign_content']);
	$database->campaign->data->code=fn_text($_POST['code'],TRUE);
    if (!strlen($database->campaign->data->code)) $forms->error('code');
	$database->campaign->data->name=fn_text($_POST['name']);
    if (!strlen($database->campaign->data->name)) $forms->error('name');
	$database->campaign->data->description=trim($_POST['description']);
	$database->campaign->data->from_date=fn_date($_POST['from_date'],"mdy");
    if (!$forms->date_ok) $forms->error('from_date');
	$database->campaign->data->thru_date=fn_date($_POST['thru_date'],"mdy");
    switch (TRUE) {
      case (!$forms->date_ok):
      case ( ($database->campaign->data->thru_date) && ($database->campaign->data->from_date > $database->campaign->data->thru_date) ):
      	$forms->error('thru_date');
	}
	$database->campaign->data->type=$_POST['type'];
	$database->campaign->data->sf_campaign_id=$_POST['sf_campaign_id'];
	$database->campaign->data->sort_order=$_POST['sort_order'];
	$database->campaign->data->active=$_POST['active'];
	$database->campaign->data->title_omit=$_POST['title_omit'];
	$database->campaign->data->divs=array();
	foreach ($_POST as $key => $value) {
    	list($field,$item)=(fn_base64($key,FALSE));
        if ($field=="divs") {
        	$value=trim($value);
            if (strlen($value)) $database->campaign->data->divs[$item]=$value;
        }
    }
	$database->campaign->data->css=array();
    $errors=array();
    $items=preg_split("/\n/",$_POST['css']);
    foreach ($items as $item) {
		$item=trim($item);
        if ( (strlen($item)) && (!in_array($item,$database->campaign->data->css)) ) {
			$database->campaign->data->css[]=$item;
            if (!preg_match("/^http/i",$item)) {
            	$error=verify_file($item,"css",TRUE);
                if (strlen($error)) $errors[]=$error;
            }
		}
    }
    if (sizeof($errors)) $forms->error("css",$errors);
	$database->campaign->data->js=array();
    $errors=array();
    $items=preg_split("/\n/",$_POST['js']);
    foreach ($items as $item) {
		$item=trim($item);
        if ( (strlen($item)) && (!in_array($item,$database->campaign->data->js)) ) {
			$database->campaign->data->js[]=$item;
            if (!preg_match("/^http/i",$item)) {
            	$error=verify_file($item,"js",TRUE);
                if (strlen($error)) $errors[]=$error;
            }
		}
    }
    if (sizeof($errors)) $forms->error("js",$errors);
	$database->campaign->data->subcategory=array();
    foreach ($_POST as $key => $value) {
		$field=preg_split("/\_/",$key);
        if ($field[0]=="subcategory") $database->campaign->data->subcategory[]=$field[2];
    }
    foreach ($_POST as $key => $value) {
		if (preg_match("/^content_name/i",$key)) {
        	$row=intval(preg_replace("/^content_name/i","",$key));
        	$forms->constant->content[$row]=$_POST["content_content_id{$row}"];
		}
	}
    $item=0;
    foreach ($forms->constant->content as $row => $content_id) {
		if (array_key_exists($content_id,$campaign_content)) {
			$content=$campaign_content[$content_id];
		  } else {
          	switch (TRUE) {
              case (!sizeof($menu->campaign_content)):
              case (min(array_keys($menu->campaign_content)) > 0):
				$content_id=-1;
                break;
               default:
               	$content_id=min(array_keys($menu->campaign_content)) - 1;
			}
		  	$content=new campaign_content_class($content_id);
        }
        $content->error=array();
        $content->name=trim($_POST["content_name{$row}"]);
        if (!strlen($content->name)) $content->error["content_name{$item}"]="";
        $content->description=trim($_POST["content_description{$row}"]);
        $content->access=$_POST["content_access{$row}"];
		$content->upload($row,$item);
		foreach ($content->error as $key => $value) {
        	$forms->error($key,$value);
        }
		$menu->campaign_content[$content_id]=$content;
		$item++;
    }
	$_SESSION['campaign_content']=serialize($menu->campaign_content);
	if ( ($database->campaign->data->type != "Remote") && (!sizeof($menu->campaign_content)) ) $forms->error("null","","Content required");

	if (sizeof($forms->error)) break;
    $database->campaign->update($forms->report['record_id']);
    if ($database->campaign->meta->error) {
		$forms->error('null','',$database->campaign->meta->error);
		break;
	}
    $query_where=array();
    $query_where[]=new query_where("record_type","=","campaign");
    $query_where[]=new query_where("record_id","=",$database->campaign->data->id);
    $query=array();
    $query[]="update content";
    $query[]="set record_item=id";
    $query[]=trim($database->where($query_where));
    $database->content->query($query);
    $row=0;
	foreach ($menu->campaign_content as $content) {
		$database->content->read($content->id);
		$database->content->data->record_id=$database->campaign->data->id;
		$database->content->data->record_type="campaign";
		$database->content->data->record_item=$content->access . ":" . substr("000000" . $row,-6);
		$database->content->data->name=$content->name;
		$database->content->data->description=$content->description;
		$database->content->data->filetype="html";
		$database->content->data->revised=strtotime("now");
		$database->content->data->meta=$content->meta;
		$database->content->data->content=$content->content;
		$database->content->update($database->content->data->id);
        $row++;
    }
    $query=array();
    $query[]="delete from content";
    $query_where[]=new query_where("id","=","record_item","","field");
    $query[]=trim($database->where($query_where));
    $database->content->query($query);
	$forms->message[]="Record maintained";
	unset($_SESSION['campaign_content']);
	break;
}
$menu->head();
print $forms->message();
?>
<script type='text/javascript'>
function fn_action(obj,action,id,name) {
	if (action=='delete') {
	    if (!confirm("Campaign: " + name)) {
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
$( function() {
	$( "#sortable_accordion" )
    .accordion({
	    header: "> div > h3",
	    collapsible: true,
	    active: false
	})
  .sortable({
    axis: "y",
    handle: "h3",
    stop: function( event, ui ) {
      ui.item.children( "h3" ).triggerHandler( "focusout" );
      $( this ).accordion( "refresh" );
    }
  });
} );
function content_row_add() {
	row=$("#row").val();
    row++;
	$("#row").val(row);
	$.ajax({
	    data: {
        	ajax_action: 'add',
        	ajax_row: row
        },
	    dataType: 'json',
	    success:function(data){
			$( "#sortable_accordion" ).append(data['row']);
			$( "#sortable_accordion" ).accordion( "refresh" );
			$( "#sortable_accordion" ).accordion( "option", "active", $('#sortable_accordion h3').index("#content_label" + row) );
        }
	});
}
function content_row_del(obj, row, content_id) {
    if (!confirm('Delete row?')) {
        obj.checked=false;
        return;
    }
	$.ajax({
	    data: {
        	ajax_action: 'delete',
        	ajax_content_id: content_id
        },
	    dataType: 'json',
	    success:function(data){
	        $( "#content_div" + row ).remove();
	        $( "#sortable_accordion" ).accordion( "refresh" );
	        $( "#sortable_accordion" ).accordion( "option", "active", false );
        }
	});
}
function content_label(id) {
	data=new Array();
	title=$("#content_name" + id).val();
    if (title.length) {
        data.push($("#content_access" + id).val() + ":" );
    	data.push(title);
	  } else {
        data.push("Empty:");
		data.push("New Content");
	}
	$('#content_label' + id).html(data.join(' '));
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
    $menu->tabs->item("Info",tab_info());
    $menu->tabs->item("Content",tab_content());
    $menu->tabs->item("Divs",tab_divs());
    $menu->tabs->item("Subcategories",tab_subcategory());
    $menu->tabs->item("CSS",tab_css());
    $menu->tabs->item("JavaScript",tab_js());
    $menu->tabs->output();
    $buttons=array();
    $buttons[]=$forms->button("Update",array("onclick"=>"fn_action(this,'update'," . fn_escape($database->campaign->data->id) . ",'');"));
    $buttons[]=$forms->button("Cancel",array("onclick"=>"fn_action(this,'cancel','','');"));
    print "<p>" . implode(" ",$buttons) . "</p>";
  	break;
  default:
	print "<table class='standard border tablesorter'>\n";
    print "<thead>";
	print "<tr>";
    print "<th>Code</th>";
    print "<th>Name</th>";
    print "<th>From Date</th>";
    print "<th>Thru Date</th>";
    print "<th>Order</th>";
    print "<th>Type</th>";
    print "<th>Del?</th>";
	print "</tr>";
    print "</thead>";
	print "<tr>";
    print "<td colspan=6>" . fn_href("Add new","javascript:fn_action(this,'maintain','','');") . "</td></tr>";
	print "</tr>";
    print "<tbody>";
    $query=array();
    $query[]="select * from campaign";
    $query[]="order by active desc, sort_order, name, code";
    $database->campaign->query($query);
    while ($database->campaign->fetch = $database->campaign->fetch_array() ) {
        $database->campaign->fetch();
        switch (TRUE) {
          case (!$database->campaign->data->active):
          	$bg=$forms->background->yellow;
            break;
          case (!$database->campaign->active()):
          	$bg=$forms->background->lightgrey;
			break;
		  default:
          	$bg="";
		}
        print "<tr $bg>";
        print "<td>" . fn_href($database->campaign->data->code,"javascript:fn_action(this,'maintain'," . fn_escape($database->campaign->data->id) . ",'');") . "</td>";
        print "<td>" . $database->campaign->data->name . "</td>";
        print "<td class=center>" . fn_date($database->campaign->data->from_date,"ymd") . "</td>";
        print "<td class=center>" . fn_date($database->campaign->data->thru_date,"ymd") . "</td>";
        print "<td class=center>" . $database->campaign->data->sort_order . "</td>";
        print "<td>" . $database->campaign->data->type . "</td>";
        $text=( ($database->campaign->data->active) ? "&nbsp;" : $forms->radio("delete",$database->campaign->data->id,0,array("onclick"=>"fn_action(this,'delete'," . fn_escape($database->campaign->data->id) . "," . fn_escape($database->campaign->data->code) .");"))  );
        print "<td class=center>{$text}</td>";
        print "</tr>";
    }
    print "</tbody>";
    print "</table>";
	$database->campaign->free_result();
}
print $forms->close();
$menu->copyright();
function tab_info() {
global $database, $forms, $menu;
	$results=array();
	$results[]="<table class='large noborder'>";
    $results[]="<tr>";
    $results[]="<td width='30%'>Code</td>";
    $results[]="<td width='70%'>" . $forms->text("code",$database->campaign->data->code,12,12) . "</td>";
    $results[]="</tr>";
    $results[]="<tr>";
    $results[]="<td>Name</td>";
    $results[]="<td>" . $forms->text("name",$database->campaign->data->name,40,80) . "</td>";
    $results[]="</tr>";
    $results[]="<tr>";
    $results[]="<td>Meta Description</td>";
    $results[]="<td>" . $forms->text("description",$database->campaign->data->description,60,255) . "</td>";
    $results[]="</tr>";
    $results[]="<tr>";
    $results[]="<td>From Date</td>";
    $results[]="<td>" . $forms->text("from_date",$database->campaign->data->from_date,10,10,"date") . "</td>";
    $results[]="</tr>";
    $results[]="<tr>";
    $results[]="<td>Thru Date</td>";
    $results[]="<td>" . $forms->text("thru_date",$database->campaign->data->thru_date,10,10,"date") . "</td>";
    $results[]="</tr>";
    $results[]="<tr>";
    $results[]="<td>Type</td>";
    $results[]="<td>" . $forms->select("type",$database->campaign->constant->type,$database->campaign->data->type,FALSE) . "</td>";
    $results[]="</tr>";
    $results[]="<tr>";
    $results[]="<td>Salesforce Campaign ID</td>";
    $results[]="<td>" . $forms->text("sf_campaign_id",$database->campaign->data->sf_campaign_id,30,30) . "</td>";
    $results[]="</tr>";
    $results[]="<tr>";
    $results[]="<td>Sort Order</td>";
    $results[]="<td>" . $forms->select_number("sort_order",$database->campaign->data->sort_order,0,99) . "</td>";
    $results[]="</tr>";
    $results[]="<tr>";
    $results[]="<td>Active?</td>";
    $results[]="<td>" . $forms->checkbox("active",1,$database->campaign->data->active) . "</td>";
    $results[]="</tr>";
	$results[]="</table>";
    return $results;
}
function tab_content() {
global $database, $forms, $menu;
	$results=array();
    $results[]="<div id='sortable_accordion'>";
    $row=-1;
	foreach ($menu->campaign_content as $content) {
		$row++;
		$results[]=campaign_row($row,$content);
    }
    $results[]="</div> <!-- .sortable_accordion -->";
    $results[]="<p>" . $forms->button("Add Content",array("onclick"=>"content_row_add();")) . "</p>";
    print $forms->hidden("row",$row);
    return $results;
}
function tab_divs() {
global $database, $forms, $menu;
	$results=array();
	$results[]="<table class='large noborder'>";
    $results[]="<tr>";
    $results[]="<td width='30%'>Omit Titles?</td>";
    $results[]="<td width='70%'>" . $forms->checkbox("title_omit",1,$database->campaign->data->title_omit) . "</td>";
    $results[]="</tr>";
    $results[]="<tr><td><br>Divs</td></tr>";
    foreach ($database->campaign->constant->divs as $key => $value) {
    	$results[]="<tr>";
    	$results[]="<td>{$value}</td>";
    	$results[]="<td>" . $forms->text(fn_base64(array("divs",$key)),$database->campaign->data->divs[$key],40,40) . " (campaign_{$key})</td>";
    	$results[]="</tr>";
    }
    $results[]="</table>";
    return $results;
}
function tab_subcategory() {
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
        $subcategory->found=in_array($database->subcategory->data->id,$database->campaign->data->subcategory);
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
			$content[]=$forms->checkbox("subcategory_" . $category->id . "_" . $subcategory->id,1,$subcategory->found,array("onclick"=>"fn_subcategory(this);","class"=>"subcategory category_" . $category->id)) . " " . $subcategory->name;
        }
		$results[]="<tr><td><div class=newspaper2>" . implode("<br>\n",$content) . "</div></td></tr>";
	}
    $results[]="</table>";
	return $results;
}
function tab_css() {
global $database, $forms, $menu;
	$results=array();
	$results[]="<table class='large noborder'>";
    $results[]="<tr>";
    $results[]="<td width='30%'>Additional CSS</td>";
    $text=array($forms->textarea("css",$database->campaign->data->css,3,80));
    if (array_key_exists("css",$forms->error_text)) $text=array_merge($text,$forms->error_text['css']);
    $results[]="<td width='70%'>" . implode("<br>",$text)  . "</td>";
    $results[]="</tr>";
    $results[]="</table>";
    return $results;
}
function tab_js() {
global $database, $forms, $menu;
	$results=array();
	$results[]="<table class='large noborder'>";
    $results[]="<tr>";
    $results[]="<td width='30%'>Additional JavaScript</td>";
    $text=array($forms->textarea("js",$database->campaign->data->js,3,80));
    if (array_key_exists("js",$forms->error_text)) $text=array_merge($text,$forms->error_text['js']);
    $results[]="<td width='70%'>" . implode("<br>",$text)  . "</td>";
    $results[]="</tr>";
    $results[]="</table>";
    return $results;
}
function campaign_row($row,$content) {
global $database, $forms, $menu;
	$results=array();
    $results[]="<div id=content_div{$row}>";
    $data=array();
    if (strlen($content->name)) {
    	$data[]=$content->access . ":";
    	$data[]=$content->name;
	  } else {
    	$data[]="Empty: New Content";
	}
	if (sizeof($content->error)) $data[]="(" . sizeof($content->error) . " Error(s) Detected)";
//if (sizeof($content->error)) $data[]=implode(", ",array_keys($content->error));
    $results[]="<h3 id=content_label{$row}>" . implode(" ",$data) . "</h3>";
    $results[]="<div>";
    $results[]="<table class='standard noborder'>";
    if ($content->id) {
	    $results[]="<tr>";
	    $results[]="<td>Document ID</td>";
	    $results[]="<td>" . $content->id . "</td>";
	    $results[]="</tr>";
    }
    $results[]="<tr>";
    $results[]="<td width='30%'>Delete?</td>";
    $results[]="<td width='70%'>" . $forms->checkbox("content_delete{$row}",1,0,array("onclick"=>"content_row_del(this," . fn_escape($row) . "," . fn_escape($content->id) . ");")) . "</td>";
    $results[]="</tr>";
    $results[]="<tr>";
    $results[]="<td width='30%'>Title</td>";
    $results[]="<td width='70%'>" . $forms->text("content_name{$row}",$content->name,20,30,"",array("onfocusout"=>"content_label(" . fn_escape($row) . ");")) . "</td>";
    $results[]="</tr>";
    $results[]="<tr>";
    $results[]="<td>Description</td>";
    $text=array($forms->textarea("content_description{$row}",$content->description,3,60));
    if (array_key_exists("content_description{$row}",$content->error)) $text[]=$content->error["content_description{$row}"];
    $results[]="<td>" . implode("<br>",$text) . "</td>";
    $results[]="</tr>";
    $results[]="<tr>";
    $results[]="<td>Access</td>";
    $results[]="<td>" . $forms->select("content_access{$row}",$database->campaign->constant->access,$content->access,FALSE,array("onfocusout"=>"content_label(" . fn_escape($row) . ");")) . "</td>";
    $results[]="</tr>";
    $results[]="<tr>";
    $results[]="<td>" . (($content->meta->name)  ? "Replace File" : "Upload File") . "</td>";
    $text=array($forms->file("content_file{$row}"));
	if (array_key_exists("content_file{$row}",$forms->error_text)) $text[]=$forms->error_text["content_file{$row}"];
    if ($content->meta->name) $text[]="Name: " . $content->meta->name;
    if ($content->revised) $text[]="Revised: " . date("m/d/Y h:i A",$content->revised);
    $results[]="<td>" . implode("<br>",$text)  . "</td>";
    $results[]="</tr>";
    $results[]="</table>";
	$results[]=$forms->hidden("content_content_id{$row}",$content->id);
    $results[]="</div> <!-- end row {$row} -->";
    $results[]="</div> <!-- end group {$row} -->";
	return implode($results);
}
class campaign_content_class {
	var $id=0;
    var $name;
    var $description;
    var $access;
    var $error=array();
    var $meta;
    var $content;
    function __construct() {
		$this->meta=new file_meta_class();
        if (func_num_args()) $this->id=func_get_arg(0);
    }
    function file($data) {
		$this->id=$data->id;
        $this->name=$data->name;
        $this->description=$data->description;
        list($this->access)=preg_split("/\:/",$data->record_item);
        $this->revised=$data->revised;
		$this->content=$data->content;
        $this->meta=$data->meta;
    }
    function upload($row,$item) {
	global $database, $forms, $menu;
    	$field="content_file{$row}";
        if ($_FILES[$field]['name']) {
	        $this->content="";
	        $this->error=array();
	        $this->revised=0;
	        $this->meta->name=$_FILES[$field]['name'];
	        $this->meta->mime=$_FILES[$field]['type'];
	        switch (TRUE) {
	          case (intval($_FILES[$field]['error'])):
	            $this->error["content_file{$item}"]="Upload error: " . $file['error'];
	            break;
	          case (!intval($_FILES[$field]['size'])):
	            $this->error["content_file{$item}"]="Empty file";
	            break;
	          case (!preg_match("/(htm|html|txt)$/i",$_FILES[$field]['name'])):
	            $this->error["content_file{$item}"]="Invalid file type: " . $_FILES[$field]['name'];
	            break;
	          default:
	            $this->content=file_get_contents($_FILES[$field]['tmp_name']);
	        }
		}
		if ( (!strlen($this->description)) && (!strlen($this->meta->name)) ) $this->error["content_description{$item}"]="Record must contain either description or file";
    }
}
?>