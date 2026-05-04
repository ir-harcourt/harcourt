<?php
$forms->title("File Manager (Revised 05/18/2020)");
require_once "classes/site.php";
$site=new site_class();
$forms->constant->backup=array("date"=>"_" . date("Ymd"),"bak"=>"_bak","none"=>"");
$forms->report['action']=$_POST['action'];
$forms->report['option']=$_POST['option'];
$forms->report['folder']=$_POST['folder'];
$forms->report['folder_url']=substr($forms->report['folder'],2);
$forms->report['file']=$_POST['file'];
$forms->report['type']=$_POST['type'];
$forms->report['url']=$forms->report['folder_url'] . $forms->report['file'];
switch (TRUE) {
  case (!isset($_SESSION['site_folders'])):
  case (!$forms->report['action']):
  case ($forms->report['action']=="scan"):
	$site_stack=array(".");
	$site_folders=array();
	$eof=FALSE;
    while (!$eof) {
	    $current_dir=array_pop($site_stack);
        $site_folders[]="{$current_dir}/";
	    $current_contents=scandir($current_dir);
	    foreach ($current_contents as $current_file) {
        	$current_file=trim($current_file);
			$file="{$current_dir}/{$current_file}";
	        switch (TRUE) {
	          case (in_array($current_file,array("",".",".."))):
	            break;
	          case (is_dir($file)):
	            $site_stack[]=$file;
	            break;
	        }
	    }
	    switch (TRUE) {
	      case (!sizeof($site_stack)):
	        $eof=TRUE;
	        break;
	    }
    }
	sort($site_folders);
	$_SESSION['site_folders']=$site_folders;
	break;
}
switch (TRUE) {
  case ($forms->report['action']==""):
	break;
  case ($forms->report['action']=="cancel"):
    $forms->message[]="Request cancelled";
    break;
  case ($forms->report['action']=="delete"):
	if (file_exists($forms->report['url'])) unlink($forms->report['url']);
    $forms->message[]="Deleted: <b>" . $forms->report['url'] . "</b>";
    break;
  case ($forms->report['action']=="download"):
    header("Content-type: text/plain");
    header("Content-Disposition: attachment; filename=" . preg_replace("/\s/","_",$forms->report['file']));
    header("Pragma: no-cache");
    header("Expires: 0");
	$fh=fopen("php://output", "w");
	readfile($forms->report['url']);
    fclose($fh);
	die;
  case ($forms->report['action']=="maintain"):
  	if (!$forms->report['file']) {
    	$forms->report['option']="upload";
	  } else {
        $forms->report['filename']=$forms->report['file'];
	}
	break;
  case (($forms->report['action']=="update") && ($forms->report['option']=="move")):
	$forms->report['move_folder']=$_POST['move_folder'];
    $forms->report['move_overwrite']=$_POST['move_overwrite'];
    $forms->report['move_url']=substr($forms->report['move_folder'],2) . $forms->report['file'];
    switch (TRUE) {
	  case (!$forms->report['move_folder']):
      case ($forms->report['move_folder']==$forms->report['folder']):
		$forms->error("move_folder","Invalid folder name");
		break;
	  case ((file_exists($forms->report['move_url'])) && (!$forms->report['move_overwrite'])):
		$forms->error("move_folder","Cannot overwrite existing file");
		break;
	  default:
		$message="Move <b>" . $forms->report['url'] . "</b> to <b>" . $forms->report['move_url'] . "</b>";
		if (file_exists($forms->report['move_url'])) {
        	$message .= " (Overwrite)";
			unlink($forms->report['move_url']);
		}
        rename($forms->report['url'],$forms->report['move_url']);
        $forms->message[]=$message;
	}
	break;
  case ($forms->report['action']=="update"):
	$forms->report['filename']=trim($_POST['filename']);
	$forms->report['backup']=$_POST['backup'];
	$forms->report['overwrite']=$_POST['overwrite'];
	$forms->report['filetype']=$_POST['filetype'];
	if ((!$forms->report['option']) || ($forms->report['option']=="existing")) {
		$forms->message[]="No action taken";
    	break;
	}
    switch (TRUE) {
	  case ($forms->report['option'] == "upload"):
		$forms->report['upload_filename']=trim($_FILES['upload']['name']);
		$forms->report['filetype_upload']=end(explode(".", strtolower($forms->report['upload_filename'])));
		switch (TRUE) {
	      case (!intval($_FILES['upload']['size'])):
	        $forms->error('upload','File is empty');
	        break;
	      case ($_FILES['upload']['error'] != "0"):
	        $forms->error('upload','Upload error #' . $_FILES['upload']['error']);
	        break;
	      case (!$forms->report['filename']):
	        $forms->report['filename']=$forms->report['upload_filename'];
	        break;
	    }
	}
    if ($forms->report['upload_filename']) {
		$forms->report['upload_filename']=$_FILES['upload']['name'];
	  } else {
		$forms->report['filetype_upload']=end(explode(".", strtolower($forms->report['file'])));
    }
    if ($forms->report['file']) {
		$forms->report['filetype_local']=end(explode(".", strtolower($forms->report['file'])));
	  } else {
		$forms->report['filetype_local']=$forms->report['filetype_upload'];
    }
	$forms->report['filetype_filename']=end(explode(".", strtolower($forms->report['filename'])));
	$forms->report['filename_url']=$forms->report['folder_url'] . $forms->report['filename'];
	$backup_suffix=end(explode(".", strtolower($forms->report['filename'])));


    switch (TRUE) {
      case (!$forms->constant->backup[ $forms->report['backup'] ]):
		$forms->report['backup_url']="";
        break;
      case ($backup_suffix != $forms->report['filename']):
	    $backup_suffix = ".{$backup_suffix}";
		$forms->report['backup_url']=$forms->report['folder_url'] . preg_replace("/{$backup_suffix}$/",$forms->constant->backup[ $forms->report['backup'] ] . $backup_suffix,$forms->report['filename']);
		break;
      default:
		$forms->report['backup_url']=$forms->report['folder_url'] . $forms->report['filename'] . $forms->report['backup'];
	}
    switch (TRUE) {
	  case (!$forms->report['filename']):
      	$forms->error('filename','Cannot be blank');
      	break;
	  case (($forms->report['option'] != "upload") && ($forms->report['file'] == $forms->report['filename'])):
      	$forms->error('filename','Duplicate name');
		break;
	  case (preg_match("/[*\"\'\/\\\]/",$forms->report['filename'])):
      	$forms->error('filename','Special characters not allowed');
		break;
      case (($forms->report['file']) && ($forms->report['option'] == "upload")):
      	break;
	  case ((!$forms->report['overwrite']) && file_exists($forms->report['filename_url'])):
      	$forms->error('filename','Cannot overwrite existing file');
		break;
	}
    switch (TRUE) {
	  case ($forms->error['filename']):
      case ($forms->report['filetype']):
    	break;
	  case ($forms->report['filetype_upload'] != $forms->report['filetype_local']):
	  case ($forms->report['filetype_filename'] != $forms->report['filetype_local']):
      	$forms->error('filename','Cannot change file type');
		break;
    }
    if (sizeof($forms->error)) break;
	switch ($forms->report['option']) {
	  case "upload":
		$message="Upload <b>" . $forms->report['filename_url'] . "</b>";
		if (($forms->report['backup_url']) && (file_exists($forms->report['filename_url']))) {
			$message .= " (Backup existing file)";
			if (file_exists($forms->report['backup_url'])) unlink($forms->report['backup_url']);
            rename($forms->report['filename_url'],$forms->report['backup_url']);
        }
		if (file_exists($forms->report['filename_url'])) unlink($forms->report['filename_url']);
		move_uploaded_file($_FILES['upload']['tmp_name'],$forms->report['filename_url']);
        $forms->message[]=$message;
		break;
	  case "rename":
		if (file_exists($forms->report['filename_url'])) unlink($forms->report['filename_url']);
        rename($forms->report['url'],$forms->report['filename_url']);
		$forms->message[]="Rename <b>" . $forms->report['url'] . "</b> to <b>" . $forms->report['filename_url'] . "</b>";
		break;
	  case "copy":
		if (file_exists($forms->report['filename_url'])) unlink($forms->report['filename_url']);
        copy($forms->report['url'],$forms->report['filename_url']);
		$forms->message[]="Copy <b>" . $forms->report['url'] . "</b> to <b>" . $forms->report['filename_url'] . "</b>";
		break;
    }
}
$menu->head();
$forms->message();
?>
<script language=javascript>
function fn_action(obj,action,file) {
	switch (action) {
	  case '':
		return;
	  case 'download':
		obj.selectedIndex=0;
		break;
	  case 'delete':
	    if (!confirm("Delete " + file + "?")) {
        	obj.selectedIndex=0;
            return;
	    }
		break;
	}
    document.scs_form.action.value=action;
    document.scs_form.file.value=file;
    document.scs_form.submit();
}
function fn_option(file) {
	option=document.getElementById('option').value;
	if (option=="upload") {
		div_show('upload_id',true);
	  } else {
		div_show('upload_id',false);
    }
	if ((option == "copy") || (option == "rename") || (!file)) {
		div_show('file_id',true);
	  } else {
		div_show('file_id',false);
    }
	if (option=="move") {
		div_show('move_id',true);
	  } else {
		div_show('move_id',false);
    }
}
</script>
<?php
print $forms->open(array("data"=>TRUE));
print $forms->hidden("action");
print $forms->hidden("file");
switch (TRUE) {
  case (($forms->report['action'] == "update") && (sizeof($forms->error))):
  case ($forms->report['action'] == "maintain"):
	print $forms->hidden("folder",$forms->report['folder']);
	print $forms->hidden("type",$forms->report['type']);
	if ($forms->report['file']) {
		$results=array();
		$fileinfo=stat($forms->report['url']);
	    $imageinfo=getimagesize($forms->report['url']);
        $results['URL']=$forms->report['url'];
        $results['Size']=number_format($fileinfo['size']);
        $results['Change date']=date("F j, Y g:i A",$fileinfo['mtime']);
		if ($imageinfo['mime']) {
        	$results['Mime']=$imageinfo['mime'];
        	$results['Image size']=$imageinfo[3];
        	$results['Bits']=$imageinfo['bits'];
        	$results['Channels']=$imageinfo['channels'];
        }
	}
	print "<table class='standard noborder'>\n";
    print "<tr>\n";
    print "<td width=50%>\n";
    if ($forms->report['file']) {
        print "<p>Action: " . $forms->select("option",array(""=>"Keep existing","upload"=>"Upload new file","rename"=>"Rename file","copy"=>"Copy file","move"=>"Move to another folder"),$forms->report['option'],FALSE,array("onchange"=>"fn_option(" . fn_escape($forms->report['file']) . ");")) . "</p>\n";
      } else {
        print $forms->hidden("option","upload");
    }
    print "<p class=standard id=upload_id style='display:none;'>\n";
    print "Upload file: " . $forms->file("upload",60);
    if ($forms->error_text['upload']) print " <b>" . $forms->error_text['upload'] . "</b>\n";
    print "<br>Backup format: " . $forms->select("backup",array("date"=>"_YYYYMMDD","bak"=>"_bak","none"=>"No backup"),$forms->report['backup'],FALSE);
    print "</p>\n";

    print "<p class=standard id=move_id style='display:none;'>\n";
    print "Move to folder: " . $forms->select("move_folder",$_SESSION['site_folders'],$forms->report['move_folder']);
    if ($forms->error_text['move_folder']) print " <b>" . $forms->error_text['move_folder'] . "</b>\n";
    print "<br>Overwrite existing file? " . $forms->checkbox("move_overwrite",1,$forms->report['move_overwrite']);
    print "</p>\n";

    print "<p class=standard id=file_id style='display:none;'>\n";
    print "File name: " . $forms->text("filename",$forms->report['filename'],60);
    if ($forms->error_text['filename']) print " <b>" . $forms->error_text['filename'] . "</b>\n";
    print "<br>Overwrite existing file? " . $forms->checkbox("overwrite",1,$forms->report['overwrite']);
	print "<br>Change file type? " . $forms->checkbox("filetype",1,$forms->report['filetype']);
    print "</p>\n";
    print
        "<p>" . $forms->button("Update",array("onclick"=>"fn_action(this,'update'," . fn_escape($forms->report['file']) . ");")) .
        " " .  $forms->button("Cancel",array("onclick"=>"fn_action(this,'cancel','');")) . "</p>\n";
    print "</td>\n";
    if ($forms->report['file']) {
        print "<td width=50%>\n";
        print "<p class=standard>\n";
        foreach ($results as $key => $value) {
        	print "$key: <b>$value</b></br>\n";
        }
        print "</p>\n";
        print "</td>\n";
	}
    print "</tr>\n";
    print "</table>\n";
	if ($imageinfo['mime']) print "<p>" . $forms->img($forms->report['url'],array(),array("width"=>500,"resize"=>TRUE)) . "</p>\n";
	print "<script language=javascript>\n";
    print "fn_option(" . fn_escape($forms->report['file']) . ");\n";
    print "</script>\n";
    break;
  default:
	print
    	"<p class=standard>Folder: " .
        $forms->select("folder",$_SESSION['site_folders'],$forms->report['folder'],FALSE,array("onchange"=>"fn_action(this,'list','');")) .
        "<br>Type: " . $forms->optgroup("type",$site->constant->type_list,$forms->report['type'],TRUE,array("onchange"=>"fn_action(this,'list','');")) .
        "<br>Rescan folders? " . $forms->checkbox("rescan",1,0,array("onclick"=>"fn_action(this,'scan','');")) .
		"<br>" . $forms->button("Go!",array("onclick"=>"fn_action(this,'list','');")) .
        "</p>\n";
	$pattern=$site->constant->type[$forms->report['type']]->pattern;
	if ($forms->report['action']) {
	    $results=array();
	    $contents=scandir($forms->report['folder']);
	    foreach ($contents as $file) {
	        $file=trim($file);
	        $url=$forms->report['folder'] . $file;
	        switch (TRUE) {
	          case (!is_file($url)):
	            break;
              case (!$pattern):
			  case (preg_match("/{$pattern}/i",$file)):
	            $results[]=$file;
	            break;
	        }
	    }
		print "<table class='small border tablesorter'>";
        print "<thead>";
        print "<tr>";
        print "<th>Action</th>";
        print "<th>File</th>";
        print "<th>Size</th>";
        print "<th>MD5</th>";
        print "<th>Revised</th>";
        print "</tr>";
        print "</thead>";
		print "<tr>";
        print "<td class=center>" . fn_href("Add new","javascript:fn_action(this,'maintain','');") . "</td>";
        print "<td colspan=4>&nbsp;</td>";
        print "</tr>";
        print "<tbody>";
        foreach ($results as $file) {
            $url=$forms->report['folder'] . $file;
            $fileinfo=stat($url);
            print "<tr>";
            print "<td class=center>" . $forms->select(base64_encode("file" . "\t" . $file),array("maintain"=>"Maintain","download"=>"Download","delete"=>"Delete"),"",TRUE,array("onchange"=>"fn_action(this,this.value," . fn_escape($file) . ");")) . "</td>";
            print "<td>" . $file . "</td>";
            print "<td class=right>" .  number_format($fileinfo['size']) . "</td>";
            print "<td>" . md5_file($url) . "</td>";
            print "<td class=center>" . date("M d, Y g:i A",$fileinfo['mtime']) . "</td>";
            print "</tr>";
        }
        print "</tbody>";
        print "</table>";
    }
}
print $forms->close();
$menu->copyright();
?>