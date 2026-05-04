<?php
require_once "scs_header.php";
require_once "classes/inventory.php";

$database->user->access("Administrator");
$forms->title("Inventory PRJ Import");
$forms->report['action']=$_POST['action'];
$forms->report['filename']=$_POST['filename'];
switch ($forms->report['action']) {
  case "cancel":
	$forms->message[]="Request Cancelled";
	break;
  case "upload":
	switch (TRUE) {
	  case (!$_FILES['prj']['name']):
		$forms->error('prj',"File required");
        break;
	  case (intval($_FILES['prj']['error'])):
		$forms->error('prj',"Upload error #" . $_FILES['prj']['error']);
        break;
    }
    if (sizeof($forms->error)) break;
    $forms->report['filename']=$_FILES['prj']['name'];
    if (file_exists("scs.tmp")) unlink("scs.tmp");
    move_uploaded_file($_FILES['prj']['tmp_name'],"scs.tmp");
    $lines=preg_split("/\n/",file_get_contents("scs.tmp"));
	unlink("scs.tmp");
	$results=array();
    foreach ($lines as $line => $item) {
	    if (!$line) continue;
	    $data=str_getcsv($item,";");
        $sku=current(preg_split("/_/",trim($data[3])));
        $prj=parse_url(trim($data[4]));
		if ( (strlen($sku)) && (strlen($prj['query'])) && (!array_key_exists($sku,$results)) ) $results[$sku]=$prj['query'];
    }
	$forms->report['upload_count']=sizeof($results);
    if (!$forms->report['upload_count']) $forms->error('prj',"Empty or corrupt file");
    if (sizeof($forms->error)) break;
	$forms->message[]="Review Selection";
    $fh=fopen("scs.tmp","w");
    foreach ($results as $sku => $prj) {
		fputcsv($fh,array($sku,$prj),"\t");
    }
    fclose($fh);
	break;
  case "update":
	if (!file_exists("scs.tmp")) {
		$forms->message[]="Upload file not found";
        break;
    }
	$update_list=array("changed"=>"Changed","not_changed"=>"Not Changed","missing"=>"Missing/Invalid SKU");
	$update_items=array();
    foreach ($update_list as $code => $code_name) {
		$update_items[$code]=array();
    }
	$update_errors=array();
	$database->temp->meta->error_abort=FALSE;
    $fh=fopen("scs.tmp","r");
    while (list($sku,$url_query) = fgetcsv($fh,0,"\t")) {
		parse_str($url_query,$prj_query);
        $prj=array();
        foreach ($prj_query as $key => $value) {
			if ( (in_array($key,array("prjCatalog","prjNode","prjName","values"))) && (strlen($value)) ) $prj[]="$key=$value";
        }
        if (sizeof($prj) != 4) continue;
		$query="update inventory set prj=" . fn_escape(implode("&",$prj)) . " where sku=" . fn_escape($sku);
        $database->temp->query($query);
        if ($database->temp->affected_rows()) {
			$update_items['changed'][$sku]=$sku;
		  } else {
			$update_items['missing'][$sku]=$sku;
        }
    }
	fclose($fh);
	$query_where=array();
    $query_where[]=new query_where("sku","in",array_keys($update_items['missing']));
	$database->temp->query("select sku from inventory " . $database->where($query_where));
    while ($database->temp->fetch = $database->temp->fetch_array() ) {
		$update_items['not_changed'][$database->temp->fetch['sku']]=$sku;
        unset($update_items['missing'][$database->temp->fetch['sku']]);
    }
	$database->temp->free_result();
    $comment=array();
    $comment[]="File: " . $forms->report['filename'];
    foreach ($update_list as $code => $code_name) {
		if (sizeof($update_items[$code])) $comment[]="SKU's $code_name:" . number_format(sizeof($update_items[$code]));
    }
	$database->log->ipc("Import: Inventory PRJ",$comment);
	unlink("scs.tmp");
	break;
}
$menu->head();
print $forms->message();
?>
<script type="text/javascript">
function fn_action(action) {
	if (action=='update') {
	    if (!confirm("Update Inventory PRJ?")) {
            return;
	    }
    }
	document.scs_form.action.value=action;
	document.scs_form.submit();
}
</script>
<?php
print $forms->open(array("data"=>TRUE));
print $forms->hidden("action");
print $forms->hidden("filename",$forms->report['filename']);
switch (TRUE) {
  case ($forms->report['action']=="update"):
	print "<table class='standard noborder'>\n";
	print "<tr>\n";
	print "<td width='30%'>File name:</td>\n";
	print "<td width='70%'>" . $forms->report['filename'] . "</td>\n";
	print "</tr>\n";
	print "</table>\n";
	print "<div class='accordion'>\n";
    foreach ($update_list as $code => $name) {
		if (!sizeof($update_items[$code])) continue;
	    print "<h3>" . $name. " (" . number_format(sizeof($update_items[$code])) . ")</h3>\n";
	    print "<div>" . implode("<br>",$update_items[$code]) . "</div>\n";
	}
	print "</div>\n";
    print "<p>" . $forms->button("Return",array("onclick"=>"fn_action('');")) . "</p>\n";
	break;
  case ( ($forms->report['action']=="upload") & (!sizeof($forms->error)) ):
	print "<table class='standard noborder'>\n";
	print "<tr>\n";
	print "<td width='30%'>File name:</td>\n";
	print "<td width='70%'>" . $forms->report['filename'] . "</td>\n";
	print "</tr>\n";
	print "<tr>\n";
	print "<td>Unique records:</td>\n";
	print "<td>" . number_format($forms->report['upload_count']) . "</td>\n";
	print "</tr>\n";
	print "</table>\n";
    $buttons=array();
    $buttons[]=$forms->button("Update",array("onclick"=>"fn_action('update');"));
    $buttons[]=$forms->button("Cancel",array("onclick"=>"fn_action('cancel');"));
    print "<p>" . implode(" ",$buttons) . "</p>\n";
	break;
  default:
	print "<table class='standard noborder'>\n";
	print "<tr>\n";
	print "<td width='30%'>File name</td>\n";
	print "<td width='70%'>" . $forms->file("prj",40);
    if ($forms->error_text['prj']) print "<br>" . $forms->error_text['prj'];
	print "</td>\n";
	print "</table>\n";
    print "<p>" . $forms->button("Upload",array("onclick"=>"fn_action('upload');")) . "</p>\n";
}
print $forms->close();
$menu->copyright();
?>