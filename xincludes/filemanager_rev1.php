<?php
$tree=array();
fn_pre($_SESSION['filemanager_rev1']);
foreach ($_SESSION['filemanager_rev1'] as $folder) {
	$items=preg_split("/\//",substr($folder,1,-1));
	$tree=tree_branch("",$items,$tree);
}
function tree_branch($folder,$items,$tree=array()) {
 print "<br>folder: $folder (" . implode("/",$items) . ")";
	if (!array_key_exists($folder,$tree)) $tree[$folder]=array();
	if (sizeof($items) == 1) {
		$tree[$folder][]=$items[0];
	  } else {
		$item_folder=$items[0];
		array_shift($items);
        if (
tree_branch($item_folder,$items,$tree[$folder]);
//		$tree[ $folder][$items[0] ]=tree_branch($items[0],array_shift($items),$tree[$folder]);
	}
	return $tree;
}
/*
function tree_branch($pathname,$folders,$contents=array()) {
	foreach ($folders as $folder) {
		$items=preg_split("/\//",substr($folder,1,-1));
        switch (TRUE) {
          case ($items[0]==""):
          	break;
          case (sizeof($items) == 1):
			$contents[$pathname][]=$items[0];
            break;
          default:
			$contents[$items[0]]=array();
			$contents=tree_branch($items[0],$folders[$folder],$contents);
    		break;
		}
	}
    return $contents;
}
*/
fn_pre($tree,1);



$filemanager=new filemanager_class();
class filemanager_class {
	var $folders=array();
	var $contents=array();
    var $tree=array();
	function scs_table_version() {
		$results=array();
        $results['03/08/2016']="Initial release";
		$results['file']= __FILE__;
        return $results;
    }
    function __construct() {
    global $database, $forms, $menu;
 	    $forms->report['action']=$_POST['action'];
	    $forms->report['file']=$_POST['file'];
	    $forms->report['option']=$_POST['option'];
	    $forms->report['backup']=$_POST['backup'];
	    $forms->report['filename']=$_POST['filename'];
	    $forms->report['folder']=$_POST['folder'];
	    $forms->report['overwrite']=$_POST['overwrite'];
	    $forms->report['filetype']=$_POST['filetype'];
        if (strlen($forms->report['file'])) $forms->report['url']=$_SERVER['DOCUMENT_ROOT'] . "/" . $forms->report['file'];
		if (isset($_SESSION['filemanager_rev1'])) $this->folders=$_SESSION['filemanager_rev1'];
		switch ($forms->report['action']) {
	      case "":
	        $this->scan();
            break;
	      case "load":
			$this->load();
            break;
          default:
fn_pre($_POST,1);
          	print "Error: " . $forms->report['action'];
		}
    }
    function folders() {
		$this->folders=array();
		$items=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($_SERVER['DOCUMENT_ROOT'] . "/" . $folder,FilesystemIterator::UNIX_PATHS));
		foreach ($items as $item) {
			if ( (!$item->isDir()) || ($item->getFilename()=="..") ) continue;
            $this->folders[]=preg_replace("/^" . preg_quote($_SERVER['DOCUMENT_ROOT'],"/") . "\//","",$item->getPathname());
		}
		$_SESSION['filemanager_rev1']=$this->folders;
    }
    function load() {
    global $database, $forms, $menu;
		$results=array();
        if (!$forms->report['file']) {
			$results[]="<p>Add file</p>";
	        $results[]=$forms->hidden("option","upload");
	      } else {
	        $text=array();
	        $fileinfo=stat($forms->report['url']);
	        $imageinfo=getimagesize($forms->report['url']);
	        if ($imageinfo['mime']) $text[]=$forms->img($forms->report['file'],array(),array("resize"=>TRUE,"width"=>100));
	        $text[]="URL: " . $forms->report['file'];
	        $text[]="Size: " . number_format($fileinfo['size']);
	        $text[]="Change date: " . date("F j, Y g:i A",$fileinfo['mtime']);
	        if ($imageinfo['mime']) {
	            $text[]="Mime: " . $imageinfo['mime'];
	            $text[]="Image size: " . $imageinfo[3];
	            $text[]="Bits: " . $imageinfo['bits'];
	            if ($imageinfo['channels']) $text[]="Channels: " . $imageinfo['channels'];
	        }
	        $results[]="<p>" . implode("<br>\n",$text) . "</p>";
	        $results[]="<p>Action: " . $forms->select("option",array(""=>"Keep existing","upload"=>"Upload new file","rename"=>"Rename file","copy"=>"Copy file","move"=>"Move to another folder"),$forms->report['option'],FALSE,array("onchange"=>"fn_option(" . fn_escape($forms->report['file']) . ");")) . "</p>";
	    }
        $text=array();
	    $text[]="Upload file: " . $forms->file("upload",60);
	    if ($forms->error_text['upload']) $text[]=" <b>" . $forms->error_text['upload'] . "</b>";
        $results[]="<div id=upload_id>" . implode("<br>",$text) . "</div>";
        $results[]="<div id=backup_id>Backup format: " . $forms->select("backup",array("date"=>"_YYYYMMDD","bak"=>"_bak","none"=>"No backup"),$forms->report['backup'],FALSE) . "</div>";
        $text=array();
	    $text[]="File name: " . $forms->text("filename",$forms->report['filename'],60);
	    if ($forms->error_text['filename']) $text[]=" <b>" . $forms->error_text['filename'] . "</b>";
        $results[]="<div id=filename_id>" . implode("<br>",$text) . "</div>";
        $results[]="<div id=folder_id>Folder name: " . $forms->select("folder",$this->folders,$forms->report['folder']) . "</div>";
        $results[]="<div id=overwrite_id>Overwrite existing file? " . $forms->checkbox("overwrite",1,$forms->report['overwrite']) . "</div>";
        $results[]="<div id=filetype_id>Change file type? " . $forms->checkbox("filetype",1,$forms->report['filetype']) . "</div>";
        $buttons=array();
        $buttons[]=$forms->button("Update",array("onclick"=>"fn_update(" . fn_escape($forms->report['file']) . ");"));
//        $buttons[]=$forms->button("Update",array("onclick"=>"fn_action('update'," . fn_escape($forms->report['file']) . ");"));
        if (strlen($forms->report['file'])) {
			$buttons[]=$forms->button("Download File",array("onclick"=>"fn_action('download'," . fn_escape($forms->report['file']) . ");"));
			$buttons[]=$forms->button("Delete File",array("onclick"=>"fn_action('delete'," . fn_escape($forms->report['file']) . ");"));
        }
        $results[]="<p>" . implode(" ",$buttons) . "</p>";
        print implode("\n",$results);
    }
    function scan($folder="") {
		if (strlen($folder)) {
        	unset ($contents[$folder]);
		  } else {
          	$contents=array();
		}
		$items=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($_SERVER['DOCUMENT_ROOT'] . "/" . $folder,FilesystemIterator::UNIX_PATHS));
		foreach ($items as $item) {
			if ($item->getFilename()=="..") continue;
	        $file=new stdClass();
	        $file->name=$item->getFilename();
	        $file->type=$item->getType();
	        $file->size=$item->getSize();
	        $file->permissions=($item->isReadable() ? "r" : "-") . ($item->isWritable() ? "w" : "-") . ($item->isExecutable() ? "x" : "-");
	        $file->owner=$item->getOwner();
	        $file->group=$item->getGroup();
	        $file->revised_timestamp=$item->getMTime();
	        $file->pathname=preg_replace("/^" . preg_quote($_SERVER['DOCUMENT_ROOT'],"/") . "\//","",$item->getPathname());
            if ( ($file->type=="dir") && (!isset($_SESSION['filemanager_rev1'])) ) $this->folders[]="/" . substr($file->pathname,0,-1);
	        if ($file->pathname==".") continue;
	        $suffix=preg_split("/\./",strtolower($file->pathname));
	        switch (TRUE) {
	          case ($file->type=="dir"):
	            $file->class="icon_folder";
	            break;
	          case (in_array(end($suffix),array("php","php3","php5","phtml"))):
	            $file->class="icon_php";
	            break;
	          case (in_array(end($suffix),array("htm","html","shtm","shtml"))):
	            $file->class="icon_html";
	            break;
	          case (in_array(end($suffix),array("css"))):
	            $file->class="icon_css";
	            break;
	          case (in_array(end($suffix),array("js","jar"))):
	            $file->class="icon_java";
	            break;
	          case (in_array(end($suffix),array("vbs"))):
	            $file->class="icon_script";
	            break;
	          case (in_array(end($suffix),array("gif","jpg","png"))):
	            $file->class="icon_image";
	            break;
	          case (in_array(end($suffix),array("pdf"))):
	            $file->class="icon_pdf";
	            break;
	          case (in_array(end($suffix),array("doc","docx"))):
	            $file->class="icon_doc";
	            break;
	          case (in_array(end($suffix),array("ppt","pptx"))):
	            $file->class="icon_ppt";
	            break;
	          case (in_array(end($suffix),array("xls","xlt","xlm","xlsx","xlsm","csv"))):
	            $file->class="icon_xls";
	            break;
	          case (in_array(end($suffix),array("txt"))):
	            $file->class="icon_txt";
	            break;
	          case (in_array(end($suffix),array("sql","mdb"))):
	            $file->class="icon_db";
	            break;
	          case (in_array(end($suffix),array("zip","gz"))):
	            $file->class="icon_zip";
	            break;
	          default:
	            $file->class="icon_file";
	        }
	        if ($file->type=="dir") {
	            $data=preg_split("/\//",$file->pathname);
	            array_pop($data);
	            $file->name=end($data);
	            $file->pathname=implode("/",$data);
	        }
	        $this->contents=$this->contents($file,$file->pathname,$this->contents);
		}
		if (!isset($_SESSION['filemanager_rev1'])) $_SESSION['filemanager_rev1']=$this->folders;
		$this->tree();
        $this->html();
    }
    function contents($file,$pathname,$contents,$debug=FALSE) {
	    $data=preg_split("/\//",$pathname);
	    switch (TRUE) {
	      case (sizeof($data) > 1):
	        $folder=array_shift($data);
	        $contents[$folder]=$this->contents($file,implode("/",$data),$contents[$folder],TRUE);
	        break;
	      case ($file->type=="dir"):
	        $contents[$pathname]=array();
	        $contents[$pathname]["."]=$file;
	        break;
	      default:
	        $contents[$pathname]=$file;
	    }
	    return $contents;
    }
    function tree() {
		$this->tree=array();
        $this->tree_build($this->contents);
    }
	function tree_build($contents,$hidden=FALSE) {
	    $text=array();
	    $text[]="class='scs_filetree'";
	    $child_id=$this->id("child",$contents);
	    if (strlen($child_id)) {
	        $text[]="id={$child_id}";
	        $text[]="style='display: none;'";
	    }
	    $this->tree[]="<ul " . implode(" ",$text) . ">";
	    foreach ($contents as $filename => $file) {
	        if (is_array($file)) {
	            $parent_id=$this->id("parent",$contents[$filename]);
	            $child_id=$this->id("child",$contents[$filename]);
	            $text=array();
	            $text[]="<li class=icon_folder";
	            if (strlen($parent_id)) $text[]="id={$parent_id}";
	            $text[]=">";
	            $this->tree[]=implode(" ",$text) . fn_href($filename,"javascript:fn_folder(" . fn_escape($parent_id) . "," . fn_escape($child_id) . ");") . "</li>";
	            $this->tree_build($file,TRUE);
	        }
	    }
	    foreach ($contents as $filename => $file) {
	        if ( ($filename != ".") && (!is_array($file)) ) {
	            $this->tree[]="<li class=" . $file->class . ">" . fn_href($filename,"javascript:fn_load(" . fn_escape($file->pathname) . ");") . "</li>";
	        }
	    }
	    $this->tree[]="</ul>";
	}
	function id($parent_child,$contents) {
	    if (array_key_exists(".",$contents)) return fn_base64(array_merge( array($parent_child),preg_split("/\//",$contents['.']->pathname)));
	}
    function html() {
    global $database, $forms, $menu;
		$forms->title("File Manager (Revised " . key($this->scs_table_version()) . ")");
	    $menu->head();
	    $forms->message();
?>
<script language=javascript>
function fn_load(file) {
	$.ajax({
	    url: window.location.pathname,
	    data: {action: 'load', file: file },
	    method: 'POST',
	    dataType: 'html',
	    success:function(data){
			$('#filemanager_right').html(data);
            fn_option(file);
	   }
	});
}
function fn_folder(parent,child) {
	if ($('#' + child).css('display') == 'none') {
		$('#' + parent).removeClass('icon_folder');
		$('#' + parent).addClass('icon_folder_open');
		$('#' + child).show();
	  } else {
		$('#' + parent).removeClass('icon_folder_open');
		$('#' + parent).addClass('icon_folder');
		$('#' + child).hide();
	}
}
function fn_action(action,file) {
	if ( (action=='delete') && (!confirm("Delete " + file + "?")) ) return;
    document.scs_form.action.value=action;
    document.scs_form.file.value=file;
    document.scs_form.submit();
}
function fn_update(file) {
	var data = {
	    'action' : 'update',
	    'file' : file,
	    'option' : $('#option').val(),
	    'backup' : $('#backup').val(),
	    'filename' : $('#filename').val(),
	    'folder' : $('#foler').val(),
	    'overwrite' : $('#overwrite').val(),
	    'filetype' : $('#filetype').val()
    }
	$.ajax({
	    url: window.location.pathname,
	    data: data,
	    method: 'POST',
	    dataType: 'html',
	    success:function(data){
			$('#filemanager_right').html(data);
//			fn_option(file);
	   }
	});
}
function fn_option(file) {
	upload_show=0;
    backup_show=0;
    filename_show=0;
    folder_show=0;
    overwrite_show=0;
    filetype_show=0;
    switch ( $('#option').val() ) {
      case 'add':
        upload_show=1;
        folder_show=1;
		break;
      case 'upload':
        upload_show=1;
        backup_show=1;
        break;
      case 'rename':
        filename_show=1;
        overwrite_show=1;
        filetype_show=1;
        break;
      case 'copy':
        filename_show=1;
        folder_show=1;
        overwrite_show=1;
        filetype_show=1;
        break;
      case 'move':
        folder_show=1;
        overwrite_show=1;
        break;
    }
    div_show('upload_id',upload_show);
    div_show('backup_id',backup_show);
    div_show('filename_id',filename_show);
    div_show('folder_id',folder_show);
    div_show('overwrite_id',overwrite_show);
    div_show('filetype_id',filetype_show);
}
</script>
<style>
ul.scs_filetree a {color: #333; display: block; padding: 0 2px; text-decoration: none;}
ul.scs_filetree li {list-style: outside none none; white-space: nowrap;}
ul.scs_filetree {font-family: Verdana,sans-serif; font-size: 11px; line-height: 18px;}

.icon_application { background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAFiSURBVBgZpcEhbpRRGIXh99x7IU0asGBJWEIdCLaAqcFiCArFCkjA0KRJF0EF26kkFbVVdEj6/985zJ0wBjfp8ygJD6G3n358fP3m5NvtJscJYBObchEHx6QKJ6SKsnn6eLm7urr5/PP76cU4eXVy/ujouD074hDHd5s6By7GZknb3P7mUH+WNLZGKnx595JDvf96zTQSM92vRYA4lMEEO5RNraHWUDH3FV48f0K5mAYJk5pQQpqIgixaE1JDKtRDd2OsYfJaTKNcTA2IBIIesMAOPdDUGYJSqGYml5lGHHYkSGhAJBBIkAoWREAT3Z3JLqZhF3uS2EloQCQ8xLBxoAEWO7aZxros7EgISIIkwlZCY6s1OlAJTWFal5VppMzUgbAlQcIkiT0DXSI2U2ymYZs9AWJL4n+df3pncsI0bn5dX344W05dhctUFbapZcE2ToiLVHBMbGymS7aUhIdoPNBf7Jjw/gQ77u4AAAAASUVORK5CYII) no-repeat left center; padding: 2px 0 2px 18px; }
.icon_code { background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAHtSURBVDjLjZM9T9tQFIYpQ5eOMBKlW6eWIQipa8RfQKQghEAKqZgKFQgmFn5AWyVDCipVQZC2EqBWlEqdO2RCpAssQBRsx1+1ndix8wFvfW6wcUhQsfTI0j33PD7n+N4uAF2E+/S5RFwG/8Njl24/LyCIOI6j1+v1y0ajgU64cSSTybdBSVAwSMmmacKyLB/DMKBpGkRRZBJBEJBKpXyJl/yABLTBtm1Uq1X2JsrlMnRdhyRJTFCpVEAfSafTTUlQoFs1luxBAkoolUqQZbmtJTYTT/AoHInOfpcwtVtkwcSBgrkDGYph+60oisIq4Xm+VfB0+U/P0Lvj3NwPGfHPTcHMvoyFXwpe7UmQtAqTUCU0D1VVbwTPVk5jY19Fe3ZfQny7CE51WJDXqpjeEUHr45ki9rIqa4dmQiJfMLItGEs/FcQ2ucbRmdnSYy5vYWyLx/w3EaMfLmBaDpMQvuDJ65PY8Dpnz3wpYmLtApzcrIAqmfrEgdZH1grY/a36w6Xz0DKD8ES25/niYS6+wWE8mWfByY8cXmYEJFYLkHUHtVqNQcltAvoLD3v7o/FUHsNvzlnwxfsCEukC/ho3yUHaBN5Buo17Ojtyl+DqrnvQgUtfcC0ZcAdkUeA+ye7eMru9AUGIJPe4zh509UP/AAfNypi8oj/mAAAAAElFTkSuQmCC) no-repeat left center; padding: 2px 0 2px 18px; }
.icon_css { background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAH8SURBVDjLjZPLaxNRFIfHLrpx10WbghXxH7DQx6p14cadiCs31Y2LLizYhdBFWyhYaFUaUxLUQFCxL61E+0gofWGLRUqGqoWp2JpGG8g4ybTJJJm86897Ls4QJIm98DED9/6+mXNmjiAIwhlGE6P1P5xjVAEQiqHVlMlkYvl8/rhQKKAUbB92u91WSkKrlcLJZBK6rptomoZoNApFUbhElmU4HA4u8YzU1PsmWryroxYrF9CBdDqNbDbLr0QikUAsFkM4HOaCVCoFesjzpwMuaeXuthYcw4rtvG4KKGxAAgrE43FEIhGzlJQWxE/RirQ6i8/T7XjXV2szBawM8yDdU91GKaqqInQgwf9xCNmoB7LYgZn+Oud0T121KfiXYokqf8X+5jAyR3NQvtzEq96z4os7lhqzieW6TxJN3UVg8yEPqzu38P7xRVy+cPoay52qKDhUf0HaWsC3xRvstd3Qvt9mTWtEOPAJf/+L8oKAfwfLnil43z7Bkusqdr2X4Btvg1+c5fsVBZJ/H9aXbix/2EAouAVx4zVmHl2BtOrkPako2DsIwulexKhnG/cmfbg+uIbukXkooR/I5XKcioLu+8/QNTyGzqE36OidQNeDJayLe7yZBuUEv8t9iRIcU6Z4FprZ36fTxknC7GyCBrBY0ECSE4yzAY1+gyH4Ay9cw2Ifwv9mAAAAAElFTkSuQmCC) no-repeat left center; padding: 2px 0 2px 18px; }
.icon_db { background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAHVSURBVDjLjZPLaiJBFIZNHmJWCeQdMuT1Mi/gYlARBRUkao+abHUhmhgU0QHtARVxJ0bxhvfGa07Of5Iu21yYFPyLrqrz1f+f6rIRkQ3icca6ZF39RxesU1VnAVyuVqvJdrvd73Y7+ky8Tk6n87cVYgVcoXixWNByuVSaTqc0Ho+p1+sJpNvtksvlUhCb3W7/cf/w+BSLxfapVIqSySRlMhnSdZ2GwyHN53OaTCbU7/cFYBgG4RCPx/MKub27+1ur1Xqj0YjW6zWxCyloNBqUSCSkYDab0WAw+BBJeqLFtQpvGoFqAlAEaZomuc0ocAQnnU7nALiJ3uh8whgnttttarVaVCgUpCAUCgnQhMAJ+gG3CsDZa7xh1mw2ZbFSqYgwgsGgbDQhcIWeAHSIoP1pcGeNarUqgFKpJMLw+/0q72azkYhmPAWIRmM6AGbXc7kc5fN5AXi9XgWACwAguLEAojrfsVGv1yV/sVikcrksAIfDIYUQHEAoPgLwT3GdzWYNdBfXh3xwApDP5zsqtkoBwuHwaSAQ+OV2u//F43GKRCLEc5ROpwVoOngvBXj7jU/wwZPPX72DT7RXgDfIT27QEgvfKea9c3m9FsA5IN94zqbw9M9fAEuW+zzj8uLvAAAAAElFTkSuQmCC) no-repeat left center; padding: 2px 0 2px 18px; }
.icon_doc { background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAIdSURBVDjLjZO7a5RREMV/9/F9yaLBzQY3CC7EpBGxU2O0EBG0sxHBUitTWYitYCsiiJL0NvlfgoWSRpGA4IMsm43ZXchmv8e9MxZZN1GD5MCBW8yce4aZY1QVAGPMaWAacPwfm8A3VRUAVJWhyIUsy7plWcYQgh7GLMt0aWnpNTADWFX9Q2C+LMu4s7Oj/X5/xF6vp51OR1utloYQtNls6vLy8kjE3Huz9qPIQjcUg/GZenVOokIEiSBBCKUSQ+TFwwa1Wo2iKBARVlZW3iwuLr7izssPnwZ50DLIoWz9zPT+s/fabrf/GQmY97GIIXGWp28/08si5+oV1jcGTCSO6nHH2pddYqmkaUq320VECCFQr9cBsBIVBbJcSdXQmK7Q6Qsnq54sj2gBplS896RpSpIkjI2NjVZitdh7jAOSK6trXcpC2GjlfP1esHD+GDYozjm893jvSZJkXyAWe+ssc6W5G9naLqkaw/pGxBrl1tVpJCrWWpxzI6GRgOQKCv2BYHPl5uUatROeSsVy7eIkU9UUiYoxBgDnHNbagw4U6yAWwpmphNvXT6HAhAZuLNRx1iDDWzHG/L6ZEbyJVLa2c54/PgsKgyzw5MHcqKC9nROK/aaDvwN4KYS7j959DHk2PtuYnBUBFUEVVBQRgzX7I/wNM7RmgEshhFXAcDSI9/6KHQZKAYkxDgA5SnOMcReI5kCcG8M42yM6iMDmL261eaOOnqrOAAAAAElFTkSuQmCC) no-repeat left center; padding: 2px 0 2px 18px; }
.icon_file { background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAQAAAC1+jfqAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAC4SURBVCjPdZFbDsIgEEWnrsMm7oGGfZrohxvU+Iq1TyjU60Bf1pac4Yc5YS4ZAtGWBMk/drQBOVwJlZrWYkLhsB8UV9K0BUrPGy9cWbng2CtEEUmLGppPjRwpbixUKHBiZRS0p+ZGhvs4irNEvWD8heHpbsyDXznPhYFOyTjJc13olIqzZCHBouE0FRMUjA+s1gTjaRgVFpqRwC8mfoXPPEVPS7LbRaJL2y7bOifRCTEli3U7BMWgLzKlW/CuebZPAAAAAElFTkSuQmCC) no-repeat left center; padding: 2px 0 2px 18px; }
.icon_film { background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAIfSURBVDjLpZNPaBNBGMXfbrubzBqbg4kL0lJLgiVKE/AP6Kl6UUFQNAeDIAjVS08aELx59GQPAREV/4BeiqcqROpRD4pUNCJSS21OgloISWMEZ/aPb6ARdNeTCz92mO+9N9/w7RphGOJ/nsH+olqtvg+CYJR8q9VquThxuVz+oJTKeZ63Uq/XC38E0Jj3ff8+OVupVGLbolkzQw5HOqAxQU4wXWWnZrykmYD0QsgAOJe9hpEUcPr8i0GaJ8n2vs/sL2h8R66TpVfWTdETHWE6GRGKjGiiKNLii5BSLpN7pBHpgMYhMkm8tPUWz3sL2D1wFaY/jvnWcTTaE5DyjMfTT5J0XIAiTRYn3ASwZ1MKbTmN7z+KaHUOYqmb1fcPiNa4kQBuyvWAHYfcHGzDgYcx9NKrwJYHCAyF21JiPWBnXMAQOea6bmn+4ueYGZi8gtymNVobF7BG5prNpjd+eW6X4BSUD0gOdCpzA8MpA/v2v15kl4+pK0emwHSbjJGBlz+vYM1fQeDrYOBTdzOGvDf6EFNr+LYjHbBgsaCLxr+moNQjU2vYhRXpgIUOmSWWnsJRfjlOZhrexgtYDZ/gWbetNRbNs6QT10GJglNk64HMaGgbAkoMo5fiFNy7CKDQUGqE5r38YktxAfSqW7Zt33l66WtkAkACjuNsaLVaDxlw5HdJ/86aYrG4WCgUZD6fX+jv/U0ymfxoWVZomuZyf+8XqfGP49CCrBUAAAAASUVORK5CYII) no-repeat left center; padding: 2px 0 2px 18px; }
.icon_flash { background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAHYSURBVDjLjZPLSxtRFMa1f0UXCl0VN66igg80kQZtsLiUWhe14MKFIFHbIEF8BNFFKYVkkT9GKFJooXTToq2gLkQT82oyjzuvO8nXe65mmIkRHfg2c+/3O+d8l9MBoIMkvi6hkNDAA3om9MTz+QAhy7JqnPO667poJ3GOdDr92Q/xAwbIrOs6GGOeFEVBtVpFoVCQkHw+j0wm40Ga5k4C0AXTNGHbNsxv32Hu7YNtp1Cr1VAsFiXAMAxQkWw2ewNpBZDZPjiA+XYebioJ9nIKqqqiVCrdGUlm0gpwzs5hzrwGX1uGMTMLtvrBG6VcLstOcrncPQDOYW3tgCffw0isg4uqnP6J8AhCnVAelUqlPYD/PYE59wZ67BXsL4fg/6ryYhNC82uaJkFtAdbHT+CJFbgbCagjYbDNlDev4zgyH4KQ7gA2n/fMUWWeiAtzBMrgWABAXciAhaibAKAYnXyaGx3/5cSXoIajsH/8hHP8B87llTSSqAMSmQMAfSL2VYtET5WRCLcW3oHt7Aaq+s1+eQAt/EJXh8MNe2kRSmwa/LoQeOsmpFUeQB0ag9I/jIve0G/n6Lhx3x60Ud3L4DbIPhEQo4PHmMVdTW6vD9BNkEesc1O0+t3/AXamvvzW7S+UAAAAAElFTkSuQmCC) no-repeat left center; padding: 2px 0 2px 18px; }
.icon_folder { background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAGrSURBVDjLxZO7ihRBFIa/6u0ZW7GHBUV0UQQTZzd3QdhMQxOfwMRXEANBMNQX0MzAzFAwEzHwARbNFDdwEd31Mj3X7a6uOr9BtzNjYjKBJ6nicP7v3KqcJFaxhBVtZUAK8OHlld2st7Xl3DJPVONP+zEUV4HqL5UDYHr5xvuQAjgl/Qs7TzvOOVAjxjlC+ePSwe6DfbVegLVuT4r14eTr6zvA8xSAoBLzx6pvj4l+DZIezuVkG9fY2H7YRQIMZIBwycmzH1/s3F8AapfIPNF3kQk7+kw9PWBy+IZOdg5Ug3mkAATy/t0usovzGeCUWTjCz0B+Sj0ekfdvkZ3abBv+U4GaCtJ1iEm6ANQJ6fEzrG/engcKw/wXQvEKxSEKQxRGKE7Izt+DSiwBJMUSm71rguMYhQKrBygOIRStf4TiFFRBvbRGKiQLWP29yRSHKBTtfdBmHs0BUpgvtgF4yRFR+NUKi0XZcYjCeCG2smkzLAHkbRBmP0/Uk26O5YnUActBp1GsAI+S5nRJJJal5K1aAMrq0d6Tm9uI6zjyf75dAe6tx/SsWeD//o2/Ab6IH3/h25pOAAAAAElFTkSuQmCC) no-repeat left center; padding: 2px 0 2px 18px; }
.icon_folder_open { background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAHZSURBVHjaxFO/axRBFP5m726zId5hfiAxGlERTAIpExI4K4NVQBBTWsb8BVZCwEbQykYs05qUF0JSBLEVLAJJFQ0nufjrvOM25s7czuzO883s6rmg1RUOvJ03u+993/vevBVEhG6Wgy5X1wBZ89jfmNrxChMTQvyJRwhODipR6M/yIUhlCftsXZ/fDS2AIGdstLiSE0KYPJsM9sN27erXnUcVSt6alXELRJGqNj+/esDHNQuAkNrQsif48gKRzLCwAufn4Y3cxMj0Exe20ZphtAUXzpnhd6XicgdACYe05GSO1QR9eohq+TWw+/xf0l0n1zfwuweswdPhKeQPxpct1MtbuFB8Bm9wPBH8qwKKK8iexfvVG50mQjnI9p6zbn1/HdfubnOYZlUfEfrsR8eg0Nh39pvwLj5M3wIUj1PURu3NS1y+swzlb3KwD60aNplRYpDIALRQXl3E4OTSXgcg4OJ0aF0tvyWMfswaNRLm2KoV18b1T9271RkkSfRhZQGX5hc5uA5StXi31kjAjJ2ADiJmv19KSSCpG8jhvGq6LDzvEOW4lDx/GGJWM0MS5AQJ3VvDfjsN0A6eKvlp+mjt8Rw3Pf+3e8v09ntm77syU0oN5X//G38KMADaP/qtsmS7rgAAAABJRU5ErkJggg) no-repeat left center; padding: 2px 0 2px 18px; }
.icon_html { background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAJwSURBVDjLjZPdT1JhHMetvyO3/gfLKy+68bLV2qIAq7UyG6IrdRPL5hs2U5FR0MJIAqZlh7BVViI1kkyyiPkCyUtztQYTYbwJE8W+Pc8pjofK1dk+OxfP+X3O83srAVBCIc8eQhmh/B/sJezm4niCsvX19cTm5uZWPp/H3yDnUKvVKr6ELyinwWtra8hkMhzJZBLxeBwrKyusJBwOQ6PRcJJC8K4DJ/dXM04DOswNqNOLybsRo9N6LCy7kUgkEIlEWEE2mwX9iVar/Smhglqd8IREKwya3qhg809gPLgI/XsrOp/IcXVMhqnFSayurv6RElsT6ZCoov5u1fzUVwvcKRdefVuEKRCA3OFHv2MOxtlBdFuaMf/ZhWg0yt4kFAoVCZS3Hd1gkpOwRt9h0LOES3YvamzPcdF7A6rlPrSbpbhP0kmlUmw9YrHYtoDku2T6pEZ/2ICXEQ8kTz+g2TkNceAKKv2nIHachn6qBx1MI5t/Op1mRXzBd31AiRafBp1vZyEcceGCzQ6p24yjEzocGT6LUacS0iExcrkcK6Fsp6AXLRnmFOjyPMIZixPHmAAOGxZQec2OQyo7zpm6cNN6GZ2kK1RAofPAr8GA4oUMrdNNkIw/wPFhDwSjX3Dwlg0CQy96HreiTlcFZsaAjY0NNvh3QUXtHeHcoKMNA7NjqLd8xHmzDzXDRvRO1KHtngTyhzL4SHeooAAnKMxBtUYQbGWa0Dc+AsWzSVy3qkjeItLCFsz4XoNMaRFFAm4SyTXbmQa2YHQSGacR/pAXO+zGFif4JdlHCpShBzstEz+YfJtmt5cnKKWS/1jnAnT1S38AGTynUFUTzJcAAAAASUVORK5CYII) no-repeat left center; padding: 2px 0 2px 18px; }
.icon_image { background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAHwSURBVDjLpZM9a1RBFIafM/fevfcmC7uQjWEjUZKAYBHEVEb/gIWFjVVSWEj6gI0/wt8gprPQykIsTP5BQLAIhBVBzRf52Gw22bk7c8YiZslugggZppuZ55z3nfdICIHrrBhg+ePaa1WZPyk0s+6KWwM1khiyhDcvns4uxQAaZOHJo4nRLMtEJPpnxY6Cd10+fNl4DpwBTqymaZrJ8uoBHfZoyTqTYzvkSRMXlP2jnG8bFYbCXWJGePlsEq8iPQmFA2MijEBhtpis7ZCWftC0LZx3xGnK1ESd741hqqUaqgMeAChgjGDDLqXkgMPTJtZ3KJzDhTZpmtK2OSO5IRB6xvQDRAhOsb5Lx1lOu5ZCHV4B6RLUExvh4s+ZntHhDJAxSqs9TCDBqsc6j0iJdqtMuTROFBkIcllCCGcSytFNfm1tU8k2GRo2pOI43h9ie6tOvTJFbORyDsJFQHKD8fw+P9dWqJZ/I96TdEa5Nb1AOavjVfti0dfB+t4iXhWvyh27y9zEbRRobG7z6fgVeqSoKvB5oIMQEODx7FLvIJo55KS9R7b5ldrDReajpC+Z5z7GAHJFXn1exedVbG36ijwOmJgl0kS7lXtjD0DkLyqc70uPnSuIIwk9QCmWd+9XGnOFDzP/M5xxBInhLYBcd5z/AAZv2pOvFcS/AAAAAElFTkSuQmCC) no-repeat left center; padding: 2px 0 2px 18px; }
.icon_java { background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAILSURBVDjLrVM7ixNhFB2LFKJV+v0L24nabIogtmItin9ALBS3tXNt3CWgVlpMsAgrWRexkCSTd0KimYS8Q94vsnlrikAec7z34hSibON+cJjXPeee79xvFADK/0C5UIFyubxLUEulklooFNR8Pn+Sy+VOstmsmslk1HQ6raZSqd2/BCqVyh4RW/V6HePxGJPJRDCdTuU6Go0EZ2dnIFEkk8lWIpHYEwEi24lszGYzjHptfPvsgvbuEJ9ePMPH548Epwf70N4f4fuXY6rpYDgcIh6PG7FYzM62dSav12spfHXn2rk4fbmPxWIhIpFIRFfIzk+v1wvDMLAhka9vD+B88gCv79lxdPeG4M39W/jw9KF8q+oJzOdz2VIoFPqhOJ3O7mAwwHK5xGazketqtRKws3+Bto1arYZgMFhTHA6HC78XW6P0wYJmcAy2y+9arRYoPCHTpOD3+w8Vm8122xTgQhobqtUqms0mGo0GeDLckdOnESIcDqPdbnN3aJp2VbFarTfN7kxmUqfTkSLuyM8syB3pLMj7fr8Pn883kTFaLJbr1EHfbrdilwm9Xg/dblfABNMF3/NWisUiKPjHIkDrMou43e4CF+m6LkUMU4idcFc+WJwRkbU/TiKtS4QrgUDgmGZrcEcelXkKORsWJ9sGkV3n/kzRaHSHgtrQjEGCHJSAyBuPx7Nz4X/jL/ZqKRurPTy6AAAAAElFTkSuQmCC) no-repeat left center; padding: 2px 0 2px 18px; }
.icon_linux { background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAIuSURBVDjLjZNPiFJRFManVo24jYISClqli3KGp0VY7mSCBqKoIHIIahFtStwUCPVGIiloRJmkqQERiqBFIZGtcgrHrCGaFo+hCQlCU9/T558n/v+699Z7PBmjDnzcxbnnd8/53jtjAMaoSOwkmiDi/qFdRJu1Oh1gotVqyd1ut9/r9TBKJI9QKDSnh+gBXKfT6cfjcbhcLvj9flQqFVSrVXYWCgUGyefzCIfDGkQt3kQBklTGvv022A84yWlFJpNBvV6HLMsoFosM0Gw20Wg0EIlEfkP0AFEUEb53EYnnbpw5MYV0Os0KarUaSqXShpGYJ3pAWfyJ3IcjKH5y4NIpK5aX37O5FUVho9AHaCe5XG40IJlcwv1gAMLnFSw8fASfzwfiiwahnVA/JEnaCOA47mw0GkWvDxbZbBZmsxk8z2sQOg71hIKGAB6PZ9xms60KggA16AWv1wuDwcBgFNJutxmEaghwbPr4Ubd7hhUOBgMNkkgkYDQakUqlQP4PBqCi3QwBzp+bPulwHEaHXKIJNW4H7mDLuAHr699YB+ooQ4DCu8u7f7yaeum0b8ObpbRW/H31KSatFph2bCfGiRpAlQZYix16lnuyF8Gre/BgYRFKkzjekJGcd+L66a14ccuM8pebbAS9NMDHxzMX1hYt+ZV5S+3aFTcCd+cwO8sjduMg3gat+BqzQ3jNj9qNvubBn085SQxSaOJvy6QvJnfrbHt1ABOF/Mc6q6Krb/oFGtGkE2IcdecAAAAASUVORK5CYII) no-repeat left center; padding: 2px 0 2px 18px; }
.icon_music { background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAQAAAC1+jfqAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAETSURBVBgZfcExS0JRGIDh996OFIQEgSRhTS1Bg0trw937B9UPCAT3hnJ1kYbGhrv0BxoaXSsMhBCsyUEcoiTKUM/3HU8Fce4Q+DyRZz5DcOkdiqIIiiAo7xiCMXs4HI4ZisPhOMcQOJQbOoxxKHm22UUxBBbHM1cRfw58GUtMIAieTIwgxAQWRclMEZSYwCIIGYsixASCYsl4pgiGwDFF+HWUaDopbfCGHRp+nCWSTktFXvFDOKyuNNYp4LhFriPPaXW5UWAV5Y6HNH+/dbHJIjN6NHlJzMnxWqNIDqFHh8/U7hiEJbp0+ar0m2a4MGFEjie6jCrtJs1y57FuI21R6w8g8uwnH/VJJK1ZrT3gn8gz3zcVUYEwGmDcvQAAAABJRU5ErkJggg) no-repeat left center; padding: 2px 0 2px 18px; }
.icon_pdf { background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAHhSURBVDjLjZPLSxtRFIfVZRdWi0oFBf+BrhRx5dKVYKG4tLhRqlgXPmIVJQiC60JCCZYqFHQh7rrQlUK7aVUUfCBRG5RkJpNkkswrM5NEf73n6gxpHujAB/fOvefjnHM5VQCqCPa1MNoZnU/Qxqhx4woE7ZZlpXO53F0+n0c52Dl8Pt/nQkmhoJOCdUWBsvQJ2u4ODMOAwvapVAqSJHGJKIrw+/2uxAmuJgFdMDUVincSxvEBTNOEpmlIp9OIxWJckMlkoOs6AoHAg6RYYNs2kp4RqOvfuIACVFVFPB4vKYn3pFjAykDSOwVta52vqW6nlEQiwTMRBKGygIh9GEDCMwZH6EgoE+qHLMuVBdbfKwjv3yE6Ogjz/PQ/CZVDPSFRRYE4/RHy1y8wry8RGWGSqyC/nM1meX9IQpQV2JKIUH8vrEgYmeAFwuPDCHa9QehtD26HBhCZnYC8ucGzKSsIL8wgsjiH1PYPxL+vQvm5B/3sBMLyIm7GhhCe90BaWykV/Gp+VR9oqPVe9vfBTsruM1HtBKVPmFIUNusBrV3B4ev6bsbyXlPdkbr/u+StHUkxruBPY+0KY8f38oWX/byvNAdluHNLeOxDB+uyQQfPCWZ3NT69BYJWkjxjnB1o9Fv/ASQ5s+ABz8i2AAAAAElFTkSuQmCC) no-repeat left center; padding: 2px 0 2px 18px; }
.icon_php { background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAGsSURBVDjLjZNLSwJRFICtFv2AgggS2vQLDFvVpn0Pi4iItm1KItvWJqW1pYsRemyyNILARbZpm0WtrJ0kbmbUlHmr4+t0z60Z7oSSAx935txzvrlPBwA4EPKMEVwE9z+ME/qtOkbgqtVqUqPRaDWbTegE6YdQKBRkJazAjcWapoGu6xayLIMoilAoFKhEEAQIh8OWxCzuQwEmVKtVMAyDtoiqqiBJEhSLRSqoVCqAP+E47keCAvfU5sDQ8MRs/OYNtr1x2PXdwuJShLLljcFlNAW5HA9khLYp0TUhSYMLHm7PLEDS7zyw3ybRqyfg+TyBtwl2sDP1nKWFiUSazFex3tk45sXjL1Aul20CGTs+syVY37igBbwg03eMsfH9gwSsrZ+Doig2QZsdNiZmMkVrKmwc18azHKELyQrOMEHTDJp8HXu1hostG8dY8PiRngdWMEq467ZwbDxwlIR8XrQLcBvn5k9Gpmd8fn/gHlZWT20C/D4k8eTDB3yVFKjX6xSbgD1If8G970Q3QbvbPehAyxL8SibJEdaxo5dikqvS28sInCjp4Tqb4NV3fgPirZ4pD4KS4wAAAABJRU5ErkJggg) no-repeat left center; padding: 2px 0 2px 18px; }
.icon_ppt { background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAHeSURBVDjLjZO/i1NBEMc/u+/lBYxiLkgU7vRstLEUDyxtxV68ykIMWlocaGHrD1DxSAqxNf4t115jo6DYhCRCEsk733s7u2PxkuiRoBkYdmGZz3xndsaoKgDGmC3gLBDxbxsA31U1AKCqzCBXsywbO+e8iOgqz7JM2+32W+AiYFX1GGDHOeen06mmabrwyWSio9FI+/2+ioj2ej3tdDoLiJm+bimAhgBeUe9RmbkrT5wgT97RaDQoioIQAt1ud7/Var1h+uq+/s9+PLilw+FwqSRgJ1YpexHSKenHF4DFf/uC3b7CydsPsafraO5IkoTxeEwIARGh2WwCYNUJAOmHZ5y4eY/a7h4hPcIdHvDz/fMSnjviOCZJEiqVCtVqdfEl8RygHkz9DLZWQzOHisd9OizfckcURRhjMMbMm14CQlEC/NfPjPd2CSJQCEEEDWYBsNZijFkaCqu5Ky+blwl5geaOUDg0c8TnNssSClkER1GEtXYZcOruI6ILl1AJqATirW02Hr8sFThBVZfklyXMFdQbbDzdXzm78z4Bx7KXTcwdgzs3yizuzxAhHvVh4avqBzAzaQa4JiIHgGE9C3EcX7ezhVIgeO9/AWGdYO/9EeDNX+t8frbOdk0FHhj8BvUsfP0TH5dOAAAAAElFTkSuQmCC) no-repeat left center; padding: 2px 0 2px 18px; }
.icon_psd { background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAALqSURBVDjLjVPbS9NRHN9T/0BWFBQUVIQKXigCpVIyygcDJUrnZdNEc5hlmZc0TdSF0bxszs3LvM3pXE6Xa3NL0TJTl7pcTY1Ec5rX/WxqCrHhp7NBPaRGD5+Xw/lczud8vzQAtJ0wWhLp+4kbUThSFGrQFwTrB59dE+ryA/3+vreNOMaPOmLkMeUT4mTMaoph1klh7pPApOLAUH4LPTn+X7qzLwXsKDDGj0wy8hibM+oCrI9pYTWGA0ZnWEd8sWZQYvXDC5g0XAzyo6BJP5f/R2C89OYeErlquiUPP9vogNgF1iYfbH10B0zxRMQFC4oszMsz8F3XBOqdBKqUs7a2B6fdHAIkMnu6le1w3WrwBLrjHSKWrhhYh72w2kVHjTIIae3eKFJexkp/I0YlKWhJdKsgZIanoTjMtlHPxSY9BD/YgbA2eGPteRjmWzOJazrmZKl4rL4AQT8TD4nIfPMjzKgKIUtwNtJIyxXftISclICN3GxYfHyw3FEEy1ALLIPNsOhkWGzLw5umCHCUflBLr2O29i4WXgnQwDpB0YY5NyapASmoxlxQrGAsFrAIWQ6D6Da0GecxXBaLFfLmuHI+TgrkCBCIYKqIwVKHEHWxxzZp758GbTrc9AqYu4WYb8kkRcnsLcPejzL5DKi3dfAQSEFX9RKRZkzxQklKIaqjD4PW9+QqVy+IxmdpOkwvOaB6xVjpa8QQOSMtY4DHAPW6GuLSVFwprUJxSQYWlRyMS9JQGXlw3PELZDB8OzN9c0hkdXua1/pYfTKonloHkeoWYVachCkuHZNFwZhrTMeCmov2rIsoY+wL2TaJJLKr4r6HzUyIpso4R9yp4mB8LWFgScPHtJyNjhx/CCOcCnccZTua77jKRkiJy51lmKlJxJK2lJBLoOMxiet+myDcKWXXXbBDGn/KTcI6brO7TUgzMcBl4Pk9d3tkhSB8r+s/l+k36mKOJpKW10VRh/TlzAOFJLLnTvd2Ffhf/AKfTM1hskDhXAAAAABJRU5ErkJggg) no-repeat left center; padding: 2px 0 2px 18px; }
.icon_ruby { background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAIESURBVDjLjZNPTxNBGIexid9CEr8DBr8CHEiMVoomJiQkxBIM3dgIiaIESJTGGpVtyXIzHhoM4SIe9KAnEi4clQtJEczWFrbdP93d7s7u/JwZ7XYJBdnkyRxmfs/MvO9OD4AeDvuuMPoY/f/hKiMR5WKCvlarpRNCwiAI0A02D1mW38QlcUE/DzebTdi2HWEYBhqNBqrVqpBUKhUUCoVI0g5f4gK+wHVdeJ4nRo5lWdB1HbVaTQgcxwHfRFGUvxIuCKYfzmqZyZ2wKIO8fQ3/1Uv4Sy/QWliAO/sU9qMZmFMS3HfvT1xJ1ITOZJ9RpQi6+RH0y2fQb19BP23CVhRo+TysXA71+XkcMIk6fAfHK6tQVfWEoESXngNra0C5DHZJYGMDZiaD35IEi41qOo3vc3MoJ1Ooj92HpmkdQZiVEsHUAzl88hjY3gYIAdbXYQ0MoDo4CH1kBHssvH8jCf3eGKzDXzBNsyNoF/HH7WSJZLPA7i6wtQVnaAhmKoXjxUX8vDkMY3Qcnm6IInJOCS4nEte9QhF+RhInIRMTcFhYvZWCcXcUPmsl7w6H/w+nBFEb5SLc8TTo8jLq7M4m25mHfd8X8PC5AtHrXB5NdmwRrnfCcc4VCEnpA8jREasp6cpZAnrWO+hCGAn+Sa6xAtl84iJhttYSrzcm6OWSCzznNvzp9/4BgwKvG3Zq1eoAAAAASUVORK5CYII) no-repeat left center; padding: 2px 0 2px 18px; }
.icon_script { background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAALtSURBVBgZTcFLaFxVAIDh/5577jwzj0wSUmqMtKIiBltbbJ1FUCxVoQu3FrHGVRU3BVcKrkTcKOhCUOtOAyJ23WIQtFawpoooZWKJpnbsNJN5PzP3PO5xArPo93nOOfasXCgfAz48mE8UhzpiqCN0FLFrog7QA+qABVpAA/gC+FYyERlz/NC+qeIbT85xt4GKckMV5Voju6A09ELLzXqfi38PTgLnJBORMfPZmMeectsSeB7SA19CPBAsxgW+EAQ+PLaQZH8uXTj/S+UDwYTVOitxmAh6yqOjoR1CZwSdETR2Yadv2fPm6i2KB9IszQZzkgkVmvnLZcuP21VeO1rgs+tdAu1YOZxlKiHw8fA9iADPdvn5nxa/3epUBGOH39sqjETu2UJG4oUwDB2RcmRSHuevdtjpWgZhxEBH4KDaDflobbNrlVoRh97demHpgfTth+5J5ZpNw5kjWQxw6mCa7aYlk4bPr7X54XqfkfGIHNjAYpQ6cOH1x9fEw/cnP13M+Ik7bc3ZYxniMR9PQCElObmYptox7E97XK0MscbhHJgwxKrQMiZ+v9Y9u3knHBUCn08ut6m2DQJHe6C5WOqQl4KbVcXR2QSxwENbS38wNEapLmNi4/0Hv/r3zxvHN0p1YnGP1e/r4ODr9TbZlKBTU7xSnKG4lCUZQKMfYkJVvfT2c44xyVjKr6lpEUI3g3UOPIE1lu6O5aUTcyRjPjhISUGttYtVYYUJuXxudRZ4p/jIvZx+eoHvSopmz/Ly8jyJwBFIkD7EfMimYLM8xChVZUJapU4Ap34tbdHalfRDh7aOUHsoE2FsROQchVyOV5/Zx3ZjiFWqxoS0Wh95/qlHk2+9+AR3sw60dSgDOPj4UoVUAL3+EKt1gwlptd7arnf4cq1EfipJPpsgn46TS8fJpGLEY4K4FJxenicuodbsYbX+jwkZGfPNlfWNhSvrG/cBM8AMMA1MA7lELAgSiYBsOkk+m+KPv8o3gJ+Y+B9yFXCQeyJWrQAAAABJRU5ErkJggg) no-repeat left center; padding: 2px 0 2px 18px; }
.icon_txt { background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAQAAAC1+jfqAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAADoSURBVBgZBcExblNBGAbA2ceegTRBuIKOgiihSZNTcC5LUHAihNJR0kGKCDcYJY6D3/77MdOinTvzAgCw8ysThIvn/VojIyMjIyPP+bS1sUQIV2s95pBDDvmbP/mdkft83tpYguZq5Jh/OeaYh+yzy8hTHvNlaxNNczm+la9OTlar1UdA/+C2A4trRCnD3jS8BB1obq2Gk6GU6QbQAS4BUaYSQAf4bhhKKTFdAzrAOwAxEUAH+KEM01SY3gM6wBsEAQB0gJ+maZoC3gI6iPYaAIBJsiRmHU0AALOeFC3aK2cWAACUXe7+AwO0lc9eTHYTAAAAAElFTkSuQmCC) no-repeat left center; padding: 2px 0 2px 18px; }
.icon_vbs { background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAMAAABEpIrGAAAAElBMVEX///+AgIAAAADAwMAA//8AgIDFJPs2AAAAsElEQVQ4jY2RgQ7EIAhDQbz//+VTObRIl12XmWw8K1QREWUSUPqIf+0NQIICBgQFBAgOAKFxLIygbQkB7WNZr0XBEFD5LM16jIDA2i++X828lIAw8LoTGUgGDDgOu//q0KfDCSNP0bfFAzC3r+cQloP6dQFEBojHBVSPGygeFeiZuHsItYjjnsKjmlEyh7jOURfqoPtw5T1oR4JN4bc8pM14Dg3Ekyz6H7AntXr4JZEv7zkD3YWtwx8AAAAASUVORK5CYII) no-repeat left center; padding: 2px 0 2px 34px; }
.icon_xls { background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAIpSURBVDjLjZNPSFRRFMZ/9707o0SOOshM0x/JFtUmisKBooVEEUThsgi3KS0CN0G2lagWEYkSUdsRWgSFG9sVFAW1EIwQqRZiiDOZY804b967954249hUpB98y/PjO5zzKREBQCm1E0gDPv9XHpgTEQeAiFCDHAmCoBhFkTXGyL8cBIGMjo7eA3YDnog0ALJRFNlSqSTlcrnulZUVWV5elsXFRTHGyMLCgoyNjdUhanCyV9ayOSeIdTgnOCtY43DWYY3j9ulxkskkYRjinCOXy40MDAzcZXCyVzZS38MeKRQKf60EZPXSXInL9y+wLZMkCMs0RR28mJ2grSWJEo+lH9/IpNPE43GKxSLOOYwxpFIpAPTWjiaOtZ+gLdFKlJlD8u00xWP8lO/M5+e5efEB18b70VqjlMJai++vH8qLqoa+nn4+fJmiNNPCvMzQnIjzZuo1V88Ns3/HAcKKwfd9tNZorYnFYuuAMLDMfJ3m+fQznr7L0Vk9zGpLmezB4zx++YggqhAFEZ7n4ft+HVQHVMoB5++cJNWaRrQwMjHM9qCLTFcnJJq59WSIMLAopQDwfR/P8+oAbaqWK2eGSGxpxVrDnvQ+3s++4tPnj4SewYscUdUgIiilcM41/uXZG9kNz9h9aa+EYdjg+hnDwHDq+iGsaXwcZ6XhsdZW+FOqFk0B3caYt4Bic3Ja66NerVACOGttBXCbGbbWrgJW/VbnXbU6e5tMYIH8L54Xq0cq018+AAAAAElFTkSuQmCC) no-repeat left center; padding: 2px 0 2px 18px; }
.icon_xml { background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAIAAAACACAYAAADDPmHLAAAGWUlEQVR42u2dgXXkNBCGJxVwVMBeBYQKSCrgqABTAaECTAWECtir4KCCWyogqQBTAUkFh+dsEWX8jy17d+O9nf97Ty9vndgrS5+lkezIF/Kcz9pU9elSyDlx16Ztnx7TxovsD75s0+9t2qydU3JUmja9adO9fkgCaOXv2vRq7dyRF+GhTVdtulcBtNnX5mGzdq7Ii9K06VIF+KFNt2vnhqzCjQrwl+CAT+MBinEe3EjX71vuVIAP4Bda+d+unWtyUN4JkMAT4KpNf66dY3JQvpYu0H8GBYgDBQgOBQgOBQgOBQgOBQgOBQgOBQgOBQgOBQgOBQgOBQgOBQgOBQgOBQgOBQgOBQgOBQgOBQgOBQgOBQgOBQgOBQgOBQgOBQgOBQgOBQgOBQgOBQgOBQgOBQgOBQgOBQgOBQgOBQgOBQgOBQgOBQgOBQjOSQnwm+C1iXUhw0dnny+kW8besgN5fe8co55xXt8536frKv84cx9dqfN+vyLbm5MS4Je+UCxVm946+3hrGusyt7ZwPzjH0P29yrO878vBsmvTtbPPT9JJZtHjrN2inpQAejU3YPvYErVoTWO9Gr8Cf+sJoAXgVd4hjkEBZuAtUr1p0z9mmydMJbjF8CovnfMUsLB6POkUCjAD7S+3YLt2Db+abaj515cebGQYM4xVXum5eRWZuJi5X8l3HpuTE0BfVPEAtqMrDLUW2zZ9X3qiGXWbfp7IG1xZO4MCHAgdDVRg+0aeugGv+UfBn3uiGfq7qTjgXxl/fQ4FOBDfSBf4WfJoHTX/TZtezzymPW8PfX/S3cL9KcAC/pbhnEAjTxWMmmMUJySm+m/Faz2UklfoeOVDARbgFdpGum4ANcf6+XHm8XLGBPK6pZLyoQAL8Pr4mz7DtjneCg7+ErYSdH8bQI7NN9gWqZFhC+WVDwVYCJp104rTiqpn5s1ewXrSG3leiTr6+Bzsi2TUPNguyMsDBViINyfQyPBqfD1xLCvTbX8MW4m6zU44oXzUMqxUrwuhAAvROYFGpt9cOtZ3J6wAtfmZqGQ4i2jvUez6ZPfVz2gugQLsQUnwNRb8JZAAO3DyWxnGEnbCSVuPB6EA62Uwo/RdhnbUUMtTReY0MuxObFlU0nUVtdmunynAEUBzAgntw/8oOIY9n1q6ykLTyXmLggpI81LJsFK3gkciFGBPxrqBkrt4Ir4A6BmEXCpbeWmkgALDneDpZAqwB97NocTSFqCSLthDU8T5lLOdcdTCunYKLv3OQgH2wBsKJkpiAHSS6VyQYPmdR9v91NK1HBTghfAeEMmZGgWMCeB9x6s+NWZ7anHQMb2HQijAQrzpYMvUPMCUACgOuOp/2v2SbN7oBMUkFGAhqGJqGRZmI+Mzgaifz8/Fm+nLfyr5Fe7FJhTggNixe4rA0bBwLG+oAvK/9+b69fuqbJt9ehiVEQU4EOiqTQEfahm24t8NnBJAsVLp1f4gz2cP7T6ojFA8QgEWgB74qKQbunlP53jBIKoAe6WWTDnbfUrLiALMxOtfN/J0pw51A14wiCrXVubUcFMLyQ7x0EMpqIwowEzQ41d2iIW6gUZwMIieK7ACTI04ahnO86PjojKiADNB43IbgHndAMpjiQDK2D2H0uOiv6MAM/AqFj2wWfp/AaUCzL3ngI5by7CloAAzmNO0e0/q2mAQ9dWoQr04wJtu3leANNIYQ/PzVo7HyQmAKsv7792xB0fzYLB0vF56vASqWP1cKkAJ6HiH5KQE8P55Y+x5fXQVNvK8xSgVQEFxgPf9FODAoLF/I+PTvF6zned1jgA2DvCeFFZQxaLuggIU4I39tzL+vP/UfnNu2qQCuco+N+L3wahi9buuC/6uFN0vhABkHShAcChAcChAcChAcChAcChAcChAcChAcChAcChAcChAcChAcChAcChAcChAcChAcChAcChAcChAcChAcChAcChAcChAcChAcChAcChAcChAcChAcChAcChAcChAcChAcChAcChAcChAcChAcChAcChAcGYJUPqaVvLpgBbn/CiA97o2leBWyDmgayC/AdvvVABvJW5y/tyoALoEqy5nvlk7N+RFadp0mdbR1Zc37GS4fDs5T9Jb0u7zhZRVAu33N2vnjhyVRrp44OOy+HYlbe0Oqj5NvceXfFpoN7/t0/9vWfkP33X5xQW5TVgAAAAASUVORK5CYII) no-repeat left center; padding: 2px 0 2px 130px; }
.icon_xml32 { background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAIAAAD8GO2jAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAVlJREFUeNrslj2yREAQx1EbkEscQijmAI4hkbuK3BFUiWRUSTdVSu4MAoHX9frVVGu262F2o/0Hqs1H/1rP9Bhj3co4qaqqVlEG856m6fpvIWOeZ2GMxSJaluXsRziOI/Rahg6ZpvlegMDQBnjF0Ak4ZGgGgKIo0gaAXThvlSRJ27Z0zONmvLZtywP0p4jpwXJXlmXf95fdjeOIrpqm+Vt2rHihUu6sEE/R81f71ziOWVfXdbSF9XIOPUTVK+wHMOq6BjvPc2wfhoEOUxMPT+LNacpa1ZmqAgR7mibqEamnATQ613Xpgez7Po5EXpZldwH7vMET1gOewEDjFqAoij1A2eqDrqwBRrefTwGe510HgAHhgwG/XNoYBAEDQygUfBCWsNtY4KoxDENlYxoFwGcr+R2yWDZOXVv2gukspRquLfL0T6XoCxAuMrSydFUD9Wm96tDiHfQjwAD0driCV3U7SAAAAABJRU5ErkJggg) no-repeat left center; padding: 2px 0 2px 34px; }
.icon_zip { background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAQAAAC1+jfqAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAEUSURBVCjPXdFNSsMAEIbh0Su4teAdIgEvJB5C14K4UexCEFQEKfivtKIIIlYQdKPiDUTRKtb0x6ZJ+volraEJ3+zmycwkMczGzTE3lwkbxeLE5XTqQfTIjhIm6bCy9E/icoOoyR4v7PLDN+8ibxQHxGzE3JBfHrgUalDnQ6BNk1WRFPjs66kDNTxqg0Uh5qYg4IkrjrS9pTWfmvKaBaGaNU4EY+Lpkq88eKZKmTAhbd3i5UFZg0+TzV1d1FZy4FCpJCAQ8DUnA86ZpciiXjbQhK7aObDOGnNsUkra/WRAiQXdvSwWpBkGvQpnbHHMRvqRlCgBqkm/dd2745YbtofafsOcPiiMTc1fzNzHma4O/XLHCtgfTLBbxm6KrMIAAAAASUVORK5CYII) no-repeat left center; padding: 2px 0 2px 18px; }

#filemanager_left {width: 48%; float: left; height: 500px; overflow-y: scroll;}
#filemanager_xxx {width: 48%; float: left; border-right: 1px solid black; height: 500px; overflow-y: scroll;}
#filemanager_right {width: 48%; float: left; padding-left: 4px;}
</style>
<?php
		print $forms->open(array("data"=>TRUE));
	    print $forms->hidden("action");
	    print $forms->hidden("file");
	    print "<div id='filemanager_left'>" . implode("\n",$this->tree) . "</div>\n";
	    print "<div id='filemanager_right'></div>\n";
	    print $forms->close();
	    $menu->copyright();
	}
}
?>