<?php
class local_class {
    var $folder;
    var $folder_exists=0;
	function scs_table_version() {
		$results=array();
        $results['02/27/2024']="Initial release";
        return $results;
    }
	function __construct() {
        $this->folder=$_SERVER['DOCUMENT_ROOT'] . "/cron/";
        $this->folder_exists=is_dir($this->folder);
        if (preg_match("/^xmlhttprequest$/i",$_SERVER['HTTP_X_REQUESTED_WITH'])) {
            $this->json();
            } else {
            $this->html();
        }
    }
    function json() {
        $this->response=new stdClass();

        die(json_encode($this->response));
    }
    function html() {
        global $database, $forms, $menu;
        $forms->title("CRON Toolbox (" . key($this->scs_table_version()) . ")");
        $forms->message[]="CRON Folder: {$this->folder}";
        $menu->head();
        print $forms->message();
        print $this->html_folder();
        $menu->copyright();
    }
    function html_folder() {
        global $database, $forms;
        if (!$this->folder_exists) return "<p>Folder not found</p>";
        $items=array();
        if ( (property_exists($database->registry, "cron")) && (property_exists($database->registry->cron, "url")) ) {
            foreach ($database->registry->cron->url as $url) {
                $prefix=substr($url, 1, -4);
                $obj=new stdClass();
                $obj->cron=TRUE;
                $items[$prefix]=$obj;
            }

        }
        $files=scandir($this->folder);
        foreach ($files as $file) {
            if (!preg_match("/(.php|.htm)$/i", $file)) continue;
            list($prefix, $suffix)=explode(".", $file);
            if (array_key_exists($prefix, $items)) {
                $obj=$items[$prefix];
              } else {
                $obj=new stdClass();
                $items[$prefix]=$obj;
            }
            $obj->$suffix=$file;
        }
        ksort($items);
        $results=array();
        $results[]="<table class='border'>";
        $results[]="<tr>";
        $results[]="<th rowspan=2>File Prefix<br>Click to execute</th>";
        $results[]="<th rowspan=2>CRON?</th>";
        $results[]="<th colspan=2>PHP</th>";
        $results[]="<th colspan=2>HTML</th>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<th>MD5</th>";
        $results[]="<th>Timestamp</th>";
        $results[]="<th>View?</th>";
        $results[]="<th>Created</th>";
        $results[]="</tr>";
        foreach ($items as $prefix => $obj) {
            $results[]="<tr>";
            $file_php=(strlen($obj->php)) ? $this->folder . $obj->php : "";
            $file_htm=(strlen($obj->htm)) ? $this->folder . $obj->htm : "";
            $url=( ($file_php) && ($obj->cron) ) ? fn_href($prefix, "cron/{$prefix}.php", array(), array("target"=>"_blank")) : $prefix;
            $results[]="<td>{$url}</td>";
            $results[]="<td class=center>" . (($obj->cron) ? "Yes" : "No") . "</td>";
            if  (strlen($file_php)) {
                $results[]="<td>" . hash_file("md5", $file_php) . "</td>";
                $results[]="<td class=center>" . date("m/d/Y h:i A T", filemtime($file_php)) . "</td>";
              } else {
                $results[]="<td colspan=2 class=center>Not found</td>";
            }
            if  (strlen($file_htm)) {
                $text=(filesize($file_htm)) ? fn_href("View", "cron/{$prefix}.htm", array(), array("target"=>"_blank")) : "";
                $results[]="<td class=center>{$text}</td>";
                $results[]="<td class=center>" . date("m/d/Y h:i A T", filemtime($file_htm)) . "</td>";
              } else {
                $results[]="<td colspan=2 class=center>Not found</td>";
            }
            $results[]="</tr>";
        }
        $results[]="</table>";
        return implode("", $results);
    }
}
?>