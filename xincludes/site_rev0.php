<?php
if (!isset($database)) die;
require_once "classes/site.php";

$forms->title("Site Status Utility (Revised 11/30/2012)");
$action_list=array("compare"=>"Compare site to master list","master"=>"Master List","site"=>"Site List","scan"=>"Rescan Site","build"=>"Build master list");

$forms->report['action']=$_POST['action'];
$forms->report['folder']=$_POST['folder'];
$forms->report['drill_down']=$_POST['drill_down'];
$forms->report['upload']=$_POST['upload'];
$forms->report['type']=$_POST['type'];
$forms->report['timeout']=$_POST['timeout'];
$forms->report['elapsedtime']=$_POST['elapsedtime'];
$forms->report['md5']=$_POST['md5'];
$forms->report['date']=fn_date($_POST['date'],"mdy");
if (!$date_ok) $forms->error('date');

switch (TRUE) {
  case (!$forms->report['action']):
	$forms->report['timeout']=28;
	unset($_SESSION['site_stack']);
	unset($_SESSION['site_folders']);
	unset($_SESSION['site_files']);
    break;
   case (isset($_SESSION['site_files'])):
	$site_folders=unserialize($_SESSION['site_folders']);
	$site_files=unserialize($_SESSION['site_files']);
}
switch (TRUE) {
  case (!$forms->report['action']):
    break;
  case ($forms->report['action']=="scan"):
  case (isset($_SESSION['site_stack'])):
  case (!isset($_SESSION['site_folders'])):
	if (isset($_SESSION['site_stack'])) {
	    $site_stack=$_SESSION['site_stack'];
		unset($_SESSION['site_stack']);
	  } else {
	    $site_stack=array(".");
	    $site_folders=array($site_stack[0]);
	    $site_files=array();
	}
	$eof=FALSE;
	$timeout=strtotime("+ {$forms->report['timeout']} seconds");
	while (!$eof) {
	    $current_dir = array_pop($site_stack);
	    $current_contents = scandir($current_dir);
	    foreach ($current_contents as $current_file) {
        	$current_file=trim($current_file);
	        $file="{$current_dir}/{$current_file}";
	        switch (TRUE) {
	          case ($current_file == "."):
	          case ($current_file == ".."):
	          case (!$current_file):
	            break;
	          case (is_dir($file)):
	            $site_stack[]=$file;
	            $site_folders[]=$file;
	            break;
	          case (is_file($file)):
	            $database->site->data=new site_data_class($current_dir,$current_file);
				$site_files[$current_dir . "/" . $current_file]=$database->site->data;
	            break;
	        }
	    }
	    switch (TRUE) {
	      case (!sizeof($site_stack)):
	        $eof=TRUE;
	        break;
		  case (strtotime("now") > $timeout):
          	$eof=TRUE;
            $forms->report['elapsedtime']=$database->site->constant->elapsedtime($forms->report['elapsedtime']);
            $_SESSION['site_stack']=$site_stack;
            $_SESSION['site_folders']=serialize($site_folders);
            $_SESSION['site_files']=serialize($site_files);
			break;
	    }
	}
    if (isset($_SESSION['site_stack'])) break;
	usort($site_folders,"url_sort");
	uksort($site_files,"url_sort");
	$_SESSION['site_folders']=serialize($site_folders);
    $_SESSION['site_files']=serialize($site_files);
	$query="select * from site order by build_datetime limit 1";
	$database->site->query($query);
	$database->site->fetch(TRUE);
	$database->site->free_result();
	$_SESSION['build_datetime']=$database->site->data->build_datetime;
	$database->site->constant->elapsedtime($forms->report['elapsedtime']);
    break;
  case ($forms->report['action']=="build"):
	foreach ($site_files as $data) {
		$database->site->data=$data;
		$database->site->update();
    }
	$database->site->clear();
	$forms->message[]="Master List Rebuilt";
	$database->site->constant->elapsedtime();
	$_SESSION['build_datetime']=$database->site->constant->build_datetime;
    break;
}
switch (TRUE) {
  case (!$forms->report['action']):
  case (!$forms->report['upload']):
  case (sizeof($forms->error)):
	break;
   case ($forms->report['action']=="compare"):
	$compare_list=fn_compare_list();
	if (!sizeof($compare_list)) break;
	header("Content-type: text/csv");
	header("Content-Disposition: attachment; filename=compare.csv");
	header("Pragma: no-cache");
	header("Expires: 0");
	$fh = fopen("php://output", "w");
    $results=array("status","folder","file","type","master_date","master_size","master_md5","site_date","site_size","site_md5");
	fputcsv($fh,$results);
	foreach ($compare_list as $url => $item) {
	    $results=array();
	    $results["status"]="";
	    $results["folder"]="";
	    $results["file"]="";
	    $results["type"]="";
	    $results["master_date"]="";
	    $results["master_size"]="";
	    $results["master_md5"]="";
	    $results["site_date"]="";
	    $results["site_size"]="";
	    $results["site_md5"]="";
        switch (TRUE) {
          case (!isset($item['master'])):
            $results['status']="Add";
            $source='site';
            break;
          case (!isset($item['site'])):
            $results['status']="Delete";
            $source='master';
            break;
          default:
            $results['status']="Change";
            $source='master';
        }
        $results['folder']=$item[$source]->folder;
        $results['file']=$item[$source]->file;
        $results['type']=$item[$source]->type;
		if (isset($item['master'])) {
	        $results["master_date"]=date("m/d/Y H:i",$item['master']->modify_datetime);
	        $results["master_size"]=$item['master']->size;
	        $results["master_md5"]=$item['master']->md5;
        }
		if (isset($item['site'])) {
	        $results["site_date"]=date("m/d/Y H:i",$item['site']->modify_datetime);
	        $results["site_size"]=$item['site']->size;
	        $results["site_md5"]=$item['site']->md5;
        }
		fputcsv($fh,$results);
    }
	fclose($fh);
	die;
  case ($forms->report['action']=="master"):
  case ($forms->report['action']=="site"):
	header("Content-type: text/csv");
	header("Content-Disposition: attachment; filename={$forms->report['action']}.csv");
	header("Pragma: no-cache");
	header("Expires: 0");
	$fh = fopen("php://output", "w");
    $results=array("folder","file","type","date","size","md5");
	fputcsv($fh,$results);
    if ($forms->report['action'] == "master") {
	    $query_where=array();
	    if ($forms->report['type']) $query_where[]=new query_where("type","=",$forms->report['type']);
	    if ($forms->report['drill_down']) {
	        $query_where[]=new query_where("folder","like",$forms->report['folder'] . "%");
	      } else {
	        $query_where[]=new query_where("folder","=",$forms->report['folder']);
	    }
	    if ($forms->report['date']) $query_where[]=new query_where("modify_datetime",">=",strtotime($forms->report['date']));
	    $query=
	        "select * from site \n" .
	        $database->where($query_where) .
	        "order by folder,file";
	    $database->site->query($query);
	    while ($database->site->fetch = $database->site->fetch_array()) {
	        $database->site->fetch();
            $results=array();
            $results['folder']=$database->site->data->folder;
            $results['file']=$database->site->data->file;
            $results['type']=$database->site->data->type;
            $results["date"]=date("m/d/Y H:i",$database->site->data->modify_datetime);
            $results['size']=$database->site->data->size;
            $results['md5']=$database->site->data->md5;
            fputcsv($fh,$results);
	    }
	    $database->site->free_result();
	  } else {
		foreach ($site_files as $url => $item) {
			switch (TRUE) {
              case ($forms->report['drill_down']):
              case ($item->folder == $forms->report['folder']):
	            $results=array();
	            $results['folder']=$item->folder;
	            $results['file']=$item->file;
	            $results['type']=$item->type;
	            $results["date"]=date("m/d/Y H:i",$item->modify_datetime);
	            $results['size']=$item->size;
	            $results['md5']=$item->md5;
	            fputcsv($fh,$results);
			}
        }
    }
	fclose($fh);
	die;
}
if (!isset($_SESSION['site_stack'])) {
	if (sizeof($site_files)) {
	    $forms->message[]=number_format(sizeof($site_files)) . " files";
	    $forms->message[]=number_format(sizeof($site_folders)) . " folders";
	}
    if ($_SESSION['build_datetime']) $forms->message[]="Master List as of: " . date("F j, Y h:i A",$_SESSION['build_datetime']);
}
$menu->head();
print $forms->message();
print $forms->open();
switch (TRUE) {
  case (isset($_SESSION['site_stack'])):
  case (!sizeof($_SESSION['site_files'])):
    print $forms->hidden("action","scan");
    print $forms->hidden("elapsedtime",$forms->report['elapsedtime']);
	if (isset($_SESSION['site_stack'])) {
    	$text="Continue Scan";
	  } else {
      	$text="Scan Site";
	}
    print
    	"<p>Timeout in " . $forms->select_number("timeout",$forms->report['timeout'],0,60) . " seconds" .
        "<br><br>" . $forms->submit($text) .  "</p>\n";
	if (isset($_SESSION['site_stack'])) {
		print
        	"<p class=standard>" .
            number_format(sizeof($site_files)) . " files scanned<br>\n" .
            number_format(sizeof($site_folders)) . " folders scanned<br>\n" .
            number_format(sizeof($site_stack)) . " unscanned folders<br>" .
			implode("<br>\n",$_SESSION['site_stack']) . "</p>\n";
    }
	break;
  default:
	print $forms->hidden("timeout",$forms->report['timeout']);
	print "<table class='standard noborder'>\n";
    print "<tr>\n";
    print "<td width=30%>Action</td>\n";
    if (in_array($forms->report['action'],array("build","scan"))) {
		$action="";
	  } else {
		$action=$forms->report['action'];
    }
    print "<td width=70%>" . $forms->select("action",$action_list,$action,FALSE) . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Folder</td>\n";
    print "<td>" . $forms->select("folder",$site_folders,$forms->report['folder'],FALSE) . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Drill down?</td>\n";
    print "<td>" . $forms->checkbox("drill_down",1,$forms->report['drill_down']) . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Change Date</td>\n";
    print "<td>" . $forms->text("date",$forms->report['date'],10,10,"date:ymd") . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>File Type</td>\n";
    $type_list=array();
    $type_list["Web"]=array(""=>"");
    $type_list["Programs"]=array(""=>"");
    $type_list["Files"]=array(""=>"");
    $type_list["Miscellaneous"]=array(""=>"");
	foreach ($database->site->constant->type as $type => $item) {
		$type_list[$item->group][$type]=$item->name;
    }
    unset($type_list["Web"][""],$type_list["Programs"][""],$type_list["Files"][""],$type_list["Miscellaneous"][""]);
    print "<td>" . $forms->optgroup("type",$type_list,$forms->report['type']) . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Include MD5 Hash?</td>\n";
    print "<td>" . $forms->checkbox("md5",1,$forms->report['md5']) . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Upload results?</td>\n";
    print "<td>" . $forms->checkbox("upload",1,$forms->report['upload']) . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>&nbsp;</td>\n";
    print "<td>" . $forms->submit("Go!") . "</td>\n";
    print "</tr>\n";
	print "</table>\n";
}
switch (TRUE) {
  case (!$forms->report['action']):
  case (sizeof($forms->error)):
	break;
   case ($forms->report['action']=="compare"):
	$compare_list=fn_compare_list();
    $compare_total=array();
    $compare_total[]=new compare_total_class("Files Changed",$forms->background->darkseagreen);
    $compare_total[]=new compare_total_class("Files Added",$forms->background->silver);
    $compare_total[]=new compare_total_class("Files Deleted",$forms->background->yellow);
    if ($forms->report['md5']) {
    	$colspan=3;
	  } else {
      	$colspan=2;
	}
	print "<table class='small border'>\n";
	print $forms->caption();
	print "<tr>\n";
	if ($forms->report['drill_down']) print "<th rowspan=2>Folder</th>\n";
    print "<th rowspan=2>File</th>\n";
	if (!$forms->report['type']) print "<th rowspan=2>Type</th>\n";
    print "<th colspan=$colspan>Master List</th>\n";
    print "<th colspan=$colspan>Site</th>\n";
    print "</tr>\n";
	print "<tr>\n";
    print "<th>Change Date</th>\n";
    print "<th>Size</th>\n";
    if ($forms->report['md5']) print "<th>MD5</th>\n";
    print "<th>Change Date</th>\n";
    print "<th>Size</th>\n";
    if ($forms->report['md5']) print "<th>MD5</th>\n";
    print "</tr>\n";
    if (sizeof($compare_list)) {
	    foreach ($compare_list as $url => $item) {
            switch (TRUE) {
              case (!isset($item['master'])):
                $status=1;
                $source='site';
                break;
              case (!isset($item['site'])):
                $status=2;
                $source='master';
                break;
              default:
                $status=0;
                $source='master';
            }
            $compare_total[$status]->count++;
            print "<tr " . $compare_total[$status]->background . ">\n";
            if ($forms->report['drill_down']) print "<td>" . $item[$source]->folder . "</td>\n";
            print "<td>$url</td>";
            if (!$forms->report['type']) print "<td class='center'>" . $item[$source]->type . "</td>\n";
            if (!isset($item['master'])) {
                print "<td colspan=$colspan class=center><b>New File</b></td>\n";
              } else {
                print "<td class='center nobr'>" . date("m/d/Y h:i A",$item['master']->modify_datetime) . "</td>\n";
                print "<td class='right nobr'>" . number_format($item['master']->size) . "</td>\n";
                if ($forms->report['md5']) print "<td>" . $item['master']->md5 . "</td>\n";
            }
            if (!isset($item['site'])) {
                print "<td colspan=$colspan class=center><b>Deleted File</b></td>\n";
              } else {
                print "<td class='center nobr'>" . date("m/d/Y h:i A",$item['site']->modify_datetime) . "</td>\n";
                print "<td class='right nobr'>" . number_format($item['site']->size) . "</td>\n";
                if ($forms->report['md5']) print "<td>" . $item['site']->md5 . "</td>\n";
            }
            print "</tr>\n";
        }
	}
    print "</table>\n";
    $results="";
    foreach ($compare_total as $item) {
    	if ($item->count) {
        	if ($results) $results .= "&nbsp;&nbsp;";
            $results .= "<span " . $item->background . ">&nbsp;&nbsp;(" . number_format($item->count) . ") " . $item->name . "&nbsp;&nbsp;</span>";
        }
    }
	print "<p class='standard center'>";
	if ($results) {
    	print $results;
	  } else {
      	print "No Changes Found";
	}
    print "</p>\n";
	break;
   case ($forms->report['action']=="master"):
	print "<table class='small border'>\n";
	print "<caption>&nbsp;</caption>\n";
	print "<tr>\n";
	if ($forms->report['drill_down']) print "<th>Folder</th>\n";
    print "<th>File</th>\n";
	if (!$forms->report['type']) print "<th>Type</th>\n";
    print "<th>Change Date</th>\n";
    print "<th>Size</th>\n";
    if ($forms->report['md5']) print "<th>MD5</th>\n";
    print "</tr>\n";
    $query_order="folder,file";
	$query_where=array();
    if ($forms->report['type']) $query_where[]=new query_where("type","=",$forms->report['type']);
	if ($forms->report['drill_down']) {
		$query_where[]=new query_where("folder","like",$forms->report['folder'] . "%");
	  } else {
		$query_where[]=new query_where("folder","=",$forms->report['folder']);
	}
	if ($forms->report['date']) {
    	$query_where[]=new query_where("modify_datetime",">=",strtotime($forms->report['date']));
		$query_order="modify_datetime desc";
	}
    $query=
    	"select * from site \n" .
        $database->where($query_where) .
        "order by $query_order";
	$database->site->query($query);
    while ($database->site->fetch = $database->site->fetch_array()) {
		$database->site->fetch();
        print "<tr>\n";
		if ($forms->report['drill_down']) print "<td>" . $database->site->data->folder . "</td>\n";
        print "<td>" . $database->site->data->file . "</td>\n";
        if (!$forms->report['type']) print "<td class='center nobr'>" . $database->site->data->type . "</td>\n";
        print "<td class='center nobr'>" . date("m/d/Y h:i A",$database->site->data->modify_datetime) . "</td>\n";
        print "<td class='right nobr'>" . number_format($database->site->data->size) . "</td>\n";
        if ($forms->report['md5']) print "<td class='nobr'>" . $database->site->data->md5 . "</td>\n";
        print "</tr>\n";
    }
	$database->site->free_result();
    print "</table>\n";
    print "<p class='standard center'>" . number_format($database->site->meta->rows) . " file(s) found</p>\n";
	break;
   case ($forms->report['action']=="site"):
	$count=0;
	print "<table class='small border'>\n";
	print "<caption>&nbsp;</caption>\n";
	print "<tr>\n";
	if ($forms->report['drill_down']) print "<th>Folder</th>\n";
    print "<th>File</th>\n";
	if (!$forms->report['type']) print "<th>Type</th>\n";
    print "<th>Change Date</th>\n";
    print "<th>Size</th>\n";
    if ($forms->report['md5']) print "<th>MD5</th>\n";
    print "</tr>\n";
	foreach ($site_files as $item) {
    	switch (TRUE) {
          case ((!$forms->report['drill_down']) && ($item->folder != $forms->report['folder'])):
          case (($forms->report['drill_down']) && (substr($item->folder,0,strlen($forms->report['folder'])) != $forms->report['folder'])):
          case (($forms->report['type']) && ($item->type != $forms->report['type'])):
          case (($forms->report['date']) && ($item->modify_datetime < strtotime($forms->report['date']))):
          	break;
          default:
			$count++;
	        print "<tr>\n";
	        if ($forms->report['drill_down']) print "<td>" . $item->folder . "</td>\n";
	        print "<td>" . $item->file . "</td>\n";
	        if (!$forms->report['type']) print "<td class=center>" . $item->type . "</td>\n";
	        print "<td class='center nobr'>" . date("m/d/Y h:i A",$item->modify_datetime) . "</td>\n";
	        print "<td class='right nobr'>" . number_format($item->size) . "</td>\n";
	        if ($forms->report['md5']) print "<td class='nobr'>" . $item->md5 . "</td>\n";
	        print "</tr>\n";
		}
    }
    print "</table>\n";
    print "<p class='standard center'>" . number_format($count) . " file(s) found</p>\n";
  	break;
}
print $forms->close();
$menu->copyright();

function fn_compare_list() {
global $database,$forms,$site_files;
    $compare_list=array();
	$query_where=array();
    if ($forms->report['type']) $query_where[]=new query_where("type","=",$forms->report['type']);
	if ($forms->report['drill_down']) {
		$query_where[]=new query_where("folder","like",$forms->report['folder'] . "%");
	  } else {
		$query_where[]=new query_where("folder","=",$forms->report['folder']);
	}
    $query=
    	"select * from site \n" .
        $database->where($query_where) .
        "order by folder,file";
	$database->site->query($query);
    while ($database->site->fetch = $database->site->fetch_array()) {
		$database->site->fetch();
		$url=$database->site->data->folder . "/" . $database->site->data->file;
		$database->site->data->build_datetime=0;
        $compare_list[$url]['master']=$database->site->data;
	}
    $database->site->free_result();
	foreach ($site_files as $item) {
    	switch (TRUE) {
          case ((!$forms->report['drill_down']) && ($item->folder != $forms->report['folder'])):
          case (($forms->report['drill_down']) && (substr($item->folder,0,strlen($forms->report['folder'])) != $forms->report['folder'])):
          case (($forms->report['type']) && ($item->type != $forms->report['type'])):
          	break;
          default:
	        $url=$item->folder . "/" . $item->file;
			$item->build_datetime=0;
	        $compare_list[$url]['site']=$item;
		}
	}
    foreach ($compare_list as $key => $item) {
		switch (TRUE) {
          case (!isset($item['master'])):
          case (!isset($item['site'])):
          case ($item['master'] != $item['site']):
        	break;
          default:
			unset($compare_list[$key]);
		}
	}
	uksort($compare_list,"url_sort");
    return $compare_list;
}


class compare_total_class {
	var $count=0;
    var $name;
    var $background;
    function __construct($name, $background) {
		$this->name=$name;
        $this->background=$background;
    }
}
?>