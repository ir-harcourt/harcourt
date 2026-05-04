<?php
include_once "scs_header.php";
include_once "classes/category.php";
include_once "classes/subcategory.php";
include_once "classes/document.php";
$menu->access();
$menu->title="Subcategory Manager";
$database->connect();
$menu->messages[]=$menu->title;
$report_images=array("Thumbnail","Sketch","Both");
$image_mime=array("image/gif"=>".gif","image/pjpeg"=>".jpg","image/x-png"=>".png");
$report['action']=$_POST['action'];
$report['record_id']=$_POST['record_id'];
$report['report_category_id']=$_POST['report_category_id'];
$report['report_images']=$_POST['report_images'];
if ($report['record_id']) $database->subcategory->read($report['record_id']);

switch (TRUE) {
  case ($report['action'] == ""):
    break;
  case ($report['action'] =="maintain"):
    if (!$database->subcategory->data->id) {
    	$database->subcategory->data=new subcategory_data_class();
        $database->subcategory->data->category_id=$report['report_category_id'];
    }
    $_SESSION['subcategory']=TRUE;
    break;
  case ($report['action'] == "delete"):
    $database->subcategory->delete($report['record_id']);
    $menu->messages[]="subcategory " . $database->subcategory->data->code . " deleted";
    break;
  case ($report['action'] == "list"):
    if (!$report['report_category_id']) $menu->errors['report_category_id']=$menu->background['red'];
    break;
  case (!isset($_SESSION['subcategory'])):
	break;
  case ($report['action'] == "cancel"):
    $menu->messages[]="Request cancelled";
    unset($_SESSION['subcategory']);
    break;
  case ($report['action'] == "update"):
	$database->subcategory->data->category_id=$_POST['category_id'];
	$database->subcategory->data->name=trim($_POST['name']);
	$database->subcategory->data->image_url=subcategory_image_load("image_upload","image_delete","subcategory",$report['record_id'],$database->subcategory->data->image_url);
	$database->subcategory->data->sketch_url=subcategory_image_load("sketch_upload","sketch_delete","sketch",$report['record_id'],$database->subcategory->data->sketch_url);
	$database->subcategory->data->search_words=trim($_POST['search_words']);
	$database->subcategory->data->prj=trim($_POST['prj']);
	$database->subcategory->data->parameters=explode("\n",trim($_POST['parameters']));
    foreach ($database->subcategory->constant->documents as $key => $value) {
		if (!isset($database->subcategory->data->documents[$key])) $database->subcategory->data->documents[$key]=new document_inventory_class();
	    $field_document_id=base64_encode($key . "\t" . "document_id");
	    $field_html=base64_encode($key . "\t" . "html");
		switch (TRUE) {
		  case ($_POST[$field_document_id]):
			$database->subcategory->data->documents[$key]->document_id=$_POST[$field_document_id];
			$database->subcategory->data->documents[$key]->html="";
			break;
		  case ($_POST[$field_html]):
			$database->subcategory->data->documents[$key]->html=trim($_POST[$field_html]);
			$database->subcategory->data->documents[$key]->document_id=0;
			break;
          default:
			$database->subcategory->data->documents[$key]->document_id=0;
			$database->subcategory->data->documents[$key]->html="";
        }
    }
	$database->subcategory->data->active=intval($_POST['active']);
	if (!$database->subcategory->data->category_id) $menu->errors['category_id']=$menu->background['red'];
	if (!$database->subcategory->data->name) $menu->errors['name']=$menu->background['red'];
    if (($database->subcategory->data->image_url) && (!file_exists($database->subcategory->data->image_url))) $menu->errors['image_upload']=$menu->background['red'];
    if (($database->subcategory->data->sketch_url) && (!file_exists($database->subcategory->data->sketch_url))) $menu->errors['sketch_upload']=$menu->background['red'];
	if (sizeof($menu->errors)) break;
    $database->subcategory->update($database->subcategory->meta->rows);
    if (mysql_errno()) {
        $menu->errors['name']=$menu->background['red'];
        $menu->messages[]=mysql_error();
	  } else {
    	$menu->messages[]="Record maintained";
		if (!$report['record_id']) {
			$database->subcategory->read($database->subcategory->data->id);
			$database->subcategory->data->image_url=subcategory_image_rename($database->subcategory->data->id,$database->subcategory->data->image_url);
			$database->subcategory->data->sketch_url=subcategory_image_rename($database->subcategory->data->id,$database->subcategory->data->sketch_url);
			$database->subcategory->update(TRUE);
        }
		unset($_SESSION['subcategory']);
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
function fn_image() {
	return;
	category=document.getElementById('category_id');
	folder=category.options[category.selectedIndex].text.toLowerCase();
    file=document.scs_form.name.value.toLowerCase();
    url="images/" + folder.replace(/[/()]/g,"") + "/" + file.replace(/[/()]/g,"") + ".jpg";
	document.scs_form.sketch_url.value=url.replace(/ /g,"_");
}
</script>
<?php
print "<form name=scs_form enctype='multipart/form-data' method=post action='" . $_SERVER['PHP_SELF'] . "'>\n";
print "<input type=hidden name=action>\n";
print "<input type=hidden name=record_id>\n";

switch (TRUE) {
  case ($report['action'] == "maintain"):
  case (($report['action'] == "update") && (sizeof($menu->errors))):
  	print "<input type=hidden name='report_category_id' "  . fn_html_value($report['report_category_id']) . ">\n";
  	print "<input type=hidden name='report_images' "  . fn_html_value($report['report_images']) . ">\n";
	print "<table class='large noborder'>\n";
    print "<tr>\n";
    print "<td width=30%>Product Category</td>\n";
    print "<td width=70%>";
    if (!$report['record_id']) {
    	$script="fn_image();";
	  } else {
      	$script="";
	}
	$database->category->select($database->subcategory->data->category_id,"category_id",$script);
    print "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<tr>\n";
    print "<td>Name</td>\n";
    print "<td>" . $menu->textarea("name",$database->subcategory->data->name,3,60,$script) . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Image URL";
    print "</td>\n";
    print "<td>";
	print "<input type=file name=image_upload id=image_upload size=40 " . $menu->errors['image_upload'] . ">";
    if ($menu->errors_text['image_upload']) print " <br>" . $menu->errors_text['image_upload'];
    print "</td>\n";
    print "</tr>\n";
    if ($database->subcategory->data->image_url) {
		print "<tr>\n";
        print "<td>Delete? " . $menu->checkbox("image_delete",0,1) . "</td>\n";
    	print "<td>" . $menu->image($database->subcategory->data->image_url,$database->subcategory->data->name,400) . "</td>\n";
        print "</tr>\n";
	}
    print "<tr>\n";
    print "<td>Sketch URL</td>\n";
    print "<td>";
	print "<input type=file name=sketch_upload id=sketch_upload size=40 " . $menu->errors['sketch_upload'] . ">";
    if ($menu->errors_text['sketch_upload']) print " <br>" . $menu->errors_text['sketch_upload'];
    print "</td>\n";
    print "</tr>\n";
    if ($database->subcategory->data->sketch_url) {
		print "<tr>\n";
        print "<td>Delete? " . $menu->checkbox("sketch_delete",0,1) . "</td>\n";
    	print "<td>" . $menu->image($database->subcategory->data->sketch_url,$database->subcategory->data->name,400) . "</td>\n";
        print "</tr>\n";
	}
    print "<tr>\n";
    print "<td>Partsolutions PRJ</td>\n";
    print "<td>" . $menu->textarea("prj",$database->subcategory->data->prj,4,80) . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Inventory Parameters</td>\n";
    print "<td>" . $menu->textarea("parameters",implode("\n",$database->subcategory->data->parameters),sizeof($database->subcategory->data->parameters) + 4,40) . "</td>\n";
    print "</tr>\n";
    foreach ($database->subcategory->constant->documents as $key => $value) {
	    $field_document_id=base64_encode($key . "\t" . "document_id");
	    $field_html=base64_encode($key . "\t" . "html");
    	print "<tr><td colspan=2><br><b>$value</b></td></tr>\n";
        if ($key != "description") {
	        print "<tr>\n";
	        print "<td>Document ID</td>\n";
	        print "<td>\n";
	        print $database->document->select($database->subcategory->data->documents[$key]->document_id,$field_document_id);
	        print "</td>\n";
	        print "</tr>\n";
		}
    	print "<tr>\n";
        print "<td>HTML Text</td>\n";
		print "<td>" . $menu->textarea($field_html,$database->subcategory->data->documents[$key]->html,6,80) . "</td>\n";
        print "</tr>\n";
    }
    print "<tr>\n";
    print "<td>Search words</td>\n";
    print "<td>" . $menu->textarea("search_words",$database->subcategory->data->search_words,6,60) . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Active?</td>\n";
    print "<td>" . $menu->checkbox("active", $database->subcategory->data->active) . "</td>\n";
    print "</tr>\n";
    print "</table>\n";
	print "<p class='standard center'>\n";
	print "<input class=fbutton type=button value='Update' onclick=\"javascript:fn_action(this,'update','" . $database->subcategory->data->id . "','');\"> \n";
	print "<input class=fbutton type=button value='Cancel' onclick=\"javascript:fn_action(this,'cancel','','');\">\n";
	print "</p>\n";
  	break;
  default:
	print "<table class='large noborder'>\n";
    print "<tr>\n";
    print "<th>Category</th>\n";
    print "<th>Images</th>\n";
    print "<th>&nbsp;</th>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td class=center>\n";
	$database->category->select($report['report_category_id'],"report_category_id");
    print "</td>\n";
    print "<td class=center>" . $menu->select("report_images",$report_images,$report['report_images']) . "</td>\n";
    print "<td class=center><input class=fbutton type=button value='List' onclick=\"javascript:fn_action(this,'list','','');\">\n";
    print "</tr>\n";
    print "</table>\n";
    $colspan=3;
	print "<table class='large border'>\n";
    print "<caption>&nbsp;</caption>\n";
    print "<tr>\n";
    switch ($report['report_images']) {
      case "Thumbnail":
      case "Both":
    	$colspan++;
		print "<th width=100>Thumbnail</th>\n";
    }
    switch ($report['report_images']) {
      case "Sketch":
      case "Both":
    	$colspan++;
		print "<th width=100>Sketch</th>\n";
    }
    print "<th width=40%>Name</th>\n";
    print "<th width=50%>Description</th>\n";
    print "<th width=10%>Del?</th>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td colspan=$colspan><a href=\"javascript:fn_action(this,'maintain','0','');\">Add new</a></td>\n";
    print "</tr>\n";
    if (($report['action'] == "list") && (!sizeof($menu->errors))) {
 		$query_where=array();
        if ($report['report_category_id']) $query_where[] = new query_where("category_id","=",$report['report_category_id']);
	    $query =
	        "select * from subcategory \n" .
            $database->where($query_where) .
	        "order by category_id,name";
	    $database->subcategory->query($query);
	    while ($database->subcategory->fetch = mysql_fetch_array($database->subcategory->meta->handle)) {
	        $database->subcategory->fetch();
	        print "<tr>\n";
	        switch ($report['report_images']) {
	          case "Thumbnail":
	          case "Both":
	            print "<td>" . $menu->image($database->subcategory->data->image_url,$database->subcategory->data->name,100,100) . "</td>\n";
	        }
	        switch ($report['report_images']) {
	          case "Sketch":
	          case "Both":
	            print "<td>" . $menu->image($database->subcategory->data->sketch_url,$database->subcategory->data->name,100,100) . "</td>\n";
	        }
	        print
	            "<td>" .
	            "<a href=\"javascript:fn_action(this,'maintain','" . $database->subcategory->data->id . "','');\">" .
	            $database->subcategory->data->name .
	            "</a></td>\n";
	        print "<td>" .  $database->subcategory->data->documents['description']->html . "</td>\n";
	        print "<td class=center>";
	        if (!$database->subcategory->data->active) {
	            print "<input type=radio name='delete' onclick=\"javascript:fn_action(this,'delete','" . $database->subcategory->data->id . "','" . $database->subcategory->data->code . "');\">\n";
	        }
	        print "</td>\n";
	        print "</tr>\n";
	    }
	$database->subcategory->free_result();
    }
}
print "</table>\n";

print "</form>\n";
$database->disconnect();
$menu->copyright();
function subcategory_image_load($upload,$delete,$prefix,$id,$url) {
global $menu, $report, $image_mime;
    $image_url=$url;
    if ($id) {
    	$suffix=$id;
	  } else {
      	$suffix="_upload";
	}
    switch (TRUE) {
	  case ($_FILES[$upload]['name']):
		switch (TRUE) {
		  case ($_FILES[$upload]['error']):
			$menu->errors_text[$upload]="Upload error #" . $_FILES[$upload]['error'];
            break;
		  case (!$_FILES[$upload]['size']):
			$menu->errors_text[$upload]="Empty file";
            break;
		  case (!array_key_exists($_FILES[$upload]['type'],$image_mime)):
			$menu->errors_text[$upload]="Invalid file type: <b>" . $_FILES[$upload]['type'] . "</b>";
            break;
          default:
			$image_url="product_images/" . $prefix .  $suffix . $image_mime[$_FILES[$upload]['type']];
            if (file_exists($image_url)) unlink($image_url);
            move_uploaded_file($_FILES[$upload]['tmp_name'],$image_url);
        }
		break;
	  case ($_POST[$delete]):
      	if (file_exists($url)) unlink($url);
		$image_url="";
		break;
      case (!$id):
		foreach ($image_mime as $key => $value) {
			$test_url="product_images/" . $prefix . $suffix . $value;
            if (file_exists($test_url)) {
				$image_url=$test_url;
				break;
            }
        }
    }
    if ($menu->errors_text[$upload]) $menu->errors[$upload]=$menu->background['red'];
    return $image_url;
}
function subcategory_image_rename($id,$url) {
	switch (TRUE) {
	  case (!$url):
	  case (!file_exists($url)):
		$return_url="";
	  default:
		$return_url=str_replace("_upload",$id,$url);
        rename($url,$return_url);
	}
	return $return_url;
}
?>