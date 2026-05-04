<?php
require_once "classes/cad.php";
require_once "classes/cadorder.php";
require_once "classes/cadorder_update.php";
$forms->title("CAD Cart");
$forms->report['action']=$_POST['action'];
$forms->report['order']=array();
switch ($forms->report['action']) {
  case "":
    break;
  default:
	foreach ($_POST as $key => $value) {
		if (preg_match("/^order_/i",$key)) $forms->report['order'][]=$value;
    }
    switch (TRUE) {
 	  case (!sizeof($forms->report['order'])):
		$forms->error('null','','No orders selected');
        break;
	  case ($forms->report['action'] != "complete"):
		$forms->message[]="Review your selections";
        break;
	  default:
	    $zip=new ZipArchive();
	    $filename="cad" . dechex(strtotime("now")) . ".zip";
        $url=$_SERVER['DOCUMENT_ROOT'] . $database->registry->cadcart->folder . $filename;
	    $zip->open($url, ZIPARCHIVE::CREATE);
		$query_where=array();
        $query_where[]=new query_where("user_id","=",$_SESSION['user']->id);
        $query_where[]=new query_where("id","in",$forms->report['order']);
        $query_where[]=new query_where("status","in",array('Pending','File','Retrieve'));
        $query =
        	"select * from cadorder \n\r" .
            $database->where($query_where);
		$database->cadorder->query($query);
        $manifest=array();
        $manifest[]="CAD Package manifest created " . date("F j, Y g:i A");
        while ($database->cadorder->fetch = $database->cadorder->fetch_array() ) {
			$database->cadorder->fetch();
            $manifest[]="";
            $manifest[]="== " . $database->cadorder->data->ps_zipfile . " Contents ====================";
            $manifest[]="Order #" . $database->cadorder->data->id;
            $manifest[]="SKU: " . $database->cadorder->data->sku;
            $manifest[]="Product Name:" . $database->cadorder->data->sku_name;
            foreach ($database->cadorder->data->ps_contents as $contents_key => $contents) {
				$manifest[]="";
				$manifest[]= $contents->file;
				$text="View: " . $contents->view;
                $text .= " Format: " . $contents->cad_code;
                if ($contents->version) $text .=" Version: " . $contents->version;
				$manifest[]=$text;
            }
			$zip->addFile($database->cadorder->file_url(), $database->cadorder->data->ps_zipfile);
        }
        $zip->addFromString("manifest.txt",implode("\n\r\n\r",$manifest));
		$database->cadorder->free_result();
        $zip->close();
	    header("Content-type: text/plain");
	    header("Content-Disposition: attachment; filename=" . $filename);
	    header('Content-Transfer-Encoding: binary');
	    header('Content-Length: ' . filesize($url));
	    header('Connection: close');
	    readfile($url);
		unlink($url);
        $query =
			"update cadorder \n" .
            "set status='Retrieve',status_date=" . fn_escape(strtotime("now")) . " \n" .
            $database->where($query_where);
		$database->cadorder->query($query);
        die;
    }
}
$database->user->cadcart();
$menu->head();
$forms->message();

?>
<script type='text/javascript'>
function fn_action(action) {
    if (!confirm('Continue to Download Files?')) return;
	document.scs_form.action.value=action;
	document.scs_form.submit();
}
</script>
<?php
$query_where=array();
$query_where[]=new query_where("cadorder.user_id","=",$_SESSION['user']->id);
$query_where[]=new query_where("cadorder.status","in",array('Pending','File','Retrieve'));
$query=
	"select \n" .
	"if (cadorder.firm=" . fn_escape($_SESSION['company']->firm) . ",1,2) as sort_key1, \n" .
    "case cadorder.status \n" .
    "when 'File' then 1 \n" .
    "when 'Retrieve' then 2 \n" .
    "else 3 \n" .
    "end as 'sort_key2', \n" .
    "cad.name as 'cad.name', \n" .
    "cadorder.* from cadorder \n" .
    "left join cad on cad.code=cadorder.cad_code \n" .
    $database->where($query_where) .
    "order by sort_key1, firm, sort_key2, initial_date";
$database->cadorder->query($query);
$results=array();
while ($database->cadorder->fetch = $database->cadorder->fetch_array() ) {
	$database->cadorder->fetch();
    $firm=$database->cadorder->data->firm;
    if (!array_key_exists($firm,$results)) $results[$firm]=array();
    $results[$firm][]=new cadcart_class($database->cadorder->data,$database->cadorder->fetch);
}
$database->cadorder->free_result();

print $forms->open();
print $forms->hidden("action");
print "<table class='small border'>\n";
print "<tr>\n";
print "<th rowspan=2>Retrieve?</th>\n";
print "<th rowspan=2>Status</th>\n";
print "<th rowspan=2>SKU</th>\n";
print "<th rowspan=2>Description</th>\n";
print "<th rowspan=2>CAD Format</th>\n";
print "<th colspan=3>Dates</th>\n";
print "</tr>\n";
print "<tr>\n";
print "<th>Initial</th>\n";
print "<th>Last</th>\n";
print "<th>Expires</th>\n";
print "</tr>\n";
foreach ($results as $firm => $items) {
    if (sizeof($results) > 1) print "<tr><td colspan=8 class=standard>Company: <b>$firm</b></td></tr>\n";
    foreach ($items as $item) {
		print "<tr>\n";
	    if ($item->status=="File") {
	        $compare=$item->id;
	      } else {
	        $compare=0;
	    }
	    if ($item->status=="Pending") {
	        $forms->report['pending']=TRUE;
	        $text="&nbsp;";
	      } else {
	        $text=$forms->checkbox("order_" . $item->id,$item->id,$compare);
	    }
	    print "<td class=center>$text</td>\n";
	    switch ($item->status) {
	      case "Pending":
	        $image="/sc/images/vlight_red.png";
	        break;
	      case "File":
	        $image="/sc/images/vlight_green.png";
	        break;
	      default:
	        $image="/sc/images/vlight_none.png";
	    }
	    print "<td class=center>" . $forms->img($image) . "</td>\n";
	    print "<td>" . $item->sku . "</td>\n";
	    print "<td>" . $item->sku_name . "</td>\n";
	    print "<td>" . $item->cad_name . "</td>\n";
	    print "<td class=center>" . date("m/d/Y g:i A",$item->initial_date) . "</td>\n";
	    if ($item->status == "Retrieve") {
	        $text=date("m/d/Y g:i A",$item->status_date);
	      } else {
	        $text="Never";
	    }
	    print "<td class=center>$text</td>\n";
	    print "<td class=center>" . date("m/d/Y g:i A",($item->initial_date + ($database->registry->cadcart->expire_days * 60 * 60 * 24))) . "</td>\n";
	    print "</tr>\n";
    }
}
print "</table>\n";
if ($database->registry->cadcart->cart_html) print "<div>" . $database->registry->cadcart->cart_html . "</div>\n";
if ($database->cadorder->meta->rows) print "<p>" . $forms->button("Retrieve files",array("onclick"=>"fn_action('complete');")) . "</p>\n";
print $forms->close();
$menu->copyright();

class cadcart_class {
	var $id;
	var $status;
    var $sku;
    var $sku_name;
    var $cad_name;
    var $initial_date;
    var $status_date;
    function __construct($data,$fetch) {
		$this->id=$data->id;
		$this->status=$data->status;
		$this->sku=$data->sku;
		$this->sku_name=$data->sku_name;
		$this->cad_name=$fetch['cad.name'];
		$this->initial_date=$data->initial_date;
		$this->status_date=$data->status_date;
    }
}

?>