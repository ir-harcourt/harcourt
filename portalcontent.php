<?php
require_once "scs_header.php";
require_once "classes/portal.php";
require_once "classes/portalcontent.php";
$database->user->access("Administrator");
/*
https://harcourtgroup-my.sharepoint.com/:x:/g/personal/dale_beardmore_harcourt_co/ER2N4VXeq6JAuUTAUoKa4p4BMvHLJ-lFXJ0KCmDEBtZcnw?e=3VGg4c
*/
$obj=new local_class();

class local_class {
    var $content=array();
    function scs_table_version() {
        $results=array();
        $results['10/10/2025']="Add form:URL";
        $results['08/19/2024']="Revised";
        $results['10/31/2012']="Initial release";
        return $results;
    }
    function __construct() {
        switch (TRUE) {
          case (isset($_SERVER['HTTP_X_REQUESTED_WITH'])):
            $this->json();
            break;
          default:
            $this->html();
        }
    }
    function json() {
        global $database;
        $this->response=new stdClass;

        die(json_encode($this->response));
    }
    function input() {
        global $database, $forms, $menu;
        $this->input=new stdClass;
        $this->input->action=$_POST['action'];
        $this->input->record_id=$_POST['record_id'];
        $this->input->report_portal_id=$_POST['report_portal_id'];
        $database->portalcontent->read($this->input->record_id);
        switch ($this->input->action) {
          case "":
            return;
          case "cancel":
            $forms->message[]="Request cancelled";
            unset($_SESSION['maintain_portalcontent']);
            return;
          case "delete":
            $database->portalcontent->delete($this->input->record_id);
            $query=array("delete from content");
            $query[]="where record_type='portalcontent'";
            $query[]="and record_id=" . fn_escape($this->input->record_id);
            $database->temp->query($query);
            $forms->message[]="Portal content {$database->portalcontent->data->name} deleted";
            return;
          case "maintain":
            if (!$this->input->record_id) $database->portalcontent->data->portal_id=$this->input->report_portal_id;
            $query=array("select * from content");
	        $query[]="where record_type='portalcontent'";
            $query[]="and record_id=" . fn_escape($this->input->record_id);
	        $database->content->query($query);
            $_SESSION['maintain_portalcontent']=array();
            $en_info="";
            while ($database->content->fetch = $database->content->fetch_array() ) {
                $database->content->fetch();
                if ($database->portalcontent->data->form == "Hyperlink") $database->content->data->hyperlink=$database->content->fetch['html_content'];
                switch (TRUE) {
                  case ($database->content->data->language_code=="EN"):
                    $en_info=$this->portalcontent_info($database->content->data);
                    break;
                  case ($this->portalcontent_info($database->content->data) == $en_info):
                    $database->content->data->meta->name="";
                    break;
                }
                $_SESSION['maintain_portalcontent'][ $database->content->data->language_code ]=$database->content->data;
            }
            break;
          case "update":
            $this->input_update();
            break;

        }
    }
    function input_update() {
        global $database, $forms, $menu;
        if (!isset($_SESSION['maintain_portalcontent'])) {
            $forms->message[]="Refresh not allowed";
            return;
        }
        $database->portalcontent->data->portal_id=$_POST['portal_id'];
        $database->portalcontent->data->form=$_POST['form'];
        $database->portalcontent->data->active=$_POST['active'];
        foreach ($menu->language as $language_code => $language_obj) {
            $content=(array_key_exists($language_code, $_SESSION['maintain_portalcontent'])) ? $_SESSION['maintain_portalcontent'][$language_code] : new content_data_class($language_code);
            $field_name=fn_base64(array("name", $language_code));
            $field_file=fn_base64(array("file", $language_code));
            $field_delete=fn_base64(array("delete", $language_code));
            $field_hyperlink=fn_base64(array("hyperlink", $language_code));
		    $content->name=trim($_POST[$field_name]);
            switch (TRUE) {
              case ($database->portalcontent->data->form == "Hyperlink"):
                $content->hyperlink=fn_text($_POST[$field_hyperlink]);
                if ( ($language_code != "EN") && (!strlen($content->name)) ) break;
                $url=parse_url($content->hyperlink);
                switch (TRUE) {
                  case (!strlen($content->hyperlink)):
                    $forms->error($field_hyperlink, "Cannot be blank");
                    break;
                  case ($url['scheme'] != "https"):
                  case (!strlen($url['host'])):
                  case (!strlen($url['host'])):
                    $forms->error($field_hyperlink, "Malformed URL");
                }
                $content->meta=new file_meta_class();
                if (!strlen($content->hyperlink)) continue;
                $content->revised=strtotime("now");
                $content->filetype="hyperlink";
                $content->meta->name="manual.html";
                $content->meta->mime="text/html";
                break;
              case ($_POST[$field_delete]):
                $content->fileinfo($content);
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
                $database->content->data=$content;
                $tempname=tmpfile();
                if (file_exists("scs.tmp")) unlink("scs.tmp");
                move_uploaded_file($_FILES[$field_file]['tmp_name'],"scs.tmp");
                $content->content=file_get_contents("scs.tmp");
                unlink("scs.tmp");
                $database->content->fileinfo($content,$_FILES[$field_file]['name']);
                if (!$content->meta->mime) $forms->error($field_file,"File type not supported");
            }
            $_SESSION['maintain_portalcontent'][$language_code]=$content;
            switch (TRUE) {
		      case ($language_code=="EN"):
                $database->portalcontent->data->name=$content->name;
                if (!$database->portalcontent->data->name) $forms->error($field_name);
                if ( (!$content->meta->name) && (!array_key_exists($field_file,$forms->error)) ) $forms->error($field_file,"English file mandatory");
                break;
              case ( ($content->meta->name) && (!$content->name) ):
                $forms->error($field_name);
                break;
            }
            switch (TRUE) {
              case ($database->portalcontent->data->form == "Hyperlink"):
                break;
              case (array_key_exists($field_file,$forms->error)):
              case (!$content->meta->name):
                break;
              case (!$content->filetype):
                $forms->error($field_file,"Invalid file type");
                break;
              case (
                (in_array($database->portalcontent->data->form,array("Portal Image","Category Image"))) &&
                (!in_array($content->filetype,array("image")))
                    ):
                $forms->error($field_file,"Banner files must be images");
                break;
              case (
                (in_array($database->portalcontent->data->form,array("Portal HTML","Category HTML"))) &&
                (!in_array($content->filetype,array("html")))
                   ):
                $forms->error($field_file,"Headline files must be HTML");
                break;
              case (
                (in_array($database->portalcontent->data->form,array("Link"))) &&
                (!in_array($content->filetype,array("pdf","other")))
                 ):
                $forms->error($field_file,"Links must be PDF or Other");
                break;
            }
        }
        if (sizeof($forms->error)) return;
        $database->portalcontent->update($this->input->record_id);
        if ($database->portalcontent->meta->error) {
            $forms->error("name","",$database->portalcontent->meta->error);
            return;
        }
        foreach ($_SESSION['maintain_portalcontent'] as $language_code => $database->content->data) {
            switch (TRUE) {
              case ($database->content->data->name):
                $database->content->data->record_type="portalcontent";
                $database->content->data->record_id=$database->portalcontent->data->id;
                $database->content->data->language_code=$language_code;
                if ($database->portalcontent->data->form == "Hyperlink") $database->content->data->content=$database->content->data->hyperlink;
                switch (TRUE) {
                  case ($language_code == "EN"):
                  case ($database->content->data->meta->name):
                    break;
                  default:
                    $database->content->data->meta=$_SESSION['maintain_portalcontent']['EN']->meta;
                    $database->content->data->revised=$_SESSION['maintain_portalcontent']['EN']->revised;
                    $database->content->data->filetype=$_SESSION['maintain_portalcontent']['EN']->filetype;
                    $database->content->data->content=$_SESSION['maintain_portalcontent']['EN']->content;
                }
                $database->content->update($database->content->data->id);
                break;
              case ($database->content->data->id):
                $database->content->delete($database->content->data->id);
                break;
            }
        }
        unset($_SESSION['maintain_portalcontent']);
        $forms->message[]="Record maintained";
    }
    function portalcontent_info($data) {
        if (!$data->meta->name) {
            return "";
          } else {
            $results=array();
            $results['name']=$data->meta->name;
            $results['mime']=$data->meta->mime;
            $results['revised']=$data->revised;
            return http_build_query($results);
        }
    }
    function html() {
        global $database, $forms, $menu;
        $forms->title("Portal Content Manager", key($this->scs_table_version()));
        $this->input();
        $menu->head();
        print $forms->message();
        print $this->js();
        print $forms->open(array("data"=>TRUE));
        print $forms->hidden("action");
        print $forms->hidden("record_id");
        print $forms->hidden("report_portal_id");

        switch (TRUE) {
          case ($this->input->action == "upload"):
          case ($this->input->action == "maintain"):
          case (($this->input->action == "update") && (sizeof($forms->error))):
            $this->tabs=new jquery_tab_class();
            if (isset($_SESSION['scscpq_wp'])) $this->tabs->meta->options->lineheight="auto";
            foreach ($menu->language as $language_code => $language_obj) {
               $this->tabs->item($language_obj->name,$this->html_language($language_code));
            }
            $this->tabs->output();
            $buttons=array();
            $buttons[]=$forms->button("Update",array("onclick"=>"fn_action('update',"  . fn_escape($this->input->record_id) . ",'');"));
            $buttons[]=$forms->button("Cancel",array("onclick"=>"fn_action('cancel','','');"));
            print "<p>" . implode(" ", $buttons) . "</p>";
            break;
          default:
            print implode("", $this->html_list());
        }
        print $forms->close();
        $menu->copyright();
    }
    function html_list() {
        global $database, $forms;
        $this->portal=array();
        $this->portalcontent=array();
        $this->portalcontent_list=array();
        $database->portal->query("select * from portal order by name");
        while ($database->portal->fetch = $database->portal->fetch_array()) {
            $database->portal->fetch();
            $this->portal[$database->portal->data->id]=$database->portal->data;
            $this->portalcontent[$database->portal->data->id]=array();
        }
        $database->portal->free_result();

        $query_where=array();
        $query_where[]=new query_where("portal_id","in",array_keys($this->portal));
        $query=array();
        $query[]="select * from portalcontent";
        $query[]=trim($database->where($query_where));
        $query[]="order by portal_id,name";
        $database->portalcontent->query($query);
        while ($database->portalcontent->fetch = $database->portalcontent->fetch_array() ) {
            $database->portalcontent->fetch();
            $this->portalcontent_list[$database->portalcontent->data->id]=array();
            $this->portalcontent[$database->portalcontent->data->portal_id][$database->portalcontent->data->id]=$database->portalcontent->data;
        }
        $database->portalcontent->free_result();

        $query_where=array();
        $query_where[]=new query_where("record_type","=","portalcontent");
        $query_where[]=new query_where("record_id","in",array_keys($this->portalcontent_list));
        $query=array();
        $query[]="select * from content";
        $query[]=trim($database->where($query_where));
        $database->content->query($query);
        while ($database->content->fetch = $database->content->fetch_array() ) {
            $database->content->fetch();
            $this->portalcontent_list[$database->content->data->record_id][$database->content->data->language_code]=$database->content->data->id;
        }
        $database->content->free_result();
        $results=array();
        $results[]="<div id='jquery_accordian' class='accordion'>";
        foreach ($this->portal as $this->portal_item) {
            $results[]="<h3>{$this->portal_item->name} (" . sizeof($this->portalcontent[$this->portal_item->id]) . ")</h3>";
            $results[]="<div>";
            $results[]="<table class='large border'>";
            $results[]="<tr>";
            $results[]="<th>Name</th>";
            $results[]="<th>Form</th>";
            $results[]="<th>View</th>";
            $results[]="<th>Download</th>";
            $results[]="<th>Del?</th>";
            $results[]="</tr>";
            $results[]="<tr>";
            $results[]="<td colspan=5>" . fn_href("Add new item","javascript:fn_action('maintain','0'," . fn_escape($this->portal_item->id) . ");") . "</td>";
            $results[]="</tr>";
            foreach ($this->portalcontent[$this->portal_item->id] as $item) {
                $results[]="<tr " . ((!$item->active) ? $bg=$forms->background->yellow : "" ) . ">";
                $results[]="<td>" . fn_href($item->name,"javascript:fn_action('maintain'," . fn_escape($item->id) . ",'');") . "</td>";
                $results[]="<td>" . $item->form . "</td>";
                $view_array=array();
                $upload_array=array();
                foreach ($this->portalcontent_list[$item->id] as $language_code => $content_item) {
                    $view_array[]=fn_href($language_code,"/content_viewer.php",array("viewer"=>$database->content->encode($content_item)),array("target"=>"document_viewer","title"=>"View " . $menu->language[$language_code]->name));
                    $upload_array[]=fn_href($language_code,"/content_viewer.php",array("download"=>$database->content->encode($content_item)),array("title"=>"Download " . $menu->language[$language_code]->name));
                }
                $results[]="<td>" . implode(" ",$view_array) . "</td>";
                $results[]="<td>" . implode(" ",$upload_array) . "</td>";
                $results[]="<td class=center>" . ( ($item->active) ? "&nbsp;" : $forms->checkbox_delete($item->id,$item->name)) . "</td>";
                $results[]="</tr>";
            }
            $results[]="</table>";
            $results[]="</div>";
        }
        $results[]="</div>";
        return $results;
/*
        $accordion=new jquery_accordion_class();
        return $accordion->output($results);
*/
    }
    function html_language($language_code) {
        global $database, $forms;
        $content=(array_key_exists($language_code,$_SESSION['maintain_portalcontent'])) ? $_SESSION['maintain_portalcontent'][$language_code] : new portalcontent_data_class();
        $results=array();
        $results[]="<table class='standard noborder'>";
        $field_name=fn_base64(array("name",$language_code));
        $field_file=fn_base64(array("file",$language_code));
        $field_delete=fn_base64(array("delete",$language_code));
        $field_hyperlink=fn_base64(array("hyperlink",$language_code));
        $results[]=$forms->hidden($field_file, $language_code);
        if ($language_code == "EN") {
            $results[]="<tr>";
            $results[]="<td>Portal name</td>";
            $results[]="<td>" . $database->portal->select($database->portalcontent->data->portal_id) . "</td>";
            $results[]="</tr>";
            $results[]="<tr>";
            $results[]="<td>Used by</td>";
            $results[]="<td>" . $forms->select("form",$database->portalcontent->constant->form,$database->portalcontent->data->form,FALSE,array("onchange"=>"fn_form();")) . "</td>";
            $results[]="</tr>";
        }
        $results[]="<tr>";
        $results[]="<td width='20%'>Name</td>";
        $results[]="<td width='80%'>" . $forms->text($field_name,$content->name,60,60) . "</td>";
        $results[]="</tr>";
        $results[]="<tr class=file_tr>";
        $text="Upload file";
        if ($language_code != "EN") $text .= "<br>(English content will be used by default)";
        $results[]="<td>{$text}</td>";
        $results[]="<td>" . $forms->file($field_file,40);
        if (array_key_exists($field_file,$forms->error_text)) $results[]="<br>" . $forms->error_text[$field_file];
        $results[]="</td>";
        $results[]="</tr>";
        if ($content->meta->name) {
            if ($language_code != "EN") {
                $results[]="<tr class=file_tr>";
                $results[]="<td>Delete?</td>";
                $results[]="<td>" . $forms->checkbox($field_delete,1,0) . "</td>";
                $results[]="</tr>";
            }
            $results[]="<tr class=file_tr>";
            $results[]="<td>File name</td>";
            $results[]="<td>{$content->meta->name}</td>";
            $results[]="</tr>";
            $results[]="<tr>";
            $results[]="<td>Mime</td>";
            $results[]="<td>{$content->meta->mime}</td>";
            $results[]="</tr>";
            $results[]="<tr>";
            $results[]="<td>Revised</td>";
            $results[]="<td>" . date("m/d/Y h:i A",$content->revised) . "</td>";
            $results[]="</tr>";
        }
        $results[]="<tr class=url_tr>";
        $results[]="<td>Hyperlink</td>";
        $text=array();
        if ($forms->error_text[$field_hyperlink]) $text[]=$forms->error_text[$field_hyperlink];
        $text[]=$forms->textarea($field_hyperlink, $content->hyperlink, 3, 100);
        $results[]="<td>" . implode("<br>", $text) . "</td>";
        $results[]="</tr>";
        if ($language_code == "EN") {
            $results[]="<tr>";
            $results[]="<td>Active?</td>";
            $results[]="<td>" . $forms->checkbox("active",1,$database->portalcontent->data->active) . "</td>";
            $results[]="</tr>";
        }
        $results[]="</table>";
        return $results;
    }
    function jquery_accordian() {
        $results=array();
        $results[]= <<< EOT

<style>
.jquery_loader {
    display: inline-block;
    border: 4px solid #f3f3f3;
    border-radius: 50%;
    border-top: 4px solid #0981be;
    width: 20px; height: 20px;
    -webkit-animation: spin 2s linear infinite;
    animation: spin 2s linear infinite;
}
.jquery_loader_text {
    display: inline-block;
    vertical-align: top;
    margin-left: 10px;
    margin-top: 5px;
}
@-webkit-keyframes spin {
  0% { -webkit-transform: rotate(0deg); }
  100% { -webkit-transform: rotate(360deg); }
}
@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>

EOT;
        $results[]= <<< EOT
<script>
jQuery(document).ready(function() {
    jQuery("#scs_jquery_loader").hide();
    jQuery("#jquery_accordian").show();
});
</script>

EOT;
        $results[]="<div id='scs_jquery_loader' class='scs_jquery_loader'><span class='jquery_loader'></span><span class='jquery_loader_text'>Loading ...</span></div>";
        return implode("", $results);
    }

    function js() {
        return <<< EOT

<script>
jQuery(document).ready(function() {
	fn_form();
});

function fn_form() {
    if (jQuery("#form").val() == "Hyperlink") {
        jQuery(".file_tr").hide();
        jQuery(".url_tr").show();
      } else {
        jQuery(".file_tr").show();
        jQuery(".url_tr").hide();
    }
}

function fn_action(action, id, name) {
	if (action == 'delete') {
        jQuery("#delete" + id).prop("checked", false);
	    if (!confirm(name)) return;
	}
	jQuery('#action').val(action);
	jQuery('#record_id').val(id);
	jQuery('#report_portal_id').val(name);
	jQuery('#scs_form').submit();
}
</script>
EOT;
    }
}
?>