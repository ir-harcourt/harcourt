<?php
$forms->title("Page Crawler (Revised 12/18/2012)");
require_once "classes/crawl.php";
$crawl=new crawl_class($_POST);
switch (TRUE) {
  case (!$crawl->options->action):
    unset($_SESSION['crawl']);
	break;
   case ($crawl->options->action=="cancel"):
    unset($_SESSION['crawl']);
    $forms->message[]="Request cancelled";
	break;
   case ($crawl->options->action=="timeout"):
	$crawl=unserialize($_SESSION['crawl']);
	$crawl->options=new crawl_options_class($_POST);
	unset($_SESSION['crawl']);
}
switch (TRUE) {
  case ($crawl->options->action=="crawl"):
  case ($crawl->options->action=="timeout"):
	$forms->message[]=$crawl->site();
    if ($crawl->options->action=="timeout") $_SESSION['crawl']=serialize($crawl);
    break;
}
switch (TRUE) {
  case ($crawl->options->action != "success"):
  case ($crawl->options->output != "Upload"):
	break;
  default:
    header("Content-type: text/plain");
    header("Content-Disposition: attachment; filename=scs_crawl.csv");
    header("Pragma: no-cache");
    header("Expires: 0");
	$fh=fopen("php://output", "w");
    $results=array("path","query","title","description","http_code");
    fputcsv($fh,$results);
    foreach($crawl->pages as $page) {
		$results=array($page->path,$page->query,$page->title,$page->description,$page->info['http_code']);
		fputcsv($fh,$results);
    }
    fclose($fh);
	die;
}
$menu->head();
$forms->message();
?>
<script type="text/javascript">
function fn_action(action) {
	if (action=="cancel") {
	    if (!confirm("Cancel scan?")) {
            return;
	    }
    }
	document.scs_form.action.value=action;
	document.scs_form.submit();
}
</script>
<?php
print $forms->open();
print $forms->hidden("action");
switch ($crawl->options->action) {
  case "timeout":
	print $forms->hidden("url",$crawl->options->url);
	print $forms->hidden("depth",$crawl->options->depth);
	print $forms->hidden("output",$crawl->options->output);
	print $forms->hidden("problem",$crawl->options->problem);
	print "<p>Starting page: <b>" . $crawl->options->url . "</b>\n";
    print "<br>Scan <b>" . $crawl->options->depth . "</b> page(s)\n";
    print "<br>Timeout in " . $forms->select_number("timeout",$crawl->options->timeout,0,60) . " seconds\n";
    print "<br>Output: <b>" . $crawl->options->output . "</b>\n";
    if ($crawl->options->problem) print "<br>Only Problems? <b>" . $crawl->options->problem . "</b>\n";
    print "</p>\n";
    print "<p>\n";
	print number_format(sizeof($crawl->pages)) . " completed page(s) " . number_format($crawl->constant->elapsedtime / (sizeof($crawl->pages)),2) . " page(s) per second";
	if ($crawl->unprocessed) print "<br>" . number_format(sizeof($crawl->unprocessed)) . " unprocessed page(s)\n";
	if ($crawl->rejected) print "<br>" . number_format(sizeof($crawl->rejected)) . " rejected page(s)\n";
    print "</p>\n";
    print "<p>\n";
    print $forms->button("Continue scan",array("onclick"=>"fn_action('timeout');"));
    print " " . $forms->button("Cancel scan",array("onclick"=>"fn_action('cancel');"));
    print "</p>\n";
    break;
  default:
	print "<table class='standard noborder'>\n";
	print "<tr>\n";
	print "<td width=30%>Starting page</td>\n";
	print "<td width=70%>" . $forms->text("url",$crawl->options->url,40) . "</td>\n";
	print "</tr>\n";
	print "<tr>\n";
	print "<td>Timeout in</td>\n";
	print "<td>" . $forms->select_number("timeout",$crawl->options->timeout,0,60) . " seconds</td>\n";
	print "</tr>\n";
	print "<tr>\n";
	print "<td>Scan</td>\n";
	print "<td>" . $forms->select("depth",$crawl->constant->depth,$crawl->options->depth,FALSE) . " page(s)</td>\n";
	print "</tr>\n";
	print "<tr>\n";
	print "<td>Output</td>\n";
	print "<td>" . $forms->select("output",$crawl->constant->output,$crawl->options->output,FALSE) . "</td>\n";
	print "</tr>\n";
	print "<tr>\n";
	print "<td>Only Problems?</td>\n";
	print "<td>" . $forms->select("problem",$crawl->constant->problem,$crawl->options->problem,FALSE) . "</td>\n";
	print "</tr>\n";
	print "<tr>\n";
	print "<td>&nbsp;</td>\n";
	print "<td>" . $forms->button("Begin",array("onclick"=>"fn_action('crawl');")) . "</td>\n";
	print "</tr>\n";
	print "</table>\n";
	switch (TRUE) {
      case ($crawl->options->action != "success"):
	    break;
      case ($crawl->options->output=="Google"):
        $crawl->output_google();
        break;
      case ($crawl->options->output=="Analysis"):
        $crawl->output_analysis();
        break;
      case ($crawl->options->output=="Table"):
        $crawl->output_table();
        break;
	}
	if ($crawl->options->action == "success") print "<p class=center><b>" . number_format(sizeof($crawl->pages)) . " page(s) found " . number_format($crawl->constant->elapsedtime / (sizeof($crawl->pages)),2) . " page(s) per second</p>\n";
}
print $forms->close();
$menu->copyright();
?>