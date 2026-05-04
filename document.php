<?php
require_once "scs_header.php";
$database->user->access("Administrator");
$forms->title("Document Manager");
$forms->report['action']=$_REQUEST['action'];
$forms->report['record_id']=intval($_POST['record_id']);
switch (TRUE) {
  case ($forms->report['action' ]== "ajax"):
	$database->document->read(intval($_POST['ajax_document_id']));
    if ($database->document->meta->rows) {
		$forms->report['action']="maintain";
        $forms->report['record_id']=$database->document->data->id;
	  } else {
		$forms->error('ajax_document_id');
    }
    break;
  case ($forms->report['record_id']):
	$database->document->read($forms->report['record_id']);
  	break;
  default:
	$database->document->data=new document_data_class();
}
switch ($forms->report['action']) {
  case "":
    break;
  case "cancel":
    $forms->message[]="Request cancelled";
	if (isset($_SESSION['document_maintain'])) unset($_SESSION['document_maintain']);
    break;
  case "maintain":
  	if (!$forms->report['record_id']) {
        $database->document->data->parent_id=$_POST['record_parent_id'];
    }
	$_SESSION['document_maintain']=array();
    $query_where[]=new query_where("record_type","=","document");
    $query_where[]=new query_where("record_id","=",$forms->report['record_id']);
    $query=
    	"select * from content \n" .
        $database->where($query_where);
    $database->content->query($query);
    while ($database->content->fetch = $database->content->fetch_array() ) {
		$database->content->fetch();
		$_SESSION['document_maintain'][$database->content->data->language_code]=$database->content->data;
	}
	$database->content->free_result();
	break;
  case "delete":
	$database->document->delete($forms->report['record_id']);
    $query=
    	"delete from content \n" .
        "where record_type='document' \n" .
        "and record_id=" . fn_escape($forms->report['record_id']);
	$database->temp->query($query);
    $forms->message[]="Document #" . $database->document->data->id . " deleted";
    break;
  case "update":
	$database->document->data->parent_id=$_POST['parent_id'];
	$database->document->data->active=$_POST['active'];
    foreach ($menu->language as $language) {
	    $field_name=base64_encode($language->code . "\t" . "name");
	    $field_description=base64_encode($language->code . "\t" . "description");
	    $field_file=base64_encode($language->code . "\t" . "file");
	    $field_delete=base64_encode($language->code . "\t" . "delete");
		if (array_key_exists($language->code,$_SESSION['document_maintain'])) {
        	$content=$_SESSION['document_maintain'][$language->code];
		  } else {
			$content=new content_data_class();
            $content->language_code=$language->code;
            $content->revised=strtotime("now");
		}
		$content->name=trim($_POST[$field_name]);
		$content->description=trim($_POST[$field_description]);
		switch (TRUE) {
          case (isset($_POST[$field_delete])):
			$database->content->fileinfo($content);
            break;
		  case (!$_FILES[$field_file]['name']):
			break;
	      case (!intval($_FILES[$field_file]['size'])):
			$forms->error($field_file,"Empty file");
	        break;
	      case ($_FILES[$field_file]['error']):
	        $forms->error($field_file,"Error: " . $_FILES[$field_file]["error"]);
	        break;
		  default:
            if (file_exists("scs.tmp")) unlink("scs.tmp");
			move_uploaded_file($_FILES[$field_file]['tmp_name'],"scs.tmp");
			$content->content=trim(file_get_contents("scs.tmp"));
//			if (mb_detect_encoding($content->content) == "UTF-8") $content->content=utf8_decode($content->content);
			unlink("scs.tmp");
			$database->content->fileinfo($content,$_FILES[$field_file]['name']);
		}
        $_SESSION['document_maintain'][$language->code]=$content;
        switch (TRUE) {
		  case($language->code=="EN"):
			$database->document->data->name=$content->name;
			$database->document->data->description=$content->description;
            break;
        }
        switch (TRUE) {
		  case ( (!$content->name) && ($language->code=="EN") ):
		  case ( (!$content->name) && ($content->meta->name) && (!$database->document->data->parent_id) ):
			$forms->error($field_name);
        }
        switch (TRUE) {
		  case (!$database->document->data->parent_id):
		  case (array_key_exists($field_file,$forms->error)):
			break;
		  case ( ($content->name) && (!$content->meta->name) ):
			$forms->error($field_file,"Content required when name supplied");
        }
        if ( (!$content->name) && ($content->description) ) $forms->error($field_description);
    }
	if (sizeof($forms->error)) break;
    $database->document->update($database->document->meta->rows);
    if ($database->document->meta->error) $forms->error('name','',$database->document->meta->error);
	if (sizeof($forms->error)) break;
    foreach ($_SESSION['document_maintain'] as $content) {
        $database->content->data=$content;
        $database->content->data->record_type="document";
        $database->content->data->record_id=$database->document->data->id;
        switch (TRUE) {
		  case ( ($content->name) || ($content->meta->name) ):
			$database->content->update($content->id);
          	break;
		  case ($content->id):
			$database->content->delete($content->id);
          	break;
        }
    }
	unset($_SESSION['document_maintain']);
	$forms->message[]="Record maintained";
	break;
}
$menu->head();
print $forms->message();
?>
<script type='text/javascript'>
function fn_action(obj,action,id,parent_id,name) {
	if (action=='delete') {
	    if (!confirm("Delete: Document#" + id + " " + name)) {
	        obj.checked=false;
            return;
	    }
    }
	document.scs_form.action.value=action;
	document.scs_form.record_id.value=id;
	document.scs_form.record_parent_id.value=parent_id;
	document.scs_form.submit();
}
function fn_ajax_select() {
	obj=document.scs_form.ajax_document_id;
    if (!obj.value) {
		alert('Please select a document #');
        obj.focus();
        return;
    }
	document.scs_form.action.value='ajax';
	document.scs_form.submit();

}
</script>
<?php
print $forms->open(array("data"=>TRUE));
print $forms->hidden("action");
print $forms->hidden("record_id");
print $forms->hidden("record_parent_id",47);
switch (TRUE) {
  case ($forms->report['action'] == "maintain"):
  case (($forms->report['action'] == "update") && (sizeof($forms->error))):
	$menu->tabs=new jquery_tab_class();
	if (isset($_SESSION['scscpq_wp'])) $menu->tabs->meta->options->lineheight="auto";    
    foreach ($menu->language as $language) {
		$menu->tabs->item($language->name,tab_language($language));
	}
    $menu->tabs->output();
	print
    	"<p class='standard'>" .
        $forms->button("Update",array("onclick"=>"fn_action(this,'update'," . fn_escape($database->document->data->id) . ",'','');")) .
        " " . $forms->button("Cancel",array("onclick"=>"fn_action(this,'cancel','','','');")) . "</p>\n";
    break;
  default:
	$query =
    	"select \n" .
        "(case \n" .
        "when parent_id=0 then concat('0',name) \n" .
        "else concat('1',name) \n" .
        "end) as sort_key, \n" .
        "document.* from document \n" .
        "order by sort_key";
    $database->document->query($query);
    $parents=array();
  	$documents=array();
    while ($database->document->fetch = $database->document->fetch_array() ) {
		$database->document->fetch();
        if ($database->document->data->parent_id) {
        	$parent_id=$database->document->data->parent_id;
		  } else {
        	$parent_id=$database->document->data->id;
		}
        if (!array_key_exists($parent_id,$parents)) $parents[$parent_id]=array();
		$parents[$parent_id][]=$database->document->data->id;
		$database->document->data->contents=array();
		$documents[$database->document->data->id]=$database->document->data;
	}
    $database->document->free_result();
    $query=
		"select \n" .
        "if (language_code='EN','1','2') as 'sort_key', \n" .
        "language_code,record_id,id \n" .
		"from content \n" .
        "where record_type='document' \n" .
        "order by sort_key,record_id";
	$database->content->query($query);
    while ($database->content->fetch = $database->content->fetch_array() ) {
		$database->content->fetch();
        if (array_key_exists($database->content->data->record_id,$documents)) $documents[$database->content->data->record_id]->contents[$database->content->data->language_code]=$database->content->data->id;
		$documents[$database->document->data->id]=$database->document->data;
	}
    $database->content->free_result();
    print
    	"<p class=left>Document# " .
        $forms->text('ajax_document_id','',6,0,'') .
		" " . $forms->button('Select Document',array('onclick'=>'fn_ajax_select();')) .
        "</p>\n";
    print "<div class='accordion'>\n";
	print "<h3>New Parent</h3>\n";
	print "<div>" . fn_href("Add new parent","javascript:fn_action(this,'maintain','0','0','');") . "</div>\n";
    foreach ($parents as $parent => $children) {
    	print "<h3>{$parent}. " . $text=$documents[$parent]->name . " (" . sizeof($children). ")</h3>\n";
        print "<div>\n";
		print "<table class='standard border'>\n";
        print "<tr>\n";
        print "<th>ID</th>\n";
        print "<th>Name</th>\n";
        print "<th>Description</th>\n";
        print "<th>View</th>\n";
        print "<th>Download</th>\n";
        print "<th>Del?</th>\n";
        print "</tr>\n";
        print "<tr>\n";
        print "<td colspan=6>" . fn_href("New child","javascript:fn_action(this,'maintain','0','{$parent}','');") . "</td>\n";
        print "</tr>\n";
        foreach ($children as $child) {
        	print "<tr>\n";
            $text=$documents[$child]->name;
            if (!$documents[$child]->parent_id) $text .= " (Parent document)";
            print "<td class=center>" . fn_href($child,"javascript:fn_action(this,'maintain','{$child}','{$parent}','');") . "</td>\n";
            print "<td>$text</td>\n";
            print "<td>" . $documents[$child]->description . "</td>\n";
            $view_array=array();
            $upload_array=array();
            foreach ($documents[$child]->contents as $language => $language_id) {
				$view_array[]=fn_href($language,"/content_viewer.php",array("viewer"=>$database->content->encode($language_id)),array("target"=>"document_viewer","title"=>"View " . $menu->language[$language]->name));
				$upload_array[]=fn_href($language,"/content_viewer.php",array("download"=>$database->content->encode($language_id)),array("title"=>"Download " . $menu->language[$language]->name));
            }
            print "<td>" . implode("\n ",$view_array) . "</td>\n";
            print "<td>" . implode("\n ",$upload_array) . "</td>\n";
            switch (TRUE) {
               case ( (!$documents[$child]->parent_id) && (sizeof($children)) ):
               case ($documents[$child]->active):
				$text="";
				break;
               default:
              	$text=$forms->radio("delete",$parent,0,array("onclick"=>"fn_action(this,'delete'," . fn_escape($documents[$child]->id) . "," . fn_escape($documents[$child]->parent_id) . "," . fn_escape($documents[$child]->name) . ");"));
            }
            print "<td class=center>$text</td>\n";
            print "</tr>\n";
        }
        print "</table>\n";
        print "</div>";
    }
    print "</div>\n";
}
print $forms->close();
$menu->copyright();

function tab_language($language) {
global $database, $forms;
	$results=array();
	$results[]="<table class='large noborder'>";
    $field_name=base64_encode($language->code . "\t" . "name");
    $field_description=base64_encode($language->code . "\t" . "description");
    $field_file=base64_encode($language->code . "\t" . "file");
    $field_delete=base64_encode($language->code . "\t" . "delete");
    $results[]=$forms->hidden($field_file,$language->code);
	if ($language->code == "EN") {
	    if ($database->document->data->id) {
	        $results[]="<tr>";
	        $results[]="<td>Document ID</td>";
	        $results[]="<td><b>" . $database->document->data->id . "</b></td>";
	        $results[]="</tr>";
	    }
	    $results[]="<tr>\n";
	    $results[]="<td>Parent Document</td>\n";
	    $query_where=array();
	    $query_where[] = new query_where("parent_id","=",0);
	    if ($database->document->data->id) $query_where[] = new query_where("id","<>",$database->document->data->id);
	    $query =
	        "select * from document \n" .
	        $database->where($query_where) .
	        "order by name,id";
	    $database->temp->query($query);
	    $data=array();
	    $data[0]="0. Parent";
	    while ($database->temp->fetch = $database->temp->fetch_array() ) {
	        $data[$database->temp->fetch['id']]=$database->temp->fetch['id'] . ". " . $database->temp->fetch['name'];
	    }
	    $database->temp->free_result();
	    $results[]="<td>" . $forms->select("parent_id",$data,$database->document->data->parent_id,FALSE) . "</td>\n";
	    $results[]="</tr>\n";
	}
    $results[]="<tr>";
    $results[]="<td width='30%'>Name</td>";
    $results[]="<td width='70%'>" . $forms->text($field_name,$_SESSION['document_maintain'][$language->code]->name,60,60) . "</td>";
    $results[]="</tr>";
    $results[]="<tr>";
    $results[]="<td>Description</td>";
	$results[]="<td>" . $forms->textarea($field_description,$_SESSION['document_maintain'][$language->code]->description,4,60) . "</td>";
    $results[]="</tr>";
    $results[]="<tr>";
    $results[]="<td>Upload file</td>";
    $results[]="<td>" . $forms->file($field_file,30);
    if (array_key_exists($field_file,$forms->error_text)) $results[]="<br>" . $forms->error_text[$field_file];
    $results[]="</td>";
    $results[]="</tr>";
    if ($_SESSION['document_maintain'][$language->code]->meta->name) {
        $results[]="<tr>";
        $results[]="<td>Delete?</td>";
        $results[]="<td>" . $forms->checkbox($field_delete,1,0) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
	    $results[]="<tr>";
	    $results[]="<td>File Name</td>";
	    $results[]="<td>" . $_SESSION['document_maintain'][$language->code]->meta->name . "</td>";
	    $results[]="</tr>";
	    $results[]="<tr>";
	    $results[]="<td>Mime</td>";
	    $results[]="<td>" . $_SESSION['document_maintain'][$language->code]->meta->mime . "</td>";
	    $results[]="</tr>";
	    $results[]="<tr>";
	    $results[]="<td>Revised</td>";
	    $results[]="<td>" . date("m/d/Y h:i A",$_SESSION['document_maintain'][$language->code]->revised) . "</td>";
	    $results[]="</tr>";
    }
	if ($language->code == "EN") {
	    $results[]="<tr>";
	    $results[]="<td>Active</td>";
	    $results[]="<td>" . $forms->checkbox("active",1,$database->document->data->active) . "</td>";
	    $results[]="</tr>";
    }
    $results[]="</table>\n";
    return $results;
}
?>