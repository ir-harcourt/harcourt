<?php
require_once "scs_header.php";
require_once "classes/category.php";
require_once "classes/subcategory.php";
require_once "classes/industry.php";
require_once "classes/portal.php";
$obj=new local_class();
class local_class {
    var $content=array();
    var $image_list=array("Thumbnail","Sketch","Both");
    var $error;
    var $trace=array();
    function scs_table_version() {
        $results=array();
        $results['10/06/2025']="Prevent malformed HTML";
        $results['09/25/2025']="html_list output correction";
        $results['04/01/2025']="Add lookup column";
        $results['11/13/2024']="URL decode prj";
        $results['07/25/2024']="Add lookup";
        $results['07/22/2024']="Add overview download";
        return $results;
    }
    function __construct() {
        switch (TRUE) {
          case (isset($_SERVER['HTTP_X_REQUESTED_WITH'])):
            $this->json();
            break;
          case ($_REQUEST['action'] == "download"):
            $this->download();
            break;
          default:
            $this->html();
        }
    }
    function download() {
        global $database;
        if (!$this->json_load(FALSE)) die('Content not found');
	    header("Content-type: {$database->content->data->meta->mime}");
	    header("Content-Disposition: attachment; filename={$database->content->data->meta->name}");
	    header("Pragma: no-cache");
	    header("Expires: 0");
        die($database->content->data->content);
    }
    function json() {
        global $database;
        $this->trace[]=__FUNCTION__;
        $this->response=new stdClass();
        switch ($_POST['action']) {
          case "sku_count":
            $this->json_sku_count();
            break;
          case "html_load":
          case "html_reload":
            $this->json_html_load();
            break;
          case "html_cancel":
            $this->json_html_cancel();
            break;
          case "html_preview":
            $this->json_html_preview();
            break;
          case "html_update":
            $this->json_html_update();
            break;
        }
        $this->response->trace=$this->trace;
        die(json_encode($this->response));
    }
    function json_load($session=TRUE) {
        global $database;
        $this->trace[]=__FUNCTION__;
        $this->json=new stdClass();
        $this->json->subcategory_id=$_REQUEST['subcategory_id'];
        $this->json->record_item=$_REQUEST['record_item'];
        $this->json->language_code=$_REQUEST['language_code'];
        list($null, $this->json->item)=explode(":", $this->json->record_item);
        $database->subcategory->read($this->json->subcategory_id);
        if (!$session) {
            if (!$database->subcategory->meta->rows) die('Invalid subcategory');
            $query_where=array();
            $query_where[]=new query_where("record_type","=","subcategory");
            $query_where[]=new query_where("record_id","=",$this->json->subcategory_id);
            $query_where[]=new query_where("record_item","=",$this->json->record_item);
            $query_where[]=new query_where("language_code","=",$this->json->language_code);
            $query=array("select * from content");
            $query[]=$database->where($query_where);
            $database->content->query($query);
            $database->content->fetch(TRUE);
            return $database->content->meta->rows;
          } else {
            $this->json->content=$_SESSION['maintain_subcategory'][$this->json->language_code][$this->json->record_item];
            switch (TRUE) {
              case (is_null($this->json->content)):
                $this->json->content=new content_data_class();
                break;
              case (!in_array($this->json->content->meta->mime, array("text/plain", "text/html"))):
                $this->json->content->content="";
                break;
            }
        }
    }
    function json_sku_count() {
        global $database;
        $this->trace[]=__FUNCTION__;
        $query=array("select * from inventory");
        $query[]="where subcategory_id=" . fn_escape($_REQUEST['subcategory_id']);
        $database->temp->query($query);
        $database->temp->free_result();
        $this->response->sku_count=number_format($database->temp->meta->rows);
    }
    function json_html_load() {
        global $database, $forms, $menu;
        $this->trace[]=__FUNCTION__;
        $this->json_load();
        $this->json_html_buttons();
        $this->response->overview_language_name=$menu->language[$this->json->language_code]->name;
        $this->response->overview_remove=0;
        $this->response->overview_name=$this->json->content->name;
/*
        $this->response->overview_html=\ForceUTF8\Encoding::toUTF8($this->json->content->content);
        $this->response->overview_output=\ForceUTF8\Encoding::toUTF8($this->json->content->content);
*/
        $this->response->overview_html=$this->json->content->content;
        $this->response->overview_output=$this->json->content->content;
        $this->response->overview_message="";
        $this->response->visible=1;
    }
    function json_html_preview() {
        $this->trace[]=__FUNCTION__;
        $this->response->overview_message="";
        $this->response->overview_output=trim($_REQUEST['overview_html']);
        $this->response->visible=1;
    }
    function json_html_cancel() {
        $this->trace[]=__FUNCTION__;
        $this->response->overview_message="Request Cancelled";
        $this->response->visible=0;
    }
    function json_html_update() {
        global $database, $forms, $menu;
        $this->trace[]=__FUNCTION__;
        $this->json_load();
        $obj=$_SESSION['maintain_subcategory'][$this->json->language_code][$this->json->record_item];
        if (is_null($obj)) $obj=new content_data_class();
        $this->response->visible=0;
        $field_name=fn_base64(array("overview", "name", $this->json->language_code, $this->json->item));
        $field_meta=fn_base64(array("overview", "meta", $this->json->language_code, $this->json->item));
        $error=array();
        $message=array();
        if (true_false($_REQUEST['overview_remove'])) {
            $message[]="Remove";
            $obj->name="";
            $obj->filetype="";
            $obj->revised=0;
            $obj->meta=new file_meta_class();
          } else {
            $name=fn_text($_REQUEST['overview_name']);
            $html=fn_text($_REQUEST['overview_html']);
            if (!strlen($name)) $error[]="&bull; Name cannot be blank";
            if (!strlen($html)) $error[]="&bull; HTML cannot be blank";
            if (sizeof($error)) {
                $this->response->visible=1;
                array_unshift($error, "Correct the following error(s)");
                $this->response->overview_message=implode("<br>", $error);
                return;
            }
            $message[]="Update";
            $obj->record_item=$this->json->record_item;
            $obj->language_code=$this->json->language_code;
            $obj->name=$name;
            $obj->description="";
            $obj->revised=strtotime("now");
            $obj->filetype="html";
            $obj->content=$html;
            $obj->meta=new file_meta_class();
            $obj->meta->name="{$obj->revised}.html";
            $obj->meta->mime="text/html";
        }
        $message[]="Item #" . ($this->json->item + 1);
        $message[]="(" . $menu->language[$this->json->language_code]->name . ")";
        $this->response->$field_name=$name;
        $this->response->$field_meta=$this->html_overview_meta($obj);
        $this->response->overview_message=implode(" ", $message);
        $_SESSION['maintain_subcategory'][$this->json->language_code][$this->json->record_item]=$obj;
    }
    function json_html_buttons() {
        global $database, $forms, $menu;
        $this->trace[]=__FUNCTION__;
        $buttons=array();
        $buttons[]=$forms->button("Update", array("onclick"=>"subcategory_html('html_update', {$this->json->subcategory_id}, " . fn_escape($this->json->record_item) . "," . fn_escape($this->json->language_code) .");"));
        $buttons[]=$forms->button("Preview", array("onclick"=>"subcategory_html('html_preview', {$this->json->subcategory_id}, " . fn_escape($this->json->record_item) . "," . fn_escape($this->json->language_code) .");"));
        if ($this->json->content->filetype == "html") $buttons[]=$forms->button("Reload", array("onclick"=>"subcategory_html('html_reload', {$this->json->subcategory_id}, " . fn_escape($this->json->record_item) . "," . fn_escape($this->json->language_code) .");"));
        $buttons[]=$forms->button("Cancel",array("onclick"=>"subcategory_html('html_cancel', {$this->json->subcategory_id}, " . fn_escape($this->json->record_item) . "," . fn_escape($this->json->language_code) .");"));
        $this->response->overview_buttons=implode(" ", $buttons);
    }
    function input() {
        global $database, $forms;
        $this->input=new stdClass();
        $this->input->action=$_POST['action'];
        $this->input->record_id=$_POST['record_id'];
        $this->input->report_category_id=$_POST['report_category_id'];
        $this->input->report_status=$_POST['report_status'];
        $this->input->report_images=$_POST['report_images'];
        if (!strlen($this->input->action)) return;
        $database->subcategory->read($this->input->record_id);
        switch (TRUE) {
          case (!$this->input->action):
            break;
          case ($this->input->action =="maintain"):
            $this->input_maintain();
            break;
          case ($this->input->action == "delete"):
            $this->input_delete();
            break;
          case ($this->input->action == "list"):
            if (!$this->input->report_category_id) $forms->error('report_category_id');
            break;
          case ($this->input->action == "cancel"):
            unset($_SESSION['maintain_subcategory']);
            $forms->message[]="Request cancelled";
            break;
          case (!isset($_SESSION['maintain_subcategory'])):
            break;
          case ($this->input->action == "update"):
            $this->input_update();
            break;
        }
    }
    function input_maintain() {
        global $database, $forms, $menu;
        if (!$database->subcategory->data->id) $database->subcategory->data->category_id=$this->input->report_category_id;
        $_SESSION['maintain_subcategory']=array();
        foreach ($menu->language as $language) {
            $_SESSION['maintain_subcategory'][$language->code]=array();
        }
        $query=array("select * from content");
        $query[]="where record_type='subcategory'";
        $query[]="and record_id=" . fn_escape($this->input->record_id);
        $database->content->query($query);
        while ($database->content->fetch = $database->content->fetch_array() ) {
            $database->content->fetch();
            if (!array_key_exists($database->content->data->language_code,$_SESSION['maintain_subcategory'])) $_SESSION['maintain_subcategory'][ $database->content->data->language_code ]=array();
            if ($database->content->data->record_item) {
                $record_item=$database->content->data->record_item;
              } else {
                $record_item="name";
            }
            $_SESSION['maintain_subcategory'][ $database->content->data->language_code ][$record_item]=$database->content->data;
        }
    }
    function input_delete() {
        global $database, $forms;
        $query_where=array();
        $query_where[]=new query_where("record_type","=","subcategory");
        $query_where[]=new query_where("record_id","=",$this->input->record_id);
        $query=array("delete from content");
        $query[]=$database->where($query_where);
        $database->temp->query($query);
        foreach ($database->subcategory->data->images as $image) {
            if ($image) @unlink($image);
        }
        $database->subcategory->delete($this->input->record_id);
        $forms->message[]="Subcategory " . $database->subcategory->data->name . " deleted";
    }
    function input_update() {
        global $database, $forms, $menu;
        $database->subcategory->data->documents=array();
        $database->subcategory->data->category_id=$_POST['category_id'];
        if (!$database->subcategory->data->category_id) $forms->error('category_id');
        foreach ($menu->language as $language_code => $language) {
            $field_name=fn_base64(array("name",$language_code));
            $field_description=fn_base64(array("description",$language_code));
            $this->content=(array_key_exists("name",$_SESSION['maintain_subcategory'][$language->code])) ? $_SESSION['maintain_subcategory'][$language->code]["name"] : new content_data_class($language->code);
            $this->content->name=trim($_POST[$field_name]);
            $this->content->description=trim($_POST[$field_description]);
            if ($language->code == "EN") {
                $database->subcategory->data->name=$this->content->name;
                if (!$database->subcategory->data->name) $forms->error($field_name);
            }
            $_SESSION['maintain_subcategory'][$language->code]["name"]=$this->content;
        }
        $database->subcategory->data->currency=array();
        foreach ($menu->currency as $code => $obj) {
            if ( ($code == "USD") || (!$_POST[fn_base64(array("currency",$code,"override"))]) )continue;
            $field=fn_base64(array("currency",$code,"adder_pct"));
            $database->subcategory->data->currency[$code]=fn_number($_POST[$field] . "%",2);
            if ( ($database->subcategory->data->currency[$code] <= -100) || ($database->subcategory->data->currency[$code] >= 10) ) $forms->error($field,"Allowed range: -99% - + 1000%");
        }
        $database->subcategory->data->custom=intval($_POST['custom']);
        $this->load_tabs(array("htm","html","txt","pdf","jpg","png","gif"));
        $database->subcategory->data->output=$_POST['output'];
        $database->subcategory->data->options=array();
        foreach ($database->subcategory->constant->options as $key => $value) {
            $text=fn_text($_POST[ fn_base64(array("options",$key),TRUE) ]);
            if (strlen($text)) $database->subcategory->data->options[$key]=$text;
        }
        $database->subcategory->data->leadtime_option=$_POST['leadtime_option'];
        if ($database->subcategory->data->leadtime_option == "Weeks") {
            $database->subcategory->data->leadtime_text=trim($_POST['leadtime_text']);
            if (!strlen($database->subcategory->data->leadtime_text)) $forms->error("leadtime_text");
            } else {
            $database->subcategory->data->leadtime_text="";
        }
        $this->load_content("overview",$database->subcategory->constant->overview,array("htm","html","txt","pdf","jpg","png","gif"));
        $this->load_image("image");
        $this->load_image("thumbnail");
        $this->load_image("sketch");
        $database->subcategory->data->prj=urldecode(trim($_POST['prj']));
        if (!prj_verify($database->subcategory->data->prj)) $forms->error('prj', "Invalid PRJ Format");
        $database->subcategory->data->parameters=array();
        for ($parameter_id=0; $parameter_id < $database->subcategory->constant->parameters; $parameter_id++) {
            $parameter_name=trim($_POST[fn_base64(array("parameter", "name", $parameter_id))]);
            $parameter_type=$_POST[fn_base64(array("parameter", "type", $parameter_id))];
            $parameter_hyperlink=$_POST[fn_base64(array("parameter", "hyperlink", $parameter_id))];
            if (!$parameter_name) break;
            $database->subcategory->data->parameters[]=new subcategory_parameter_class($parameter_id, $parameter_name, $parameter_type, $parameter_hyperlink);
        }
        $database->subcategory->data->parameter_search=array();
        $database->subcategory->data->industry=array();
        $database->subcategory->data->portal=array();
        foreach ($_POST as $key => $value) {
            switch (TRUE) {
              case (preg_match("/^parameter_search_/",$key)):
                $database->subcategory->data->parameter_search[]=$value;
                break;
              case (preg_match("/^industry_/",$key)):
                $database->subcategory->data->industry[]=$value;
                break;
              case (preg_match("/^portal_/",$key)):
                $database->subcategory->data->portal[]=$value;
                break;
            }
        }
        if ( (!sizeof($database->subcategory->data->industry)) && (!sizeof($database->subcategory->data->portal)) ) $forms->error('industry_portal','','No industry/portals selected');
        $database->subcategory->data->search_words=array();
        $search_words=explode("\n",$_POST['search_words']);
        foreach ($search_words as $search_word) {
            $search_word = strtolower($search_word);
            if ( ($search_word) && (!in_array($search_word,$database->subcategory->data->search_words)) ) $database->subcategory->data->search_words[]=$search_word;
        }
        $database->subcategory->data->sort_order=fn_number($_POST['sort_order'],0);
        $database->subcategory->data->new_date=fn_date($_POST['new_date'],"mdy");
        if ( ($database->subcategory->data->new_date) && ($database->subcategory->data->new_date > date("Y/m/d")) ) $forms->error('new_date');
        $database->subcategory->data->prj_landing_page=$_POST['prj_landing_page'];
        $this->load_content("download",$database->subcategory->constant->download_count);
        $database->subcategory->data->download=array();
        for ($i=0; $i < $database->subcategory->constant->download_count; $i++) {
            $field_name=fn_base64(array("download","inventory",$i));
            $database->subcategory->data->download[$i]=trim($_POST[$field_name]);
        }
        $database->subcategory->data->page_break=$_POST['page_break'];
        $database->subcategory->data->status=$_POST['status'];
        $database->subcategory->data->lookup=intval($_POST['lookup']);
        switch (TRUE) {
          case (!$database->subcategory->data->output):
            break;
          case (!$database->subcategory->data->prj):
            $forms->error('prj',"PRJ required for Static/Smart SKU");
            break;
          case ($database->subcategory->data->output == "smart"):
            $file="classes/configure_" . $database->subcategory->data->id . ".php";
            if (!file_exists($file)) $forms->error('output',"{$file} not found");
        }
        if (sizeof($forms->error)) return;
        $database->subcategory->update($database->subcategory->meta->rows);
        switch (TRUE) {
          case (!$database->subcategory->meta->error):
            break;
          case (preg_match("/prj/i", $database->subcategory->meta->error)):
            $forms->error("prj", "Duplicate PRJ");
            return;
          case (preg_match("/prj/i", $database->subcategory->meta->error)):
            $forms->error("name", "Duplicate Name");
            return;
          default:
            $forms->error("name", $database->subcategory->meta->error);
            return;
        }
        if (!$this->input->record_id) {
            $database->subcategory->read($database->subcategory->data->id);
            $database->subcategory->image_rename();
            $database->subcategory->update(TRUE);
        }
        foreach ($_SESSION['maintain_subcategory'] as $language_code => $language_item) {
            foreach ($language_item as $document => $database->content->data) {
                switch (TRUE) {
                  case ( ($database->content->data->name) || ($database->content->data->description) ):
                    $database->content->data->record_type="subcategory";
                    $database->content->data->record_id=$database->subcategory->data->id;
                    $database->content->data->revised=strtotime("now");
                    $database->content->update($database->content->data->id);
                    break;
                  case ($database->content->data->id):
                    $database->content->delete($database->content->data->id);
                    break;
                }
            }
        }
        $forms->message[]="Record maintained";
        if ( ($database->subcategory->data->output == "smart") && ($_POST['inventory_delete']) ) {
            $query=array("delete from inventory");
            $query[]="where subcategory_id=" . fn_escape($_REQUEST['subcategory_id']);
            $database->temp->query($query);
            $forms->message[]=number_format($database->temp->affected_rows()) . " SKUS deleted";
        }
        if (!$database->subcategory->data->lookup) {
            $query=array("update inventory");
            $query[]="set prj=NULL";
            $query[]="where subcategory_id=" . fn_escape($database->subcategory->data->id);
            $database->temp->query($query);
        }
        unset($_SESSION['maintain_subcategory']);
    }
    function load_image($type) {
        global $database, $forms;
        $upload="{$type}_upload";
        $delete="{$type}_delete";
        $url="{$type}_url";
        $image=array();
        $image['folder']="/images_subcategory/";
        $image['type']=$type;
        if ($this->input->record_id) {
            $image['suffix']=$this->input->record_id;
          } else {
              $image['suffix']="_upload";
        }
        $image['period']=".";
        switch (TRUE) {
          case ($_FILES[$upload]['name']):
            $image['filetype']=end(preg_split("/\./",strtolower($_FILES[$upload]['name'])));
            switch (TRUE) {
              case ($_FILES[$upload]['error']):
                $forms->error($upload,"Upload error #" . $_FILES[$upload]['error']);
                break;
              case (!$_FILES[$upload]['size']):
                $forms->error($upload,"Empty file");
                break;
              case (!in_array($image['filetype'],array("jpg","png","gif"))):
                $forms->error($upload,"Invalid file type: <b>$filetype</b>");
                break;
              default:
                $database->subcategory->data->$url=implode("",$image);
                $database->subcategory->data->images[$url]=$_SERVER['DOCUMENT_ROOT'] . $database->subcategory->data->$url;
                if ( ($database->subcategory->data->images[$url]) && (file_exists($database->subcategory->data->images[$url])) ) unlink($database->subcategory->data->images[$url]);
                move_uploaded_file($_FILES[$upload]['tmp_name'], $database->subcategory->data->images[$url]);
                $imagesize=getimagesize($database->subcategory->data->images[$url]);
                switch (TRUE) {
                    case ( ($type=="image") && ($_POST['thumbnail_create']) ):
                    $image['type']="thumbnail";
                    $database->subcategory->thumbnail();
                    break;
                  case ($type=="thumbnail"):
                    if ( ($imagesize[0] != $database->registry->local->thumbnail) || ($imagesize[1] != $database->registry->local->thumbnail) ) {
                        $database->subcategory->data->$url="";
                        $forms->error($upload,"Thumbnail size error");
                    }
                    break;
                }
            }
            break;
          case ( ($type=="thumbnail") && ($_POST['thumbnail_create']) ):
            break;
          case ($_POST[$delete]):
              if ( ($database->subcategory->data->images[$url]) && (file_exists($database->subcategory->data->images[$url])) ) unlink($database->subcategory->data->images[$url]);
            $database->subcategory->data->$url="";
            unset($database->subcategory->data->images[$url]);
            break;
          case ($_POST[$upload]):
              $database->subcategory->data->$url=$_POST[$upload];
            $database->subcategory->data->images[$url]=$_SERVER['DOCUMENT_ROOT'] . $_POST[$upload];
            break;
        }
    }
    function load_content($record_type, $count, $types=array()) {
        global $database, $forms, $menu;
        for ($i=0; $i < $count; $i++) {
            foreach ($menu->language as $language_code => $language) {
                $field_name=fn_base64(array($record_type, "name", $language_code, $i));
                $field_file=fn_base64(array($record_type, "file", $language_code, $i));
                $text=array($record_type);
                $text[]=$i;
                $record_item=implode(":", $text);
                $this->content=(array_key_exists($record_item,$_SESSION['maintain_subcategory'][$language_code])) ? $_SESSION['maintain_subcategory'][$language_code][$record_item] : new content_data_class($language->code);
                $this->content->record_item=$record_item;
                $this->content->name=trim($_POST[$field_name]);
                switch (TRUE) {
                  case (!$this->content->name):
                  case (!$_FILES[$field_file]['name']):
                    break;
                  case (!intval($_FILES[$field_file]['size'])):
                    $forms->error($field_file,"Empty file");
                    break;
                  case ($_FILES[$field_file]['error']):
                    $forms->error($field_file,"Error: " . $_FILES[$field_file]["error"]);
                    break;
                  default:
                    $tempname=tmpfile();
                    if (file_exists("scs.tmp")) unlink("scs.tmp");
                    move_uploaded_file($_FILES[$field_file]['tmp_name'],"scs.tmp");
                    $this->content->content=file_get_contents("scs.tmp");
                    unlink("scs.tmp");
                    $database->content->fileinfo($this->content,$_FILES[$field_file]['name']);
                    if (!$this->content->meta->mime) $forms->error($field_file,"File type not supported");
                }
                switch (TRUE) {
                  case (!$this->content->name):
                    break;
                  case (!$this->content->meta->name):
                    $forms->error($field_file,"File required");
                    break;
                  case ( (sizeof($types)) && (!preg_match("/\.(" . implode("|",$types) . ")$/i",$this->content->meta->name)) ):
                    $forms->error($field_file,"File type must be " . implode(", ",$types));
                    break;
                }
                $_SESSION['maintain_subcategory'][$language_code][$record_item]=$this->content;
            }
        }
    }
    function load_tabs($types) {
        global $database, $forms, $menu;
        foreach ($database->subcategory->constant->tabs as $field => $field_name) {
            foreach ($menu->language as $language_code => $language) {
                $field_file=fn_base64(array($field, "file", $language_code));
                $field_name=fn_base64(array($field, "name", $language_code));
                $this->content=(array_key_exists($field,$_SESSION['maintain_subcategory'][$language_code])) ? $_SESSION['maintain_subcategory'][$language_code][$field] : new content_data_class($language->code);

                $this->content->record_item=$field;
                switch (TRUE) {
                  case (!$_FILES[$field_file]['name']):
                    break;
                  case (!intval($_FILES[$field_file]['size'])):
                    $forms->error($field_file,"Empty file");
                    break;
                  case ($_FILES[$field_file]['error']):
                    $forms->error($field_file,"Error: " . $_FILES[$field_file]["error"]);
                    break;
                  default:
                    $this->content->name="Specials";
                    $this->content->record_item="specials";
                    $tempname=tmpfile();
                    if (file_exists("scs.tmp")) unlink("scs.tmp");
                    move_uploaded_file($_FILES[$field_file]['tmp_name'],"scs.tmp");
                    $this->content->content=file_get_contents("scs.tmp");
                    unlink("scs.tmp");
                    $database->content->fileinfo($this->content,$_FILES[$field_file]['name']);
                    if (!$this->content->meta->mime) $forms->error($field_file,"File type not supported");
                }
                $this->content->name=fn_text($_POST[$field_name]);
                $_SESSION['maintain_subcategory'][$language_code][$field]=$this->content;
            }
        }
    }
    function html() {
        global $database, $forms, $menu;
        $database->user->access("Administrator");
        $forms->title("Subcategory Manager", key($this->scs_table_version()));
        $this->input();
        $menu->head();
        print $forms->message();
        print $this->js();
        print $forms->open(array("data"=>TRUE));
        print $forms->hidden("action");
        print $forms->hidden("record_id");

        switch (TRUE) {
          case ($this->input->action == "maintain"):
          case (($this->input->action == "update") && (sizeof($forms->error))):
            print $forms->hidden("report_category_id",$this->input->report_category_id);
            print $forms->hidden("report_status",$this->input->report_status);
            print $forms->hidden("report_images",$this->input->report_images);
            $this->tabs=new jquery_tab_class();
            if (isset($_SESSION['scscpq_wp'])) $this->tabs->meta->options->lineheight="auto";
            $this->tabs->item("Name", $this->html_name());
            $this->tabs->item("Currency",$this->html_currency());
            $this->tabs->item("Access",$this->html_access());
            $this->tabs->item("Catalog",$this->html_catalog());
            $this->tabs->item("Overview",$this->html_overview());
            $this->tabs->item("Parameters",$this->html_parameters());
            $this->tabs->item("Search",$this->html_search());
            $this->tabs->item("Images",$this->html_images());
            if ($this->input->report_category_id) {
                $this->tabs->item("Subcategory Download",$this->html_download_subcategory());
                $this->tabs->item("Inventory Download",$this->html_download_inventory());
            }
            $this->tabs->output();
            $buttons=array();
            $buttons[]=$forms->button("Update",array("onclick"=>"fn_action('update'," . fn_escape($database->subcategory->data->id) . ",'');"));
            $buttons[]=$forms->button("Cancel",array("onclick"=>"fn_action('cancel','0','');"));
            print "<p>" . implode(" ",$buttons) . "</p>";
            break;
          default:
            print implode("", $this->html_list());
        }
        print $forms->close();
        $menu->copyright();
    }
    function html_list() {
        global $database, $forms;
        $results=array();
        $results[]="<table class='large noborder'>";
        $results[]="<tr>";
        $results[]="<th>Category</th>";
        $results[]="<th>Status</th>";
        $results[]="<th>Images</th>";
        $results[]="<th>&nbsp;</th>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td class=center>" . $database->category->select($this->input->report_category_id,array("field"=>"report_category_id")) . "</td>";
        $results[]="<td class=center>" . $forms->select("report_status",$database->subcategory->constant->status,$this->input->report_status) . "</td>";
        $results[]="<td class=center>" . $forms->select("report_images",$this->image_list,$this->input->report_images) . "</td>";
        $results[]="<td class=center>" . $forms->button("List",array("onclick"=>"fn_action('list','','');")) . "</td>";
        $results[]="</tr>";
        $results[]="</table>";
        $colspan=6;
        $results[]="<table class='large border tablesorter'>";
        $results[]="<caption>&nbsp;</caption>";
        $results[]="<thead>";
        $results[]="<tr>";
        $results[]="<th width='50%'>Name</th>";
        $results[]="<th width='20%'>Output</th>";
        $results[]="<th width='10%'>Reverse Part#</th>";
        $results[]="<th width='10%'>Status</th>";
        $results[]="<th width='10%'>Currency Adder</th>";
        switch ($this->input->report_images) {
          case "Thumbnail":
          case "Both":
            $colspan++;
            $results[]="<th width='100'>Thumbnail</th>";
        }
        switch ($this->input->report_images) {
          case "Sketch":
          case "Both":
            $colspan++;
            $results[]="<th width='100'>Sketch</th>";
        }
        $results[]="<th width='10%'>Del?</th>";
        $results[]="</tr>";
        $results[]="</thead>";
        $results[]="<tr>";
        $results[]="<td colspan=$colspan>" . fn_href("Add new","javascript:fn_action('maintain','0','');") . "</td>";
        $results[]="</tr>";
        $results[]="<tbody>";

        if (($this->input->action == "list") && (!sizeof($forms->error))) {
            $query_where=array();
            if ($this->input->report_category_id) $query_where[]=new query_where("subcategory.category_id","=",$this->input->report_category_id);
            if ($this->input->report_status) $query_where[]=new query_where("subcategory.status","=",$this->input->report_status);
            $query=array("select * from subcategory");
            $query[]=$database->where($query_where);
            $query[]="order by sort_order,name";
            $database->subcategory->query($query);
            while ($database->subcategory->fetch = $database->subcategory->fetch_array() ) {
                $database->subcategory->fetch();
                switch ($database->subcategory->data->status) {
                  case "Inactive":
                    $bg=$forms->background->darksalmon;
                    break;
                  case "Pending":
                    $bg=$forms->background->cornsilk;
                    break;
                  default:
                    $bg="";
                }
                $results[]="<tr {$bg}>";
                $results[]="<td>" . fn_href($database->subcategory->data->name,"javascript:fn_action('maintain'," . fn_escape($database->subcategory->data->id) . ",'');") . "</td>";
                $results[]="<td>{$database->subcategory->constant->output[$database->subcategory->data->output]}</td>";
                $results[]="<td class=center>" . (($database->subcategory->data->lookup) ? "Yes" : "No") . "</td>";
                $results[]="<td>{$database->subcategory->data->status}</td>";
                $text=array();
                foreach ($database->subcategory->data->currency as $code => $addon_pct) {
                    $text[]="{$code}: " . number_format($addon_pct * 100,2) . "%";
                }
                $results[]="<td>" . implode("<br>",$text) . "</td>";

                switch ($this->input->report_images) {
                  case "Thumbnail":
                  case "Both":
                    switch (TRUE) {
                      case ($database->subcategory->data->thumbnail_url):
                        $text=$forms->img($database->subcategory->data->thumbnail_url,array("alt"=>"Thumbnail"));
                        break;
                      case ($database->subcategory->data->image_url):
                        $text=$forms->img($database->subcategory->data->image_url,array("alt"=>"Resized image","width"=>100,"height"=>100),array("resize"=>TRUE));
                        break;
                      default:
                        $text="&nbsp;";
                    }
                    $results[]="<td>{$text}</td>";
                }
                switch ($this->input->report_images) {
                  case "Sketch":
                  case "Both":
                    $results[]="<td>" . $forms->img($database->subcategory->data->sketch_url,array("alt"=>"Sketch","width"=>100,"height"=>100),array("resize"=>TRUE)) . "</td>";
                }
                if ($database->subcategory->data->status == "Inactive" ) {
                    $text=$forms->checkbox_delete($database->subcategory->data->id, $database->subcategory->data->name);
                  } else {
                    $text="&nbsp;";
                }
                $results[]="<td class=center>$text</td>";
                $results[]="</tr>";
            }
        }
        $results[]="</tbody>";
        $results[]="</table>";
        return $results;
    }
    function html_name() {
        global $database, $forms, $menu;
        $results=array();
        $results[]=$forms->hidden("subcategory_id",$this->input->record_id);
        $results[]="<table class='large noborder'>";
        foreach ($menu->language as $language_code => $language) {
            $field_name=fn_base64(array("name",$language_code));
            $field_description=fn_base64(array("description",$language_code));
            $this->content=$_SESSION['maintain_subcategory'][$language_code]["name"];
            $results[]="<tr>";
            $results[]="<td width='30%'>" . $language->name . " Name</td>";
            $text=array();
            $text[]=$forms->text($field_name,$this->content->name,60,60);
            if ($forms->error_text[$field_name]) $text[]=$forms->error_text[$field_name];
            $results[]="<td width='70%'>" . implode("<br>",$text) . "</td>";
            $results[]="</tr>";
            $results[]="<tr><td colspan=2><br></td></tr>";
            $results[]="<tr>";
            $results[]="<td width='30%'>" . $language->name . " Description</td>";
            $text=array();
            $text[]=$forms->textarea($field_description,$this->content->description,6,80);
            if ($forms->error_text[$field_description]) $text[]=$forms->error_text[$field_description];
            $results[]="<td width='70%'>" . implode("<br>",$text) . "</td>";
            $results[]="</tr>";
        }
        $results[]="<tr>";
        $results[]="<td>Lead Time</td>";
        $text=array();
        $text[]=$forms->select("leadtime_option",$database->subcategory->constant->leadtime_option,$database->subcategory->data->leadtime_option,FALSE,array("onchange"=>"fn_leadtime_option();"));
        $text[]=$forms->span($forms->text("leadtime_text",$database->subcategory->data->leadtime_text,8,8),array("id"=>"leadtime_text_span"));
        $results[]="<td>" . implode(" ",$text) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>Output</td>";
        $text=array($forms->select("output",$database->subcategory->constant->output,$database->subcategory->data->output,FALSE,array("onchange"=>"fn_output();")));
        if ($forms->error_text['output']) $text[]=$forms->error_text['output'];
        $results[]="<td>" . implode(" ",$text) . "</td>";
        $results[]="</tr>";
        $results[]="<tr id='inventory_delete_tr'>";
        $results[]="<td id='inventory_delete_td'></td>";
        $results[]="<td>" . $forms->checkbox("inventory_delete",1,0) . "</td>";
        $results[]="</tr>";
        $results[]="<tr><td colspan=2>Product Range Titles</td></tr>";
        foreach ($database->subcategory->constant->options as $key => $value) {
            $results[]="<tr>";
            $results[]="<td>&bull; {$value}</td>";
            $results[]="<td>" . $forms->text(fn_base64(array("options",$key)),$database->subcategory->data->options[$key],20,20) . "</td>";
            $results[]="</tr>";
        }
        if ($database->subcategory->data->price_increase) {
            $results[]="<tr>";
            $results[]="<td>Last Price Increase</td>";
            $results[]="<td>" . date("m/d/Y h:i A",$database->subcategory->data->price_increase) . "</td>";
            $results[]="</tr>";
        }
        $results[]="</table>";
        return $results;
    }
    function html_currency() {
        global $database, $forms, $menu;
        $results=array();
        $results[]="<table class=border>";
        $results[]="<tr>";
        $results[]="<th>Name</th>";
        $results[]="<th>Current Adder%</td>";
        $results[]="<th>Override?</th>";
        $results[]="<th>New Adder</th>";
        $results[]="</tr>";
        foreach ($menu->currency as $code => $obj) {
            if ($code == "USD") continue;
            $results[]="<tr>";
            $results[]="<td>" . $obj->name . "</td>";
            $results[]="<td class=center>" . number_format($obj->adder_pct * 100,2) . "%</td>";
            $results[]="<td class=center>" . $forms->checkbox(fn_base64(array("currency",$code,"override")),1,array_key_exists($code, $database->subcategory->data->currency)) . "</td>";
            $field=fn_base64(array("currency",$code,"adder_pct"));
            $text=array($forms->text($field, $database->subcategory->data->currency[$code] * 100, 7, 7,"decimal:2") . "%");
            if ($forms->error_text[$field]) $text[]=$forms->error_text[$field];
            $results[]="<td>" . implode(" ",$text) . "</td>";
            $results[]="</tr>";
        }
        $results[]="</table>";
        return $results;
      }
      function html_access() {
        global $database, $forms, $menu;
        $industry=array();
        $database->industry->query("select * from industry where active=1 order by name");
        if ($database->industry->meta->rows) {
            $industry[]="<b>" . $forms->checkbox("industry",1,(($database->industry->meta->rows == sizeof($database->subcategory->data->industry)) ? 1 : 0),array("onclick"=>"fn_checked(this);")) . " All industries</b>";
            while ($database->industry->fetch = $database->industry->fetch_array() ) {
                $database->industry->fetch();
                $industry[]=$forms->checkbox("industry_" . $database->industry->data->id,$database->industry->data->id,(in_array($database->industry->data->id,$database->subcategory->data->industry) ? $database->industry->data->id : 0),array("class"=>"industry")) . " " . $database->industry->data->name;
            }
            $database->industry->free_result();
        }
        $portal=array();
        $database->portal->query("select * from portal where active=1 order by name");
        if ($database->portal->meta->rows) {
            $portal[]="<b>" . $forms->checkbox("portal",1,(($database->portal->meta->rows == sizeof($database->subcategory->data->portal)) ? 1 : 0),array("onclick"=>"fn_checked(this);")) . " All portals</b>";
            while ($database->portal->fetch = $database->portal->fetch_array() ) {
                $database->portal->fetch();
                $portal[]=$forms->checkbox("portal_" . $database->portal->data->id,$database->portal->data->id,(in_array($database->portal->data->id,$database->subcategory->data->portal) ? $database->portal->data->id : 0),array("class"=>"portal")) . " " . $database->portal->data->name;
            }
            $database->portal->free_result();
        }
        if ( (!sizeof($industry)) && (!sizeof($portal)) ) return;
        $results=array();
        $results[]="<table class='standard border'>";
        $results[]="<tr>";
        if (sizeof($industry)) $results[]="<th width='50%'>Industry</th>";
        if (sizeof($portal)) $results[]="<th width='50%'>Portal</th>";
        $results[]="</tr>";
        $results[]="<tr>";
        if (sizeof($industry)) $results[]="<td>" . implode("<br>",$industry) . "</td>";
        if (sizeof($portal)) $results[]="<td>" . implode("<br>",$portal) . "</td>";
        $results[]="</tr>";
        $results[]="</table>";
        return $results;
    }
    function html_catalog() {
        global $database, $forms, $menu;
        $results=array();
        $results[]="<table class='large border'>";
        $results[]="<tr>";
        $results[]="<th rowspan=2 width='10%'>Field</th>";
        foreach ($menu->language as $language_code => $language) {
            $results[]="<th colspan=2>{$language->name}</th>";
        }
        $results[]="</tr>";
        $results[]="<tr>";
        foreach ($menu->language as $language_code => $language) {
            $results[]="<th width='20%'>Name</th>";
            $results[]="<th width='25%'>Content</th>";
        }
        $results[]="</tr>";
        foreach ($database->subcategory->constant->tabs as $field => $name) {
            $results[]="<tr>";
            $results[]="<td>{$name}</td>";
            foreach ($menu->language as $language_code => $language) {
                $field_name=fn_base64(array($field, "name", $language_code));
                $field_file=fn_base64(array($field, "file", $language_code));
                $obj=(isset($_SESSION['maintain_subcategory'][$language_code][$field])) ? $_SESSION['maintain_subcategory'][$language_code][$field] : new content_data_class($language->code);
                $text=array();
                $text[]=$forms->text($field_name, $obj->name, 30, 30);
                if ($forms->error_text[$field]) $text[]=$forms->error_text[$field];
                $results[]="<td>" . implode("<br>", $text) . "</td>";
                $text=array();
                $text[]=$forms->file($field_file);
                if ($forms->error_text[$field]) $text[]=$forms->error_text[$field];
                if (strlen($obj->name)) {
                    $text[]="Name: {$obj->meta->name}";
                    $text[]="File type: {$obj->filetype}";
                    $text[]="Mime: {$obj->meta->mime}";
                    $text[]="Revised: " . date("m/d/Y h:i A", $obj->revised);
                }
                $results[]="<td>" . implode("<br>", $text) . "</td>";
            }
            $results[]="</tr>";
        }
        $results[]="</table>";
        return $results;
    }
    function html_overview() {
        global $database, $forms, $menu;
        $results=array();
        $results[]="<div id=overview_message></div>";
        $results[]="<table class='border subcategory_html'>";
        $text=array();
        $text[]="Name: " . $forms->text("overview_name","",40,80,"",array("class"=>"overview_input"));
        $text[]="Remove? " . $forms->checkbox("overview_remove", 1, 0, array("class"=>"overview_input"));
        $text[]="<span id=overview_buttons></span>";
        $text[]="Language: <span id=overview_language_name></span>";
        $results[]="<tr><td colspan=2>" . implode(" ", $text) . "</td></tr>";
        $results[]="<tr>";
        $results[]="<th width='50%'>HTML</th>";
        $results[]="<th width='50%'>Preview</th>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>" . $forms->textarea("overview_html", "", 20, 80, array("class"=>"overview_input")) . "</td>";
        $results[]="<td id=overview_output></td>";
        $results[]="</tr>";
        $results[]="</table>";
        $results[]="<table class='standard border'>";
        $results[]="<tr>";
        $results[]="<th rowspan=2>Item</th>";
        foreach ($menu->language as $language_code => $language) {
            $results[]="<th colspan=2>" . $language->name . "</th>";
        }
        $results[]="</tr>";
        $results[]="<tr>";
        foreach ($menu->language as $language) {
            $results[]="<th>Name</th>";
            $results[]="<th>Content</th>";
        }
        $results[]="</tr>";
        for ($i=0; $i < $database->subcategory->constant->overview; $i++) {
            $results[]="<tr>";
            $results[]="<td class=center>" . ($i + 1) . ".</td>";
            foreach ($menu->language as $language_code => $language) {
                $field_name=fn_base64(array("overview", "name", $language_code, $i));
                $field_file=fn_base64(array("overview", "file", $language_code, $i));
                $field_meta=fn_base64(array("overview", "meta", $language_code, $i));
                $record_item="overview:{$i}";
                $obj=(array_key_exists($record_item,$_SESSION['maintain_subcategory'][$language_code])) ? $_SESSION['maintain_subcategory'][$language_code][$record_item] : new content_data_class($language->code);
                $text=array($forms->text($field_name,$obj->name,40,80));
                if ($forms->error_text[$field_name]) $text[]=$forms->error_text[$field_name];
                $results[]="<td>" . implode("<br>",$text) . "</td>";
                $text=array();
                $buttons=array();
                $buttons[]=$forms->button("HTML",array("onclick"=>"subcategory_html('html_load', {$database->subcategory->data->id}," . fn_escape($record_item) . "," . fn_escape($language_code) . ");"));
                $buttons[]=$forms->file($field_file,40);
                $text[]="<p>" . implode(" ", $buttons) . "</p>";
                if (array_key_exists($field_file,$forms->error_text)) $text[]=$forms->error_text[$field_file];
                $text[]="<div id={$field_meta}>" . $this->html_overview_meta($obj) . "</div>";
                $results[]="<td>" . implode("",$text) . "</td>";
            }
            $results[]="</tr>";
        }
        $results[]="</table>";
        return $results;
    }
    function html_overview_meta($obj) {
        $results=array();
        if ($obj->meta->name) {
            $results[]="Name: {$obj->meta->name}";
            $results[]="File Type: " .  $obj->filetype;
            $results[]="Mime: " .  $obj->meta->mime;
            $results[]="Revised: " .  date("m/d/Y h:iA",$obj->revised);
        }
        return implode("<br>", $results);
    }
    function html_parameters() {
        global $database, $forms;
        $results=array();
        $results[]="<table class='large noborder'>";
        $results[]="<tr>";
        $results[]="<td width='30%'>Product Category</td>";
        $results[]="<td width='70%'>" . $database->category->select($database->subcategory->data->category_id) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>PARTcommunity PRJ</td>";
        $text=array($forms->textarea("prj",$database->subcategory->data->prj,4,60));
        if ($forms->error_text['prj']) $text[]=$forms->error_text['prj'];
        $results[]="<td>" . implode("<br>",$text) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>Reverse Part# Lookup?</td>";
        $results[]="<td>" . $forms->checkbox("lookup",1,$database->subcategory->data->lookup) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>Catalog landing page?</td>";
        $results[]="<td>" . $forms->checkbox("prj_landing_page",1,$database->subcategory->data->prj_landing_page) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>Output Order</td>";
        $results[]="<td>" . $forms->text("sort_order",$database->subcategory->data->sort_order,6,6) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>Search Words</td>";
        $results[]="<td>" . $forms->textarea("search_words",$database->subcategory->data->search_words,3,40) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>New Date</td>";
        $results[]="<td>" . $forms->text("new_date",$database->subcategory->data->new_date,10,10,"date:ymd") . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>Page break every " . number_format($database->query_limit) . " records?</td>";
        $results[]="<td>" . $forms->checkbox("page_break",1,$database->subcategory->data->page_break) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>Status</td>";
        $results[]="<td>" . $forms->select("status",$database->subcategory->constant->status,$database->subcategory->data->status,FALSE) . "</td>";
        $results[]="</tr>";
        $results[]="</table>";

        $results[]="<table class='large border'>";
        $results[]=$forms->caption();
        $results[]="<tr>";
        $results[]="<th width='33%'>Field</th>";
        $results[]="<th width='33%'>Type</th>";
        $results[]="<th width='33%'>Hyperlink?</th>";
        $results[]="</tr>";
        for ($parameter_id=0; $parameter_id < $database->subcategory->constant->parameters; $parameter_id++) {
            $parameter=(array_key_exists($parameter_id,$database->subcategory->data->parameters)) ? $database->subcategory->data->parameters[$parameter_id] : new subcategory_parameter_class($parameter_id);
            $results[]="<tr>";
            $results[]="<td>" . $forms->text(fn_base64(array("parameter", "name", $parameter_id)),$parameter->name,40,40) . "</td>";
            $results[]="<td class=center>" . $forms->select(fn_base64(array("parameter", "type", $parameter_id)),$database->subcategory->constant->parameter_type,$parameter->type,FALSE) . "</td>";
            $results[]="<td class=center>" . $forms->checkbox(fn_base64(array("parameter", "hyperlink", $parameter_id)), 1, $parameter->hyperlink) . "</td>";
            $results[]="</tr>";
        }
        $results[]="</table>";
        return $results;
    }
    function html_search() {
        global $database, $forms;
        if (!sizeof($database->subcategory->data->parameters)) return;
        $results=array();
        $results[]="<table class='standard noborder'>";
        $results[]=$forms->hidden("search_tab",1);
        if (array_key_exists("search_tab",$forms->error_text)) $results[]="<tr><td>" . $forms->error_text['search_tab'] . "</td><tr>";
        $parameter_search=array();
        foreach ($database->subcategory->data->parameter_search as $parameter_id) {
            if (!array_key_exists($parameter_id,$database->subcategory->data->parameters)) continue;
            $parameter=$database->subcategory->data->parameters[$parameter_id];
            $parameter_search[]=
                "<li class='ui-state-default'>" .
                $forms->checkbox("parameter_search_" . $parameter_id,$parameter_id,$parameter_id) .
                $parameter->name . "</li>";
        }
        foreach ($database->subcategory->data->parameters as $parameter_id => $parameter) {
            if (in_array($parameter_id,$database->subcategory->data->parameter_search)) continue;
                $parameter_search[]=
                    "<li class='ui-state-default'>" .
                    $forms->checkbox("parameter_search_" . $parameter_id,$parameter_id,-1) .
                    $parameter->name . "</li>";
        }
        $results[]="<tr><td><ol class='sortable standard' width='50%'>" . implode("",$parameter_search) . "</ol></td></tr>";
        $results[]="</table>";
        return $results;
    }
    function html_images() {
        global $database, $forms, $menu;
        $results=array();
        $results[]=$forms->hidden("image_upload",$database->subcategory->data->image_url);
        $results[]=$forms->hidden("thumbnail_upload",$database->subcategory->data->thumbnail_url);
        $results[]=$forms->hidden("sketch_upload",$database->subcategory->data->sketch_url);
        $results[]="<table class='large noborder'>";
        $results[]="<tr>";
        $results[]="<td width='30%'>Image URL</td>";
        $text=array($forms->file("image_upload",40));
        if ($forms->error_text['image_upload']) $text[]=$forms->error_text['image_upload'];
        $results[]="<td width='70%'>" . implode("<br>",$text) . "</td>";
        $results[]="</tr>";
        if ($database->registry->local->thumbnail) {
            $results[]="<tr>";
            $results[]="<td>Create thumbnail from upload?</td>";
            $results[]="<td>" . $forms->checkbox("thumbnail_create",1,0) . "</td>";
            $results[]="</tr>";
        }
        if ($database->subcategory->data->image_url) {
            $results[]="<tr>";
            $results[]="<td>Delete? " . $forms->checkbox("image_delete",1,0) . "</td>";
            $results[]="<td>" . $forms->img($database->subcategory->data->image_url,array("alt"=>"Image"),array("resize"=>TRUE,"height"=>200)) . "</td>";
            $results[]="</tr>";
        }
        if ($database->registry->local->thumbnail) {
            $results[]="<tr>";
            $results[]="<td>Thumbnail (" . $database->registry->local->thumbnail . " x " . $database->registry->local->thumbnail . ")</td>";
            $text=array($forms->file("thumbnail_upload",40));
            if ($forms->error_text['thumbnail_upload']) $text[]=$forms->error_text['thumbnail_upload'];
            $results[]="<td>" . implode("<br>",$text) . "</td>";
            $results[]="</tr>";
            if ($database->subcategory->data->thumbnail_url) {
                $results[]="<tr>";
                $results[]="<td>Delete? " . $forms->checkbox("thumbnail_delete",1,0) . "</td>";
                $results[]="<td>" . $forms->img($database->subcategory->data->thumbnail_url,array("alt"=>"Thumbnail")) . "</td>";
                $results[]="</tr>";
            }
        }
        $results[]="<tr>";
        $results[]="<td>Sketch URL</td>";
        $text=array($forms->file("sketch_upload",40));
        if ($forms->error_text['sketch_upload']) $text[]=$forms->error_text['sketch_upload'];
            $results[]="<td>" . implode("<br>",$text) . "</td>";
        $results[]="</tr>";
        if ($database->subcategory->data->sketch_url) {
            $results[]="<tr>";
            $results[]="<td>Delete? " . $forms->checkbox("sketch_delete",1,0) . "</td>";
            $results[]="<td>" . $forms->img($database->subcategory->data->sketch_url,array("alt"=>"Sketch","height"=>200),array("resize"=>TRUE)) . "</td>";
            $results[]="</tr>";
        }
        $results[]="</table>";
        return $results;
    }
    function html_download_subcategory() {
        global $database, $forms, $menu;
        $results=array();
        $results[]="<table class='standard border'>";
        $results[]="<tr>";
        $results[]="<th rowspan=2>Item</th>";
        foreach ($menu->language as $language_code => $language) {
            $results[]="<th colspan=2>" . $language->name . "</th>";
        }
        $results[]="</tr>";
        $results[]="<tr>";
        foreach ($menu->language as $language) {
            $results[]="<th>Name</th>";
            $results[]="<th>Content</th>";
        }
        $results[]="</tr>";
        for ($i=0; $i < $database->subcategory->constant->overview; $i++) {
            $results[]="<tr>";
            $results[]="<td class=center>" . ($i + 1) . ".</td>";
            foreach ($menu->language as $language_code => $language) {
                $field_name=fn_base64(array("download", "name", $language_code, $i));
                $field_file=fn_base64(array("download", "file", $language_code, $i));
                $record_item="download:{$i}";
                $this->content=(array_key_exists($record_item,$_SESSION['maintain_subcategory'][$language_code])) ? $_SESSION['maintain_subcategory'][$language_code][$record_item] : new content_data_class($language->code);
                $text=array();
                $text[]=$forms->text($field_name,$this->content->name,40,80);
                if (array_key_exists($field_name,$forms->error_text)) $text[]=$forms->error->text[$field_name];
                $results[]="<td>" . implode("<br>",$text) . "</td>";
                $text=array();
                $text[]=$forms->file($field_file,40);
                if (array_key_exists($field_file,$forms->error_text)) $text[]=$forms->error->text[$field_file];
                if ($forms->error_text[$field_file]) $text[]=$forms->error_text[$field_file];
                if ($this->content->meta->name) {
                    $text[]="Name: " .  $this->content->meta->name;
                    $text[]="File Type: " .  $this->content->filetype;
                    $text[]="Mime: " .  $this->content->meta->mime;
                    $text[]="Revised: " .  date("m/d/Y h:iA",$this->content->revised);
                }
                $results[]="<td>" . implode("<br>",$text) . "</td>";
            }
            $results[]="</tr>";
        }
        $results[]="</table>";
        return $results;
    }
    function html_download_inventory() {
        global $database, $forms;
        $results=array();
        $results[]="<table class='large noborder'>";
        for ($i=0; $i < $database->subcategory->constant->download_count; $i++) {
            $field_name=fn_base64(array("download", "inventory", $i));
            $results[]="<tr>";
            $results[]="<td>" . ($i +1) . ". " . $forms->text($field_name,$database->subcategory->data->download[$i],40,80) . "</td>";
            $results[]="</tr>";
        }
        $results[]="</table>";
        return $results;
    }
    function js() {
        return <<< EOT

<script>
function fn_action(action,record_id,name) {
    if (action=="delete") {
        jQuery("#delete" + record_id).prop("checked", false);
        if (!confirm("Delete: #" + record_id + " " + name)) return;
    }
    jQuery("#action").val(action);
    jQuery("#record_id").val(record_id);
    jQuery("#scs_form").submit();
}
jQuery(document).ready(function() {
    jQuery(".subcategory_html").hide();
    fn_leadtime_option();
});
function fn_checked(obj) {
    jQuery("input." + obj.id).prop("checked",obj.checked);
}
var sku_count=0;
jQuery(document).ready(function() {
    jQuery.ajax({
        type: "post",
        dataType: 'json',
        data: { action: "sku_count", subcategory_id: jQuery("#subcategory_id").val() },
        success:function(response) {
            sku_count=response['sku_count'];
            fn_output();
        }
    });
});
function fn_output() {
    jQuery("#inventory_delete_td").html("Delete " + sku_count + " SKU'S?");
    if (jQuery("#output").val() == "smart") {
        jQuery("#inventory_delete_tr").show();
        } else {
        jQuery("#inventory_delete_tr").hide();
    }
}
function fn_leadtime_option() {
    if (jQuery("#leadtime_option").val() == "Weeks") {
        jQuery("#leadtime_text_span").show();
        } else {
        jQuery("#leadtime_text_span").hide();
    }
}
function subcategory_html(action, subcategory_id, record_item, language_code) {
    switch (true) {
      case (action == "html_cancel"):
        text="Cancel update?";
        break;
      case (action == "html_reload"):
        text="Reload HTML?";
        break;
      case ( (action == "html_update") && (jQuery("#overview_remove").prop('checked')) ):
        text="Remove HTML?";
        break;
      default:
        text="";
    }
    if ( (text) && (!confirm(text)) ) return;

    input=scscpq_class_data('overview_input');
    input['action']=action;
    input['subcategory_id']=subcategory_id;
    input['record_item']=record_item;
    input['language_code']=language_code;

    jQuery.ajax({
        type: "post",
        dataType: 'json',
        data: input,
        success:function(response) {
            scscpq_fill(response);
            if (response['visible']) {
                jQuery(".subcategory_html").show();
              } else {
                jQuery(".subcategory_html").hide();
            }
        }
    });
}
function subcategory_download(action, subcategory_id, field_name) {
    if (!confirm("Download File?")) return;
    url=new URL(window.location.href);
    url.searchParams.set("action", action);
    url.searchParams.set("subcategory_id", subcategory_id);
    url.searchParams.set("field_name", field_name);
    window.open(url, "subcategory");
}
</script>

EOT;
    }
}
?>