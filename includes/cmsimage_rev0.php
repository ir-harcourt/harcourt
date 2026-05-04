<?php
require_once "classes/site.php";
$site=new site_class();
$thumbnail_list=array(0=>"Omit",100=>"100px",150=>"150px",200=>"200px",250=>"250px",9999=>"Full size");
$folder_list=array();
if ($database->registry->cms->image) $folder_list[$database->registry->cms->image]="Images";
if (($database->registry->cms->image_backup) && ($database->registry->cms->image_backup != $database->registry->cms->image)) $folder_list[$database->registry->cms->image_backup]="Backup images";

$forms->title("Content Management - Images");
$forms->message[]="Revised 01/25/2013";
$forms->report['action']=$_POST['action'];
$forms->report['folder']=$_POST['folder'];
$forms->report['thumbnail']=$_POST['thumbnail'];
	$forms->report['source']=$_POST['source'];
$forms->report['temp_url']=$forms->report['folder'] . "scs.tmp";
$forms->report['file']=$_POST['file'];
$forms->report['post_file']=trim($_POST['post_file']);
if ($forms->report['file']) {
	$forms->report['url']=$forms->report['folder'] . $forms->report['file'];
	if ($database->registry->cms->image) $forms->report['image_url']=$database->registry->cms->image . $forms->report['file'];
	if ($database->registry->cms->image_backup) $forms->report['backup_url']=$database->registry->cms->image_backup . $forms->report['file'];
}
switch (TRUE) {
  case ($forms->report['action']==""):
	break;
  case ($forms->report['action']=="cancel"):
    $forms->message[]="Request cancelled";
    break;
  case ($forms->report['action']=="delete"):
	switch (TRUE) {
	  case ($forms->report['url'] == $forms->report['backup_url']):
      case (!$forms->report['backup_url']):
		unlink($forms->report['url']);
		$forms->message[]="Deleted: <b>" . $forms->report['url'] . "</b>";
        break;
      default:
		if (file_exists($forms->report['backup_url'])) unlink($forms->report['backup_url']);
        rename($forms->report['url'],$forms->report['backup_url']);
		$forms->message[]="Deleted: <b>" . $forms->report['url'] . "</b> backup created";
	}
    break;
  case ($forms->report['action']=="maintain"):
	if ($forms->report['file']) {
  		$forms->report['source']="existing";
		$forms->report['post_file']=$forms->report['file'];
    }
  case ($forms->report['action']=="update"):
	switch ($forms->report['source']) {
	  case "existing":
		break;
	  case "move_image":
	    if (file_exists($forms->report['image_url'])) unlink($forms->report['image_url']);
	    rename($forms->report['backup_url'],$forms->report['image_url']);
	    $forms->message[]=$forms->report['backup_url'] . " moved to " . $forms->report['image_url'];
	    break;
	  case "move_backup":
	    if (file_exists($forms->report['backup_url'])) unlink($forms->report['backup_url']);
	    rename($forms->report['image_url'],$forms->report['backup_url']);
	    $forms->message[]=$forms->report['image_url'] . " moved to " . $forms->report['backup_url'];
	    break;
	  case "rename":
	  case "copy":
        $forms->report['post_url']=$forms->report['folder'] . $forms->report['post_file'];
		switch (TRUE) {
	      case (!$forms->report['post_file']):
	        $forms->error('post_file','Cannot be blank');
	        break;
	      case ($forms->report['post_file']==$forms->report['file']):
	        $forms->error('post_file','Identical Name');
	        break;
		  case (!preg_match("/" . end(explode('.', $forms->report['post_file'])) . "$/i",$forms->report['file'])):
	        $forms->error('post_file','File extension cannot be changed');
	        break;
	      case (file_exists($forms->report['post_url'])):
	        $forms->error('post_file','File exists: ' . $forms->report['post_url']);
	        break;
	      case (preg_match("/[,\"\'\/\\\]/",$forms->report['post_file'])):
	        $forms->error('post_file','Special characters not allowed');
	        break;
		  case ($forms->report['source']=="rename"):
          	rename($forms->report['url'],$forms->report['post_url']);
			$forms->message[]="Renamed <b>" . $forms->report['url'] . "</b> to <b>" . $forms->report['post_url'] . "</b>";
			break;
          default:
          	copy($forms->report['url'],$forms->report['post_url']);
			$forms->message[]="Copied <b>" . $forms->report['url'] . "</b> to <b>" . $forms->report['post_url'] . "</b>";
        }
      	break;
	  case "upload":
	    switch (TRUE) {
	      case ($_FILES['post_file']['size']=="0"):
	        $forms->error('post_file','File is empty');
	        break;
	      case ($_FILES['post_file']['error'] != "0"):
	        $forms->error('post_file','Upload error #' . $_FILES['post_file']['error']);
	        break;
	      case (preg_match("/[,\"\'\/\\\]/",$_FILES['post_file']['name'])):
	        $forms->error('post_file','Special characters not allowed');
	        break;
	    }
	    if (sizeof($forms->error)) break;
	    if (file_exists($forms->report['temp_url'])) unlink($forms->report['temp_url']);
	    move_uploaded_file($_FILES['post_file']['tmp_name'],$forms->report['temp_url']);
	    $imageinfo=getimagesize($forms->report['temp_url']);
		$forms->report['url']=$forms->report['folder'] . $_FILES['post_file']['name'];
		$forms->report['backup_url']=$database->registry->cms->image_backup . $_FILES['post_file']['name'];
        switch (TRUE) {
          case (!$imageinfo['mime']):
	        $forms->error('post_file','Not an image file');
          	break;
          case ((file_exists($forms->report['url'])) && ($forms->report['url'] != $forms->report['backup_url'])):
			if (file_exists($forms->report['backup_url'])) unlink($forms->report['backup_url']);
            rename($forms->report['url'],$forms->report['backup_url']);
	        rename($forms->report['temp_url'],$forms->report['url']);
	        $forms->message[]="Uploaded: <b>" . $forms->report['url'] . "</b> back up created";
          	break;
          default:
			if (file_exists($forms->report['url'])) unlink($forms->report['url']);
	        rename($forms->report['temp_url'],$forms->report['url']);
	        $forms->message[]="Uploaded: <b>" . $forms->report['url'] . "</b>";
			break;
	    }
    }
	break;
}

$menu->head();
$forms->message();
?>
<script language=javascript>
function fn_action(obj,action,file) {
	if (action=='delete') {
	    if (!confirm("Delete " + file + "?")) {
        	obj.checked=false;
            return;
	    }
    }
    document.scs_form.action.value=action;
    document.scs_form.file.value=file;
    document.scs_form.submit();
}
</script>
<?php
print $forms->open(array("data"=>TRUE));
print $forms->hidden("action");
print $forms->hidden("file");
switch (TRUE) {
  case (!$database->registry->cms->frame):
  case ((!$database->registry->cms->image) && (!$database->registry->cms->image_backup)):
	print "<p class='large'>Content Management or Image Management not supported.</p>\n";
	break;
  case ($forms->report['action']=="maintain"):
  case (($forms->report['action']=="update") && (sizeof($forms->error))):
	print $forms->hidden("folder",$forms->report['folder']);
	print $forms->hidden("thumbnail",$forms->report['thumbnail']);
    if ($forms->report['file']) {
	    $fileinfo=stat($forms->report['url']);
	    $imageinfo=getimagesize($forms->report['url']);
	    print "<table class='standard noborder'>\n";
	    print "<tr>\n";
	    print "<td width=50%>";
	    print "Folder: " . $forms->report['folder'];
	    print "<br>File name: " . $forms->report['file'];
	    print "<br>Create date: " . date("m/d/Y g:i A",$fileinfo['mtime']);
	    print "<br>Bytes: " . number_format($fileinfo['size']);
	    print "<br>" . $imageinfo[3];
	    print "<br>Mime: " . $imageinfo['mime'];
	    print "<br>Bits: " . $imageinfo['bits'];
		print "<br>Channels: " . $imageinfo['channels'];
	    print "</td>\n";
	    print "<td width=50%>" . $forms->img($forms->report['url'],array("width=500"),array("resize"=>TRUE)) . "</td>\n";
	    print "</tr>\n";
	    print "</table>\n";
       	print "<p class=standard>" . $forms->radio("source","existing",$forms->report['source']) . " Use existing file?";
        print "<br>" . $forms->radio("source","rename",$forms->report['source'],array("onclick"=>"document.getElementById('post_file').focus();")) . " Rename file? ";
        print "<br>" . $forms->radio("source","copy",$forms->report['source'],array("onclick"=>"document.getElementById('post_file').focus();")) . " Copy file? ";
		switch (TRUE) {
		  case (!$database->registry->cms->image):
		  case (!$database->registry->cms->image_backup):
		  case ($database->registry->cms->image == $database->registry->cms->image_backup):
			break;
		  case ($forms->report['folder']==$database->registry->cms->image):
			print "<br>" . $forms->radio("source","move_backup",$forms->report['source']) . "Move to backup folder?";
			if (file_exists($forms->report['backup_url'])) print " (Overwrite existing file)";
			break;
          default:
			print "<br>" . $forms->radio("source","move_image",$forms->report['source']) . "Move to images folder?";
			if (file_exists($forms->report['image_url'])) print " (Overwrite existing file)";
		}
		print "<br>File name: " . $forms->text("post_file",$forms->report['post_file'],30);
		if ($forms->error_text["post_file"]) print " <b>" . $forms->error_text["post_file"] . "</b>";
        print "</p>\n";
	  } else {
		print $forms->hidden("source","upload");
		print "<p>Upload image " . $forms->file("post_file",40);
        if ($forms->error_text['post_file']) print "<br>" . $forms->error_text['post_file'];
        print "</p>\n";
	}
    print
    	"<p>" . $forms->button("Update",array("onclick"=>"fn_action(this,'update'," . fn_escape($forms->report['file']) . ");")) .
        " " . $forms->button("Cancel",array("onclick"=>"fn_action(this,'cancel','');")) . "</p>\n";
    break;
  default:
	print
	    "<p class=standard>Folder: " . $forms->select("folder",$folder_list,$forms->report['folder'],FALSE) .
        "<br>Thumbnail size " . $forms->select("thumbnail",$thumbnail_list,$forms->report['thumbnail'],FALSE) .
	    "<br>" . $forms->button("List",array("onclick"=>"fn_action(this,'list','','');")) . "</p>\n";
	if (!$forms->report['action']) break;
	$results=array();
    $contents=scandir($forms->report['folder']);
    foreach ($contents as $file) {
        $file=trim($file);
        $url=$forms->report['folder'] . $file;
        switch (TRUE) {
		  case (!is_file($url)):
          	break;
		  case (preg_match("/{$site->constant->type['image']->pattern}/i",$file)):
			$results[]=$file;
            break;
		}
	}
	print "<table class='small border'>\n";
    print "<caption class=left>Folder: <b>" . $forms->report['folder'] . "</b></caption>\n";
    print "<tr>\n";
    print "<th>Name</th>\n";
    print "<th>Date</th>\n";
    print "<th>Wdith x Height</th>\n";
    print "<th>Bytes</th>\n";
	print "<th>Image</th>\n";
    print "<th>Del?</th>\n";
    print "</tr>\n";
    print "<tr><td colspan=6>" . fn_href("Upload image","javascript:fn_action(this,'maintain','');") . "</td></tr>\n";
    if (sizeof($results)) {
    	foreach ($results as $file) {
			$url=$forms->report['folder'] . $file;
	        $fileinfo=stat($url);
            $imageinfo=getimagesize($url);
        	print "<tr>\n";
            print "<td>" . fn_href($file,"javascript:fn_action(this,'maintain'," . fn_escape($file) . ");") . "</td>\n";
            print "<td class=center>" . date("m/d/Y g:iA",$fileinfo['mtime']) . "</td>\n";
            print "<td class=center>" . $imageinfo[0] . "x" . $imageinfo[1] . "</td>\n";
            print "<td class=right>" . number_format($fileinfo['size']) . "</td>\n";
            if ($forms->report['thumbnail']) {
            	$text=$forms->img($url,array("width"=>$forms->report['thumbnail']),array("resize"=>TRUE));
			  } else {
				$text="View";
            }
			print "<td class=center>" . fn_href($text,$url,array(),array("target"=>"images","title"=>"Upload $file"))  . "</td>\n";
            print "<td class=center>" . $forms->radio("delete",$file,"",array("onclick"=>"fn_action(this,'delete'," . fn_escape($file) . ");")) . "</td>\n";
            print "</tr>\n";
        }
	}
    print "</table>\n";
}
print $forms->close();
$menu->copyright();
?>