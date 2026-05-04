<?php
require_once "scs_header.php";
require_once "classes/pages.php";
require_once "classes/category.php";
$database->user->access("Administrator");
$forms->title("Pages Manager");
$forms->report['action']=$_POST['action'];
$forms->report['record_id']=$_POST['record_id'];
$forms->report['record_type']=$_POST['record_type'];
if ($forms->report['record_id']) {
	$database->pages->read($forms->report['record_id']);
  } else {
    $database->pages->data=new pages_data_class();
}
switch (TRUE) {
  case (!$forms->report['action']):
    break;
  case ($forms->report['action'] == "cancel"):
	unset($_SESSION['maintain_pages']);
    $forms->message[]="Request cancelled";
    break;
  case ($forms->report['action'] =="maintain"):
	if (!$forms->report['record_id']) {
    	$database->pages->data->record_type=$forms->report['record_type'];
    	$database->pages->data->url=$menu->page->products->page;
	}
	$_SESSION['maintain_pages']=array();
	$query=
	    "select * from content \n" .
	    "where record_type='pages' \n" .
        "and record_id=" . fn_escape($forms->report['record_id']);
	$database->content->query($query);
    while ($database->content->fetch = $database->content->fetch_array() ) {
		$database->content->fetch();
        $_SESSION['maintain_pages'][ $database->content->data->language_code ]=$database->content->data;
    }
	$database->content->free_result();
    break;
  case ($forms->report['action'] == "delete"):
    $database->pages->delete($forms->report['record_id']);
    $forms->message[]="pages " . $database->pages->data->name . " deleted";
    $query_where=array();
    $query_where[]=new query_where("record_type","=","pages");
    $query_where[]=new query_where("record_id","=",$forms->report['record_id']);
    $query=
    	"delete from content \n" .
        $database->where($query_where);
	$database->temp->query($query);
    break;
  case ($forms->report['action'] == "update"):
	$database->pages->data->record_type=$forms->report['record_type'];
	foreach ($menu->language as $language_code => $language) {
		if (array_key_exists($language_code,$_SESSION['maintain_pages'])) {
			$content=$_SESSION['maintain_pages'][$language_code];
		  } else {
			$content=new content_data_class();
            $content->language_code=$language_code;
		}
		$field_name=base64_encode("name" . "\t" . $language_code);
		$field_description=base64_encode("description" . "\t" . $language_code);
		$content->name=trim($_POST[$field_name]);
		$content->description=trim($_POST[$field_description]);
		$_SESSION['maintain_pages'][$language_code]=$content;
        switch (TRUE) {
          case ($language_code=="EN"):
            $database->pages->data->name=$content->name;
			if (!$database->pages->data->name) $forms->error($field_name);
            break;
		  case ( (!$content->name) && ($content->description) ):
			$forms->error($field_name,"Name cannot be blank when description provided");
            break;
        }
    }
	$database->pages->data->category_id=$_POST['category_id'];
    if ( ($database->pages->data->record_type=="products") && (!$database->pages->data->category_id) ) $forms->error('category_id');
	$database->pages->data->url=trim($_POST['url']);
	$url=parse_url($database->pages->data->url);
    switch (TRUE) {
	  case (!$database->pages->data->url):
		$forms->error('url',"Entry cannot be blank");
        break;
	  case ($url['scheme']):
	  case ($url['host']):
		$forms->error('url',"Internal documents only");
		break;
	  case (substr($database->pages->data->url,0,1) != "/"):
		$forms->error('url',"URL must begin with a /");
        break;
	  case (!file_exists($_SERVER['DOCUMENT_ROOT'] . $url['path'])):
		$forms->error('url',"File not found");
        break;
	}
	$database->pages->data->document_id=$_POST['document_id'];
	switch (TRUE) {
	  case ($database->pages->data->record_type == "products"):
		$database->pages->data->document_id=0;
        break;
	  case (!$database->pages->data->document_id):
      	$forms->error('document_id');
	}
	$database->pages->data->sort_order=$_POST['sort_order'];
	$database->pages->data->user_type=$_POST['user_type'];

	$database->pages->data->css=array();
	$items=explode("\n",$_POST['css']);
    foreach ($items as $item) {
    	$item=trim($item);
        if (!in_array($item,$database->pages->data->css)) $database->pages->data->css[]=$item;
    }
	$database->pages->data->js=array();
	$items=explode("\n",$_POST['js']);
    foreach ($items as $item) {
    	$item=trim($item);
        if (!in_array($item,$database->pages->data->js)) $database->pages->data->js[]=$item;
    }
	$database->pages->data->active=$_POST['active'];
	if (sizeof($forms->error)) break;
    $database->pages->update($database->pages->meta->rows);
    switch (TRUE) {
      case (!$database->pages->meta->error):
      	break;
      case (preg_match("/name_key/",$database->pages->meta->error)):
        $forms->error('name',"",$database->pages->meta->error);
      	break;
      default:
        $forms->error('url',"",$database->pages->meta->error);
	}
	if (sizeof($forms->error)) break;
    foreach ($_SESSION['maintain_pages'] as $database->content->data) {
        switch (TRUE) {
          case ($database->content->data->name):
            $database->content->data->record_type="pages";
            $database->content->data->record_id=$database->pages->data->id;
	        $database->content->data->revised=strtotime("now");
            $database->content->update($database->content->data->id);
            break;
          case ($database->content->data->id):
            $database->content->delete($database->content->data->id);
        }
    }
	unset($_SESSION['maintain_pages']);
    $forms->message[]="Record maintained";
	break;
}
$menu->head();
print $forms->message();
?>
<script type='text/javascript'>
function fn_action(obj,action,record_id,record_type,name) {
	if (action=='delete') {
	    if (!confirm("Delete: #" + record_id + " " + record_type + ": " + name)) {
	        obj.checked=false;
            return;
	    }
    }
	document.scs_form.action.value=action;
	document.scs_form.record_id.value=record_id;
	document.scs_form.record_type.value=record_type;
	document.scs_form.submit();
}
function fn_url(url,category_id) {
	if (category_id) url += '?category_id=' + category_id + '#category' + category_id;
    $('#url').val(url);
}
</script>
<?php
print $forms->open(array("data"=>TRUE));
print $forms->hidden("action");
print $forms->hidden("record_id");
print $forms->hidden("record_type");
switch (TRUE) {
  case ($forms->report['action'] == "maintain"):
  case (($forms->report['action'] == "update") && (sizeof($forms->error))):
	$menu->tabs=new jquery_tab_class();
	if (isset($_SESSION['scscpq_wp'])) $menu->tabs->meta->options->lineheight="auto";    
	foreach ($menu->language as $language) {
		$menu->tabs->item($language->name,document_tab($language));
	}
    $menu->tabs->output();
	print
    	"<p class='standard center'>" .
        $forms->button("Update",array("onclick"=>"fn_action(this,'update'," . fn_escape($forms->report['record_id']) . "," . fn_escape($forms->report['record_type']) . ",'');")) .
        " " . $forms->button("Cancel",array("onclick"=>"fn_action(this,'cancel','','','');")) . "</p>\n";
  	break;
  default:
	$parents=array();
    foreach ($database->pages->constant->record_type as $record_type => $record_type) {
		$parents[$record_type]=array();
    }
    $query =
        "select \n" .
        "document.name as 'document_name', \n" .
        "pages.* from pages \n" .
        "left join document on document.id=pages.document_id \n" .
        "order by pages.record_type,pages.sort_order,pages.name";
    $database->pages->query($query);
    while ($database->pages->fetch = $database->pages->fetch_array()) {
	    $database->pages->fetch();
        if (!array_key_exists($database->pages->data->record_type,$parents)) continue;
		$database->pages->data->document_name=$database->pages->fetch['document_name'];
        $parents[$database->pages->data->record_type][]=$database->pages->data;
	}
	$database->pages->free_result();
    print "<div class='accordion'>\n";

    foreach ($parents as $parent => $children) {
    	print "<h3>" . $database->pages->constant->record_type[$parent] . " (" . sizeof($children). ")</h3>\n";
        print "<div>\n";
	    print "<table class='small border tablesorter'>\n";
	    print "<caption>&nbsp;</caption>\n";
        print "<thead>\n";
	    print "<tr>\n";
	    print "<th>Name</th>\n";
	    print "<th>Order</th>\n";
	    print "<th>URL</th>\n";
	    print "<th>Access</th>\n";
	    print "<th>Document</th>\n";
	    print "<th>Del?</th>\n";
	    print "</tr>\n";
        print "</thead>\n";
	    print "<tr>\n";
	    print "<td colspan=6>" . fn_href("Add new","javascript:fn_action(this,'maintain','0'," . fn_escape($parent) . ",'');") ."</td>\n";
	    print "</tr>\n";
        print "<tbody>\n";
        foreach ($children as $child) {
	        print "<tr>\n";
	        print "<td>" . fn_href($child->name,"javascript:fn_action(this,'maintain'," . fn_escape($child->id) . "," . fn_escape($child->record_type) . ",'');") . "</td>\n";
	        print "<td class=center>" . $child->sort_order . "</td>\n";
	        print "<td>" . $child->url . "</td>\n";
	        print "<td>" . $child->user_type . "</td>\n";
            if ($child->document_id) {
            	$text=$child->document_id . ". " . $child->document_name;
			  } else {
              	$text="";
			}
	        print "<td>$text</td>\n";
	        if ($child->active) {
	            $text="";
	          } else {
	            $text=$forms->radio("delete",$database->pages->data->id,0,array("onclick"=>"fn_action(this,'delete'," . fn_escape($child->id) . "," . fn_escape($child->record_type) . "," . fn_escape($child->name) . ");"));
	        }
	        print "<td class=center>$text</td>\n";
	        print "</tr>\n";
        }
        print "</tbody>\n";
		print "</table>\n";
        print "</div>\n";
	}
	print "</div>\n";
}
print $forms->close();
$menu->copyright();
function document_tab($language) {
global $database, $forms, $menu;
	$results=array();
    $content=$_SESSION['maintain_pages'][$language->code] ;
    $field_name=base64_encode("name" . "\t" . $language->code);
    $field_description=base64_encode("description" . "\t" . $language->code);
	$results[]="<table class='large noborder'>";
    $results[]="<tr>";
    $results[]="<td width='30%'>Record Type</td>";
    $results[]="<td width='70%'>" . $database->pages->constant->record_type[$database->pages->data->record_type] . "</td>";
    $results[]="</tr>";
    $results[]="<tr>";
    $results[]="<td width='30%'>Name</td>";
    $results[]="<td width='70%'>" . $forms->text($field_name,$content->name,40,40);
    if ($forms->error_text[$field_name]) $results[]="<br>" . $forms->error_text[$field_name];
    $results[]="</td>";
    $results[]="</tr>";
    $results[]="<tr>";
    $results[]="<td>Description</td>";
    $results[]="<td>" . $forms->textarea($field_description,$content->description,3,60);
    if ($forms->error_text[$field_description]) $results[]="<br>" . $forms->error_text[$field_description];
    $results[]="</td>";
    $results[]="</tr>";
	if ($language->code=="EN") {
        $url_options=array();
	    $results[]="<tr>";
	    $results[]="<td>User type</td>";
	    $results[]="<td>" . $forms->select("user_type",$database->user->constant->type,$database->pages->data->user_type) . "</td>";
	    $results[]="</tr>";
        switch ($database->pages->data->record_type) {
		  case "products":
          	$url_options['readonly']=TRUE;
	        $results[]="<tr>";
	        $results[]="<td>Category</td>";
	        $results[]="<td>" . $database->category->select($database->pages->data->category_id,array(),array("onchange"=>"fn_url(" . fn_escape($menu->page->products->page) . ",this.value);")) . "</td>";
	        $results[]="</tr>";
          	break;
          default:
	        $results[]="<tr>";
	        $results[]="<td>Document ID</td>";
	        $results[]="<td>" . $database->document->select($database->pages->data->document_id) . "</td>";
	        $results[]="</tr>";
		}
	    $results[]="<tr>";
	    $results[]="<td>URL File Name</td>";
	    $results[]="<td>" . $forms->text("url",$database->pages->data->url,60,0,"",$url_options);
		if ($forms->error_text['url']) $results[]="<br>" . $forms->error_text['url'];
        $results[]="</td>";
	    $results[]="</tr>";
/* Future implementation
	    $results[]="<tr>";
	    $results[]="<td>Additional CSS</td>";
	    $results[]="<td>" . $forms->textarea("css",$database->pages->data->css,3,60) . "</td>";
	    $results[]="</tr>";
	    $results[]="<tr>";
	    $results[]="<td>Additional Javascript</td>";
	    $results[]="<td>" . $forms->textarea("js",$database->pages->data->js,3,60) . "</td>";
	    $results[]="</tr>";
*/
	    $results[]="<tr>";
	    $results[]="<td>Sort Order</td>";
	    $results[]="<td>" . $forms->select_number("sort_order",$database->pages->data->sort_order,0,99) . "</td>";
	    $results[]="</tr>";
	    $results[]="<tr>";
	    $results[]="<td>Active?</td>";
	    $results[]="<td>" . $forms->checkbox("active",1,$database->pages->data->active) . "</td>";
	    $results[]="</tr>";
    }
	$results[]="</table>";
    return $results;
}
?>