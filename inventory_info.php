<?php
/*
http://localhost/inventory_info.php?action=specials&sku=HBLT-3-0.50-S&prj=alignment_pins/ball_lock_pins/parts/hbl_asmtab.prj
*/
require_once "scs_header.php";
require_once "classes/category.php";
require_once "classes/subcategory.php";
require_once "classes/inventory.php";
$forms->report['action']=strtolower($_REQUEST['action']);
$forms->report['prj']=trim($_REQUEST['prj']);
$forms->report['sku']=trim($_REQUEST['sku']);
$database->inventory->read($forms->report['sku'],"sku");
if ($forms->report['action']=="price") {
	$menu->head(TRUE);
    if ($database->inventory->meta->rows) print "<p class=large>" . $database->inventory->msrp->output_simple . "</p>\n";
	$menu->copyright();
	die;
}
switch (TRUE) {
  case ($database->inventory->meta->rows):
	$database->subcategory->read($database->inventory->data->subcategory_id);
    break;
  case ($forms->report['prj']):
	$database->subcategory->read($forms->report['prj'],"prj");
    break;
}
$query_documents=array();
if ($database->inventory->meta->rows) {
	$query_documents['stock_content']=array();
	$query_documents['stock_content'][]=new query_where("content.record_type","=","inventory","or");
	$query_documents['stock_content'][]=new query_where("content.record_id","=",$database->inventory->data->id);
	$query_documents['stock_content'][]=new query_where("content.record_item","=",$forms->report['action']);
	if ($database->inventory->data->documents[ $forms->report['action' ] ]) {
	    $query_documents['stock_document']=array();
	    $query_documents['stock_document'][]=new query_where("content.record_type","=","document","or");
	    $query_documents['stock_document'][]=new query_where("content.record_id","=",$database->inventory->data->documents[ $forms->report['action'] ]);
    }
}
if ($database->subcategory->meta->rows) {
	$query_documents['subcategory_content']=array();
	$query_documents['subcategory_content'][]=new query_where("content.record_type","=","subcategory","or");
	$query_documents['subcategory_content'][]=new query_where("content.record_id","=",$database->subcategory->data->id);
	$query_documents['subcategory_content'][]=new query_where("content.record_item","=",$forms->report['action']);
	if ($database->subcategory->data->documents[ $forms->report['action'] ]) {
	    $query_documents['subcategory_document']=array();
	    $query_documents['subcategory_document'][]=new query_where("content.record_type","=","document","or");
	    $query_documents['subcategory_document'][]=new query_where("content.record_id","=",$database->subcategory->data->documents[ $forms->report['action'] ]);
    }
}
$query_where=array();
$query_where[]=new query_where("content.language_code","in",array("EN",$_SESSION['user']->language_code));
$query_where[]=new query_where($database->where($query_documents,"omit"),"","","and_group");
$query =
	"select \n" .
	"case \n" .
    "when record_type='inventory' then 1 \n" .
    "when record_type='subcategory' then 2 \n" .
    "else 3 \n" .
    "end as 'sort_type', \n" .
    "if (content.language_code='EN',2,1) as sort_language, \n" .
	"content.* from content \n" .
    $database->where($query_where) .
    "order by sort_type,sort_language";
$database->content->query($query);

if ($database->content->meta->rows) {
	$database->content->fetch(TRUE);
	$database->content->free_result();
}
if ($database->content->meta->rows) {
	$visible=TRUE;
  } else {
	$visible=FALSE;
}
$results=array();
switch (TRUE) {
  case (!$database->content->meta->rows):
    break;
  case ($database->content->data->filetype == "html"):
	echo $database->content->data->content;
    break;
  case ($database->content->data->filetype == "image"):
	$results[]="<img src=" . fn_url("/scs_file.php",array("content_id"=>$database->content->encode($database->content->data->id))) . "'>\n";
    break;
  case ($database->content->data->meta->mime == "application/x-shockwave-flash"):
	$results[]="<object>";
	$results[]="<param name='movie' value='harcourt.swf'>";
	$results[]="<embed ";
	$results[]="src='" . fn_url("/scs_file.php",array("content_id"=>$database->content->encode($database->content->data->id))) . "'";
	$results[]="type='application/x-shockwave-flash'";
	$results[]="pluginspace='http://www.macromedia.com/go/getflashplayer'>";
	$results[]="</embed>";
	$results[]="</object>";
    break;
}
$menu_tab=array("features"=>"myF_tab_item_0","applications"=>"myF_tab_item_1","specials"=>"myF_tab_item_2");
$results[]="<script language=javascript>\n";
$results[]="function menu_title(id,visible) {";
$results[]="obj=parent.document.getElementById(id);";
$results[]="if (!obj) return;";
$results[]="if (visible) {";
$results[]="obj.style.display = 'inline';";
$results[]="} else {";
$results[]="obj.style.display = 'none';";
$results[]="}";
$results[]="}";
$results[]="menu_title('" . $menu_tab[$forms->report['action']] . "','$visible');";
$results[]="</script>";

$menu->head(TRUE);
print implode("\n",$results);
$menu->copyright();
?>