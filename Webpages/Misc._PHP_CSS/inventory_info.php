<?php
include_once "/Webpages/Header_Footer/scs_header.php";
include_once "classes/category.php";
include_once "classes/subcategory.php";
include_once "classes/inventory.php";
include_once "classes/document.php";
$database->connect();
$report['action']=strtolower($_REQUEST['action']);
$report['prj']=trim($_REQUEST['prj']);
$report['sku']=trim($_REQUEST['sku']);

$database->inventory->read($report['sku'],"sku");
switch (TRUE) {
  case ($database->inventory->meta->rows):
	$database->subcategory->read($database->inventory->data->subcategory_id);
    break;
  case ($report['prj']):
	$database->subcategory->read($report['prj'],"prj");
    break;
}
switch (TRUE) {
  case ($database->inventory->data->documents[$report['action']]->document_id):
  case ($database->inventory->data->documents[$report['action']]->html):
	$document=$database->inventory->data->documents[$report['action']];
    break;
  case ($database->subcategory->data->documents[$report['action']]->document_id):
  case ($database->subcategory->data->documents[$report['action']]->html):
	$document=$database->subcategory->data->documents[$report['action']];
    break;
  default:
	$document=new document_inventory_class();
}
if ($document->document_id) {
	$database->document->read($document->document_id);
    switch (TRUE) {
      case (!$database->document->meta->rows):
      case (!$database->document->results->file):
      	$document->document_id=0;
        break;
	  case ($database->document->data->mime == "text/html"):
      	break;
      default:
      	$_SESSION[$report['action']]=$database->document->results->file;
    }
}
$visible=TRUE;
switch (TRUE) {
  case ($document->html):
    print $document->html;
    break;
  case (!$document->document_id):
	$visible=FALSE;
    break;
  case ($database->document->data->mime == "text/html"):
    print file_get_contents($database->document->results->file);
    break;
  case ($database->document->data->mime == "application/x-shockwave-flash"):
	print "<object>\n";
    print "<param name='movie' value='harcourt.swf'>\n";
    print
    	"<embed " .
        "src='/cmspage/cms" . $database->document->data->id . "' " .
        "type='application/x-shockwave-flash' " .
        "pluginspace='http://www.macromedia.com/go/getflashplayer'>\n";
    print "</embed>\n";
    print "</object>\n";
    break;
  default:
	print "<img src='/image_viewer.php?action=" . $report['action'] . "'>\n";
    exit;
}
$menu_tab=array("features"=>"myF_tab_item_0","applications"=>"myF_tab_item_1","specials"=>"myF_tab_item_2");
$menu->head(TRUE);

print "<script language=javascript>\n";
print "function menu_title(id,visible) {\n";
print "    obj=parent.document.getElementById(id);\n";
print "    if (visible) {\n";
print "        obj.style.display = 'inline';\n";
print "      } else {\n";
print "        obj.style.display = 'none';\n";
print "    }\n";
print "}\n";
print "menu_title('" . $menu_tab[$report['action']] . "','$visible');\n";
print "</script>\n";

$menu->copyright();
$database->disconnect();
?>