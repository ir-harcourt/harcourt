<?php
require_once "scs_header.php";
$content_viewer=new content_viewer_class($_REQUEST['action']);

class content_viewer_class {
	var $action;
    var $debug=FALSE;
    var $options=array();
    var $results=array();
    function __construct($request) {
    global $database;
		$this->action=$_REQUEST['action'];
	    $this->results=array();
	    $this->results["Server:Date"]=date("m/d/Y h:i A");
	    $this->results["Server:Request Method"]=$_SERVER['REQUEST_METHOD'];
	    $this->results["Server:Referer"]=$_SERVER['HTTP_REFERER'];
	    $this->results["Server:IP Address"]=harcourt_remote_addr();
	    $this->results["User:ID"]=$_SESSION['user']->id;
	    $this->results["User:Type"]=$_SESSION['user']->type;
	    $this->results["User:Name"]=$_SESSION['user']->name;
	    $this->results["User:Language"]=$_SESSION['user']->language_code;
	    $this->results["User:Portal"]=$_SESSION['user']->portal_id;
	    foreach ($_REQUEST as $key => $value) {
	        $this->results["Request:" . $key]=$value;
	    }
	    switch (TRUE) {
	      case (array_key_exists("viewer",$_REQUEST)):
			$this->action("viewer");
			break;
	      case (array_key_exists("download",$_REQUEST)):
			$this->action("download");
			break;
		  default:
			$this->complete("Action not selected",TRUE);
	    }
        switch (TRUE) {
          case ($this->action=="download"):
            header('Content-type: ' . $database->content->data->meta->mime);
            header('Content-Disposition: attachment; filename="' . $database->content->data->meta->name . '"');
            header('Pragma: no-cache');
            header('Expires: 0');
            echo $database->content->data->content;
            $this->complete("Successful download");
            break;
          default:
            if ($this->action=="viewer") header('Content-type: ' . $database->content->data->meta->mime);
            echo $database->content->data->content;
            $this->complete("Successful view");
        }
    }
	function action($action) {
    global $database;
		if ($this->action) return;
	    $this->action=$action;
	    if (!$database->content->decode($_REQUEST[$action],TRUE)) $this->complete("Content not found","E2");
	}
	function complete($message="",$error="") {
    global $database;
		switch (TRUE) {
          case ($this->debug):
          case ($error):
	        $results=array();
            $text="";
            if ($error) {
            	$text="Error #{$error}";
			  } else {
              	$text="Success";
			}
			$results[]="$text => " . $message;
	        foreach ($this->results as $key => $value) {
	            $results[]="$key => $value";
	        }
			if ($database->content->data->id) {
				$results[]="Content:record_type => " . $database->content->data->record_type;
                if ($database->content->data->record_item) $results[]="Content:record_item => " . $database->content->data->record_item;
				$results[]="Content:filetype => " . $database->content->data->filetype;
				$results[]="Content:revised => " . date("m/d/Y h:i A",$database->content->data->revised);
                if ($database->content->data->meta->name) {
					foreach ($database->content->data->meta as $field => $value) {
                    	if (strlen($value)) $results[]="Meta:$field => " . $value;
                    }
				}
            }
            $results[]="";
            $results[]="";
	        $fh=fopen("scs_viewer.log",'a');
	        fwrite($fh,implode("\r\n",$results));
	        fclose($fh);
		}
	    if ($error) {
	        die("Content viewer parameters missing:" . $error);
		  } else {
			die;
	    }
	}
}
?>