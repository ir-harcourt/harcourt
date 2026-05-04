<?php
require_once "scs_header.php";
require_once "map_rev3.php";
require_once "classes/configure.php";
$menu->configure=new configure_class();
switch (TRUE) {
  case (isset($_SERVER['HTTP_X_REQUESTED_WITH'])):
	$results=array();
    if ($menu->configure->smart_module($_REQUEST['subcategory_id'])) {
		$results['html']=$menu->configure->select_record_type("");
	  } else {
    	$results['html']="&nbsp;";
	}
    die( json_encode($results) );
}
$database->user->access("Administrator");
$forms->title("Smart SKU Pricing Utility");
$forms->report['action']=$_REQUEST['action'];
$forms->report['function']=$_REQUEST['function'];
$forms->report['subcategory_id']=$_REQUEST['subcategory_id'];
$forms->report['record_type']=$_POST['record_type'];
$forms->report['file_type']=$_POST['file_type'];
$forms->report['import_file']=$_POST['import_file'];
$forms->report['explain']=TRUE;
$forms->constant->record_type=array();
$menu->configure->smart_module($forms->report['subcategory_id']);
$menu->map=new map_class("pricing",$forms->report,$forms->report['subcategory_id'],( (!$forms->report['record_type']) ? "" : $forms->report['record_type']) );
if ($forms->report['subcategory_id']) {
	$database->subcategory->read($forms->report['subcategory_id']);
	$forms->message[]=$database->subcategory->data->name . " (" . $forms->report['subcategory_id'] . ")";
}
switch (TRUE) {
  case (!$forms->report['action']):
	break;
  case (!$forms->report['subcategory_id']):
	$forms->error('subcategory_id');
	break;
  case ($forms->report['function'] == "download"):
    $options=array();
	if ($forms->report['record_type']) $options['xls_worksheet']=$database->pricing->worksheet_name($forms->report['record_type']);
	$query_where=array();
	$query_where[]=new query_where("subcategory_id","=",$forms->report['subcategory_id']);
    if ( (!$forms->report['record_type']) && ($forms->report['file_type'] == ".xlsx") ) {
    	$options['xls_multiple']=TRUE;
        $query=array("select record_type as 'code',count(*) as 'count' from pricing");
        $query[]=$database->where($query_where);
        $query[]="group by code";
        $query[]="order by code";
	  } else {
      	$query="";
    }
	$menu->map->export($query,$options);
	break;
  case (($forms->report['function']=="review") && ($forms->report['action']=="prelist")):
  case (($forms->report['function']=="upload") && ($forms->report['action']=="prelist")):
  	if (!$forms->report['record_type']) {
    	$forms->error('record_type');
	  } else {
    	if (!$menu->map->upload("upload",array("xls_worksheet_name"=>$database->pricing->worksheet_name($forms->report['record_type'])))) $forms->error('upload');
	}
    if ($menu->map->message) $forms->message[]=$menu->map->message;
	break;
   case (($forms->report['function']=="review") && ($forms->report['action']=="update")):
  case (($forms->report['function']=="upload") && ($forms->report['action']=="update")):
	$forms->message[]=$menu->map->import();
  	break;
}
$menu->head();
$forms->message();
?>
<script>
jQuery(document).ready(function() {
    utility_screen();
});
function fn_action(action) {
	if ( (action=='update') && (!confirm("Continue?")) ) return;
	document.scs_form.action.value=action;
	document.scs_form.submit();
}
function fn_record_type(record_type) {
    jQuery("#action").val("prelist");
    jQuery("#record_type").val(record_type);
	document.scs_form.submit();
}
function fn_subcategory() {
	jQuery.ajax({
	    data: {action: "ajax", subcategory_id: jQuery("#subcategory_id").val() },
	    dataType: 'json',
	    success:function(data){
			jQuery('#record_type_td').html(data['html']);
	   }
	});
	utility_screen();
}
function utility_screen() {
	var upload_class=0;
    var download_class=0;
    switch (jQuery("#function").val()) {
      case "download":
      	download_class=1;
        break;
      case "review":
      case "upload":
      	upload_class=1;
        break;
    }
    if (jQuery("#subcategory_id").val()) {
    	jQuery("#record_type_tr").show();
	  } else {
    	jQuery("#record_type_tr").hide();
    }
	if (upload_class) {
    	jQuery(".upload_class").show();
	  } else {
    	jQuery(".upload_class").hide();
	}
	if (download_class) {
    	jQuery(".download_class").show();
	  } else {
    	jQuery(".download_class").hide();
	}
}
</script>
<?php
print $forms->open(array("data"=>TRUE));
print $forms->hidden("action");
switch (TRUE) {
  case (($forms->report['action']=="prelist") && ($forms->report['function']=="review") && (!sizeof($forms->error))):
  case (($forms->report['action']=="prelist") && ($forms->report['function']=="upload") && (!sizeof($forms->error))):
	$menu->map->upload_html();
	break;
  case (($forms->report['action']=="prelist") && ($forms->report['function']=="pending") && (!sizeof($forms->error))):
	$menu->map->pending_html();
	break;
  case (($forms->report['action']=="update") && ($forms->report['function']=="review")):
  case (($forms->report['action']=="update") && ($forms->report['function']=="upload")):
	$menu->map->import_html();
	break;
  default:
	$menu->tabs=new jquery_tab_class();
	$menu->tabs->item("Input",tab_input());
	$menu->tabs->item($database->subcategory->data->name,tab_summary());
	tab_detail();
	$menu->tabs->output();
}
print $forms->close();
$menu->copyright();
function tab_input() {
global $database, $forms, $menu;
	$results=array();
	$results[]="<table class='standard noborder'>";
	$results[]="<tr>";
	$results[]="<td width='20%'>Action</td>";
    $results[]="<td width='80%'>" . $forms->select("function",$menu->map->function_list,$forms->report['function'],TRUE,array("onchange"=>"utility_screen();")) . "</td>";
    $results[]="</tr>";
	$results[]="<tr>";
	$results[]="<td>Subcategory</td>";
	$results[]="<td>" . $database->subcategory->select($forms->report['subcategory_id'],array("output"=>"smart"),array("onchange"=>"fn_subcategory();")) . "</td>";
    $results[]="</tr>";
    $results[]="<tr id=record_type_tr>";
    $results[]="<td>Record type</td>";
    $results[]="<td id='record_type_td'>" . $menu->configure->select_record_type($forms->report['record_type']) . "</td>";
    $results[]="</tr>";
	$results[]="<tr class='upload_class'>";
	$results[]="<td>Import File</td>";
	$results[]="<td>". $forms->file('upload',40) . "</td>";
	$results[]="</tr>";
	$results[]="<tr class='download_class'>";
	$results[]="<td>Export file type</td>";
	$results[]="<td>" . $forms->select("file_type",$menu->map->file_type_list,$forms->report['file_type'],FALSE,array("onchange"=>"pricing_screen();")) . "</td>";
	$results[]="</tr>";
	$results[]="</table>";
	$results[]="<p>" . $forms->button("Go!",array("onclick"=>"fn_action('prelist');")) . "</p>";
    return  $results;
}
function tab_summary() {
global $database, $forms, $menu;
	if ( ($forms->report['function'] != "explain") || (sizeof($forms->error)) ) return;
    $menu->tabs->landing_tab();
	$results=array();
    $query=array();
    $query[]="select";
    $query[]="record_type as 'record_type',";
    $query[]="count(*) as 'count',";
    $query[]="update_timestamp as 'update_timestamp'";
    $query[]="from pricing";
    $query[]="where subcategory_id=" . fn_escape($forms->report['subcategory_id']);
    $query[]="group by record_type";
    $database->temp->query($query);
    $update_timestamp=0;
    $count_array=array();
    while ($database->temp->fetch = $database->temp->fetch_array()) {
        $update_timestamp=$database->temp->fetch['update_timestamp'];
        $count_array[$database->temp->fetch['record_type']]=$database->temp->fetch['count'];
    }
    $database->pricing->free_result();
    $results[]="<table class='small border'>";
    $results[]="<caption class='standard left'><br>Last update: " . ( ($update_timestamp) ? date("F j, Y g:i A",$update_timestamp) : "Never") . "</caption>";
    $results[]="<tr>";
    $results[]="<th>Record Type</th>";
    $results[]="<th>Name</th>";
    $results[]="<th># Records</th>";
    $results[]="<th>Codes</th>";
    $results[]="<th>Price(s)</th>";
    $results[]="</tr>";
    foreach ($menu->configure->module->pricing->item as $record_type => $item) {
        $field=array();
        $price=array();
        $i=0;
        foreach ($item->field as $key => $map) {
            $field[]="code{$i}: {$key}";
            $i++;
        }
        $i=0;
        foreach ($item->price_name as $price_name) {
            $price[]="price{$i}: {$price_name}";
            $i++;
        }
        if ($count_array[$record_type]) {
            $bg_color="";
          } else {
            $bg_color=$forms->background->yellow;
        }
        $results[]="<tr $bg_color>";
        $results[]="<td><a href=\"javascript:fn_record_type(" . fn_escape($record_type) . ");\">{$record_type}</a></td>";
        $results[]="<td>" . $item->name . "</td>";
        $results[]="<td class=center>" . number_format($count_array[$record_type]) . "</td>";
        $results[]="<td>" . implode("<br>",$field) . "</td>";
        $results[]="<td>" . implode("<br>",$price) . "</td>";
        $results[]="</tr>";
    }
    $results[]="</table>";
    return $results;
}
function tab_detail() {
global $database, $forms, $menu;
	if ( ($forms->report['function'] != "explain") || (sizeof($forms->error)) || (!$forms->report['record_type']) ) return;
    $menu->tabs->landing_tab();
    $obj=$menu->configure->module->pricing->item[$forms->report['record_type']];
    $query=array("select * from pricing");
    $query[]="where subcategory_id=" . fn_escape($forms->report['subcategory_id']);
    $query[]="and record_type=" . fn_escape($forms->report['record_type']);
    $query[]="order by options";
    $database->pricing->query($query);
    $results=array();
    $results[]="<table class='standard border tablesorter'>";
    $results[]="<caption class=left><br>Record type: <b>" . $forms->report['record_type'] . "</b></caption>";
    $results[]="<thead>";
    $results[]="<tr>";
    if (sizeof($obj->field)) $results[]="<th colspan=" . sizeof($obj->field) . ">Code(s)</th>";
    $results[]="<th colspan=" . sizeof($obj->price_name) . ">Price(s)</th>";
    $results[]="</tr>";
    $results[]="<tr>";
    foreach ($obj->field as $key => $value) {
        $results[]="<th>{$key}</th>";
    }
    foreach ($obj->price_name as $price_name) {
        $results[]="<th>{$price_name}</th>";
    }
    $results[]="</tr>";
    $results[]="</thead>";
    $results[]="<tbody>";
	while ($database->pricing->fetch = $database->pricing->fetch_array()) {
		$database->pricing->fetch();
        $results[]="<tr>";
        if (sizeof($obj->field)) {
            parse_str($database->pricing->data->options,$options);
            foreach ($obj->field as $field_name => $field_obj) {
                switch (TRUE) {
                  case (!array_key_exists($field_name,$options)):
                  case (!strlen($options[$field_name]));
                    $results[]="<td " . $forms->background->yellow . ">Missing</td>";
                    break;
                  case ( ($field_obj->type == "list") && (!array_key_exists($options[$field_name],$field_obj->list)) ):
                    $results[]="<td " . $forms->background->yellow . ">Invalid: " . $options[$field_name] . "</td>";
                    break;
                  case ($field_obj->type == "list"):
                    $results[]="<td>" . $field_obj->list[ $options[$field_name] ] . "</td>";
                    break;
                  default:
                    $results[]="<td>" . $options[$field_name] . "</td>";
                }
           }
    	}
        if ($database->pricing->data->price_type) {
            $results[]="<td class=center rowspan=" . sizeof($obj->price_name) . ">" . $database->pricing->constant->price_type[$database->pricing->data->price_type] . "</td>";
          } else {
            for ($i=0; $i < sizeof($obj->price_name); $i++) {
                $results[]="<td class=right>$" . number_format($database->pricing->data->price[$i],2) . "</td>";
            }
        }
        $results[]="</tr>";
	}
    $results[]="</tbody>";
    $results[]="</table>";
    $database->pricing->free_result();


    $title=$obj->name;
	$menu->tabs->item($title,$results);

}
?>