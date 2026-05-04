<?php
$forms->title("SCS Access Log");
$forms->message[]="Revised 11/03/2014";
$forms->report['action']=$_POST['action'];
$forms->report['page']=intval($_POST['page']);
$forms->report['item']=intval($_POST['item']);
switch (TRUE) {
  case (!$forms->report['action']):
	$forms->report['from_date']=date("Y/m/d",strtotime("-30 days"));
	$forms->report['thru_date']=date("Y/m/d",strtotime("now"));
    $forms->report['descending']="desc";
	break;
  default:
	$forms->report['type_subtype']=$_POST['type_subtype'];
	list($forms->report['type'],$forms->report['subtype'])=preg_split("/\t/",base64_decode($_POST['type_subtype']));
	$forms->report['from_date']=fn_date($_POST['from_date'],"mdy");
	if (!$date_ok) $forms->error('from_date');
	$forms->report['thru_date']=fn_date($_POST['thru_date'],"mdy");
	switch (TRUE) {
	  case (!$date_ok):
	  case (($forms->report['thru_date']) && ($forms->report['from_date'] > $forms->report['thru_date'])):
	    $forms->error('thru_date');
	}
    $forms->report['excel']=$_POST['excel'];
    $forms->report['descending']=$_POST['descending'];
    $query_where=array();
    if ($forms->report['type']) $query_where[]=new query_where("log.type","=",$forms->report['type']);
    if ($forms->report['subtype']) $query_where[]=new query_where("log.subtype","=",$forms->report['subtype']);
    if ($forms->report['from_date']) $query_where[]=new query_where("date_format(from_unixtime(log.date_time),'%Y/%m/%d')",">=",$forms->report['from_date']);
    if ($forms->report['thru_date']) $query_where[]=new query_where("date_format(from_unixtime(log.date_time),'%Y/%m/%d')","<=",$forms->report['thru_date']);
    $query_base=
    	"select \n" .
        "user.name as 'user_name', \n" .
        "user.company_name as 'user_company_name', \n" .
        "user.address as 'user_address', \n" .
        "user.city as 'user_city', \n" .
        "user.state as 'user_state', \n" .
        "user.zip as 'user_zip', \n" .
        "user.country_code as 'user_country_code', \n" .
        "user.status as 'user_status', \n" .
    	"log.* from log \n" .
        "left join user on user.id=log.user_id \n" .
        $database->where($query_where) .
        "order by id " . $forms->report['descending'];
    if (sizeof($forms->error)) break;
	if (!$forms->report['excel']) break;

    require_once("ExcelWriterXML/ExcelWriterXML.php");

    $xls = new ExcelWriterXML("log.xls");
    $xls->overwriteFile(TRUE);
    $xls->docTitle("Access log list");
    if ($forms->report['type']) {
        $subject = $forms->report['type'];
        if ($forms->report['subtype']) $subject .= "-" . $forms->report['subtype'];
        $subject .= " Access Log";
      } else {
        $subject="Access Log";
    }
    if ($forms->report['from_date']) $subject .= " From " .fn_date($forms->report['from_date'],"ymd");
    if ($forms->report['thru_date']) $subject .= " Thru " .fn_date($forms->report['thru_date'],"ymd");
    $xls->docSubject($subject);
    $xls->docAuthor($_SESSION['user']->name);
    $xls->docCompany($database->registry->company->name);
    $sheet=$xls->addSheet('Access Log');

	$style_alpha=$xls->addStyle('style_alpha');
	$style_alpha->alignWraptext();
	$style_alpha->alignVertical('Top');

    $style_date=$xls->addStyle('style_date');
	$style_date->numberFormat("mm/dd/yyyy");
	$style_date->alignVertical('Top');
	$style_date->alignHorizontal('Left');

    $style_time=$xls->addStyle('style_time');
	$style_time->numberFormat('hh:mm:ss');
	$style_time->alignVertical('Top');
	$style_time->alignHorizontal('Left');

    $sheet->columnWidth(1,60);
    $sheet->columnWidth(2,60);
    $sheet->columnWidth(3,60);
    $sheet->columnWidth(4,200);
    $sheet->columnWidth(5,300);
    $sheet->columnWidth(6,200);
    $sheet->columnWidth(7,80);
    $sheet->columnWidth(8,80);
    $sheet->columnWidth(9,80);
    $sheet->columnWidth(10,100);
    $sheet->columnWidth(11,100);
    $sheet->columnWidth(12,100);
    $sheet->columnWidth(13,500);
    $sheet->columnWidth(14,100);
    $sheet->columnWidth(15,600);

    $sheet->writeString(1,1,"Date","db_header");
    $sheet->writeString(1,2,"Time","db_header");
    $sheet->writeString(1,3,"User#","db_header");
    $sheet->writeString(1,4,"User Name","db_header");
    $sheet->writeString(1,5,"Company Name","db_header");
    $sheet->writeString(1,6,"City","db_header");
    $sheet->writeString(1,7,"State","db_header");
    $sheet->writeString(1,8,"Zip","db_header");
    $sheet->writeString(1,9,"Country","db_header");
    $sheet->writeString(1,10,"Status","db_header");
    $sheet->writeString(1,11,"Group","db_header");
    $sheet->writeString(1,12,"Type","db_header");
    $sheet->writeString(1,13,"Comment","db_header");
    $sheet->writeString(1,14,"IP Address","db_header");
    $sheet->writeString(1,15,"Agent","db_header");
    $xls_row=1;
	$database->log->query($query_base);
	while ($database->log->fetch = $database->log->fetch_array()) {
	    $database->log->fetch();
        $xls_row++;
        $datetime=
            date("Y-m-d",$database->log->data->date_time) . "T" .
            date("H:i:s",$database->log->data->date_time) . ".000";
        $sheet->writeDateTime($xls_row,1,date("Y-m-d",$database->log->data->date_time) . "T00:00:00.000",$style_date);
		$sheet->writeDateTime($xls_row,2,$datetime,$style_time);
        $sheet->writeNumber($xls_row,3,$database->log->data->user_id,$style_alpha);
        $sheet->writeString($xls_row,4,$database->log->fetch['user_name'],$style_alpha);
        $sheet->writeString($xls_row,5,$database->log->fetch['user_company_name'],$style_alpha);
        $sheet->writeString($xls_row,6,$database->log->fetch['user_city'],$style_alpha);
        $sheet->writeString($xls_row,7,$database->log->fetch['user_state'],$style_alpha);
        $sheet->writeString($xls_row,8,$database->log->fetch['user_zip'],$style_alpha);
        $sheet->writeString($xls_row,9,$database->log->fetch['user_country_code'],$style_alpha);
        $sheet->writeString($xls_row,10,$database->log->fetch['user_status'],$style_alpha);
        $sheet->writeString($xls_row,11,$database->log->data->type,$style_alpha);
        $sheet->writeString($xls_row,12,$database->log->data->subtype,$style_alpha);
		$sheet->writeString($xls_row,13,implode("\n",$database->log->data->comment),$style_alpha);
        $sheet->writeString($xls_row,14,long2ip($database->log->data->ip_address),$style_alpha);
        $sheet->writeString($xls_row,15,$database->log->data->agent,$style_alpha);
	}
	$xls->sendHeaders();
	$xls->writeData();
    die;
}
switch (TRUE) {
  case (!$forms->report['action']):
  case (sizeof($forms->error)):
	break;
  case (($forms->report['type']) && ($forms->report['subtype'])):
	$forms->message[]="Record Type: " . $forms->report['type'] . "&#10132; " . $forms->report['subtype'];
	break;
  case ($forms->report['type']):
	$forms->message[]="Record Type: " . $forms->report['type'] . "&#10132; All";
	break;

}
$menu->head();
$forms->message();

print $forms->open();
print $forms->hidden("action");
print $forms->hidden("page");
print $forms->hidden("item");
switch (TRUE) {
  case ($forms->report['action'] == "item"):
	print $forms->hidden("type_subtype",$forms->report['type_subtype']);
	print $forms->hidden("from_date",$forms->report['from_date'],"date:ymd");
	print $forms->hidden("thru_date",$forms->report['thru_date'],"date:ymd");
	print $forms->hidden("descending",$forms->report['descending']);
	$query =$query_base . $database->item_limit($forms->report['item']);
	$database->log->query($query,TRUE);
    $database->log->fetch(TRUE);
    $database->log->free_result();
    $database->user->read($database->log->data->user_id);
	print "<table class='standard noborder'>\n";
    print "<caption>&nbsp;</caption>\n";
	print "<tr>\n";
	print "<td width=30%>Date/Time</td>\n";
	print "<td width=70%>" . date("l F j, Y h:i A",$database->log->data->date_time) . "</td>\n";
    print "</tr>\n";
    switch (TRUE) {
      case (!$database->log->data->user_id):
		print "<tr>\n";
        print "<td>User</td>\n";
        print "<td>Visitor</td>\n";
        print "</tr>\n";
        break;
      case (!$database->user->meta->rows):
		print "<tr>\n";
        print "<td>User</td>\n";
        print "<td>Not on file</td>\n";
        print "</tr>\n";
        break;
	  default:
		if ($database->user->data->username) {
			print "<tr>\n";
            print "<td>Username</td>\n";
            print "<td>" . $database->user->data->username . "</td>\n";
            print "</tr>\n";
        }
		print address_table("user",$database->user->data);
	    print "<td>Email</td>\n";
	    print "<td>" . $database->user->data->email . "</td>\n";
	    print "</tr>\n";
	    print "<tr>\n";
    }
    print "<tr><td colspan=2>&nbsp;</td></tr>\n";
    print "<tr>\n";
    print "<td>Record type</td>\n";
    print "<td>" . $database->log->data->type;
    if ($database->log->data->subtype) print " (" . $database->log->data->subtype . ")";
    print "</td>\n";
    print "</tr>\n";
    if ($database->log->data->sku) {
	    print "<tr>\n";
	    print "<td>SKU</td>\n";
	    print "<td>" . $database->log->data->sku . "</td>\n";
	    print "<tr>\n";
	}
    if (sizeof($database->log->data->comment)) {
	    print "<tr>\n";
	    print "<td>Comment</td>\n";
	    print "<td>" . implode("<br>",$database->log->data->comment) . "</td>\n";
	    print "<tr>\n";
	}
    print "<tr><td colspan=2>&nbsp;</td></tr>\n";
    print "<tr>\n";
    print "<td>IP address</td>\n";
    print "<td>" . long2ip($database->log->data->ip_address) . " (" . gethostbyaddr(long2ip($database->log->data->ip_address)) . ")</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Agent</td>\n";
    print "<td>" . $database->log->data->agent . "</td>\n";
    print "</tr>\n";
    print "</table>";
    $page_break=new page_item_break($database->log->meta->found_rows,"fn_page_item('item',0,%item%);");
    $page_break->return_url("Return","fn_page_item('page'," . (floor($forms->report['item'] / $database->query_limit) + 1) . ",0);");
    $page_break->item($forms->report['item']);
    break;
  default:
	print "<table class='standard noborder'>\n";
    print "<caption>&nbsp;</caption>\n";
	print "<tr>\n";
	print "<td>Record type</td>\n";
	$query=
	    "select * from log \n" .
        "where type<>'' \n" .
        "group by type,subtype\n" .
	    "order by type,subtype";
	$database->log->query($query);
    $type_list=array();
    while ($database->log->fetch = $database->log->fetch_array()) {
        $database->log->fetch();
		$key=base64_encode($database->log->data->type . "\t" . "");
        if (!isset($type_list[$database->log->data->type])) $type_list[$database->log->data->type][base64_encode($database->log->data->type . "\t" . "")]=$database->log->data->type . " (All)";
        if ($database->log->data->subtype) $type_list[$database->log->data->type][base64_encode($database->log->data->type . "\t" . $database->log->data->subtype)]=$database->log->data->subtype;
	}
    $database->log->free_result();
	print "<td>" . $forms->optgroup("type_subtype",$type_list,$forms->report['type_subtype']) . "</td>\n";
	print "</tr>\n";
	print "<tr>\n";
	print "<td>From date</td>\n";
	print "<td>" . $forms->text("from_date",$forms->report['from_date'],10,10,"date") . "</td>\n";
	print "</tr>\n";
	print "<tr>\n";
	print "<td>Thru date</td>\n";
	print "<td>" . $forms->text("thru_date",$forms->report['thru_date'],10,10,"date") . "</td>\n";
	print "</tr>\n";
	if ($forms->report['export']) {
	    print "<tr>\n";
	    print "<td>Excel?</td>\n";
	    print "<td>" . $forms->checkbox("excel",1,$forms->report['excel']) . "</td>\n";
	    print "</tr>\n";
	}
	print "<tr>\n";
	print "<td>Descending?</td>\n";
	print "<td>" . $forms->checkbox("descending","desc",$forms->report['descending']) . "</td>\n";
	print "</tr>\n";
	print "<tr>\n";
	print "<td>&nbsp;</td>\n";
	print "<td>" . $forms->button("Go!",array("onclick"=>"fn_page_item('page',0,0);")) . "</td>\n";
	print "</tr>\n";
	print "</table>\n";
    if ((!$forms->report['action']) || (sizeof($forms->error))) break;
	$query =$query_base . $database->page_limit($forms->report['page']);
	$database->log->query($query,TRUE);
    print "<table class='small border'>\n";
    print "<tr>\n";
    print "<th>Date/Time</th>\n";
	print "<th>User Name</th>\n";
    if (!$forms->report['type']) print "<th>Type</th>\n";
    if (!$forms->report['subtype']) print "<th>Subtype</th>\n";
    print "<th>SKU</th>\n";
    print "<th>Comment</th>\n";
    print "</tr>\n";
    if (!$forms->report['page']) {
    	$item=0;
	  } else {
      	$item=($forms->report['page'] - 1);
    }
    $item++;
    while ($database->log->fetch = $database->log->fetch_array()) {
        $database->log->fetch();
 		print "<td class='center nobr'><a href=\"javascript:fn_page_item('item',0,'$item');\">" . date("m/d/Y H:i",$database->log->data->date_time) . "</a></td>\n";
		$results=array();
		switch (TRUE) {
		  case (!$database->log->data->user_id):
          	$results[]="Visitor";
            break;
		  case (!$database->log->fetch['user_name']):
          	$results[]="Not on file";
            break;
          default:
			if ($database->log->fetch['user_company_name']) $results[]=$database->log->fetch['user_company_name'];
			$results[]=$database->log->fetch['user_name'];
        }
		print "<td class='nobr'>" . implode("<br>",$results) . "</td>\n";
		if (!$forms->report['type']) print "<td class='nobr'>" . $database->log->data->type . "</td>\n";
		if (!$forms->report['subtype']) print "<td class='nobr'>" . $database->log->data->subtype . "</td>\n";
		print "<td>" . $database->log->data->sku . "</td>\n";
        print "<td>" . implode("<br>",$database->log->data->comment) . "</td>\n";
        $item++;
        print "</tr>\n";
    }
    $database->log->free_result();
    print "</table>\n";
    $page_break=new page_item_break($database->log->meta->found_rows,"fn_page_item('page',%page%,0);");
    $page_break->page($forms->report['page']);
}
print $forms->close();
$menu->copyright();
?>