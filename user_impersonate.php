<?php
require_once "scs_header.php";
require_once "classes/industry.php";
require_once "classes/portal.php";
$forms->title("User Impersonate");
$forms->report['action']=$_POST['action'];
switch (TRUE) {
  case (isset($_SESSION['impersonate'])):
    $forms->message[]="Impersonating Stopped";
	$database->user->read($_SESSION['impersonate']);
	unset($_SESSION['impersonate'],$_SESSION['portal_legacy']);
    $database->user->session();
//    $_SESSION['user']=$database->user->data;
    $database->profile->load();
	fn_url_redirect($menu->page->home);
  case (!$database->user->access("Administrator",FALSE)):
	fn_url_redirect($menu->page->home);
    break;
  case (!$forms->report['action']):
  	$database->user->read($_SESSION['user']->id);
 	break;
  default:
	switch (TRUE) {
      case ($_POST['visitor']):
  		$database->user->data=new user_data_class();
		$database->user->data->ip="255.255.255.255";
        break;
      default:
		$database->user->read($_POST['ajax_user_id']);
        switch (TRUE) {
          case (!$database->user->meta->rows):
          case (!$database->user->access($database->user->data->type,FALSE)):
          	$forms->error("ajax_user_id","Invalid User ID");
        }
        if (sizeof($forms->error)) break;
        $database->user->data->type=$_POST['ajax_user_type'];
        $database->user->data->status=$_POST['ajax_user_status'];
        $database->user->data->catalog=$_POST['ajax_user_catalog'];
        $database->user->data->cad=$_POST['ajax_user_cad'];
        $database->user->data->price_type=$_POST['ajax_user_price_type'];
    	$database->user->data->price_pct=(fn_number($_POST['ajax_user_price_pct'],2) * .01);

        $database->user->data->ecommerce=$_POST['ajax_user_ecommerce'];
        $database->user->data->industry_id=$_POST['ajax_user_industry_id'];
        $database->user->data->portal_id=$_POST['ajax_user_portal_id'];
        $database->user->data->portal_legacy=$_POST['portal_legacy'];
        $database->user->data->currency_code=$_POST['ajax_user_currency_code'];
        $database->user->data->language_code=$_POST['ajax_user_language_code'];
        $database->user->data->campaign_code=$_POST['campaign_code'];
	    switch ($database->user->data->price_type) {
	      case "":
	        $database->user->data->price_pct=0;
	        break;
	      case "base":
	        if ($database->user->data->price_pct) $forms->error("ajax_user_price_pct","Must be 0% for base price");
	        break;
	      case "discount":
	        if ( ($database->user->data->price_pct <= 0) || ($database->user->data->price_pct >= 1) ) $forms->error("ajax_user_price_pct","Discount must be > 0% and < 100%");
	        break;
	      case "plus":
	        if ( ($database->user->data->price_pct <= 0) || ($database->user->data->price_pct >= 5) ) $forms->error("ajax_user_price_pct","Price plus must be > 0% and < 500%");
	        break;
	    }
/*
	    if ($database->user->data->status != "Active") {
	        if ($database->user->data->catalog) $forms->error('ajax_user_catalog','Catalog requires Active status');
	        if ($database->user->data->cad) $forms->error('ajax_user_cad','CAD requires Active status');
	        if ($database->user->data->pricing) $forms->error('ajax_user_price_type','Pricing requires Active status');
	        if ($database->user->data->pricing) $forms->error('ajax_user_price_pct','Discount requires Active status');
	        if ($database->user->data->ecommerce) $forms->error('ajax_user_ecommerce','Ecommece requires Active status');
	    }
*/
	}
    if (sizeof($forms->error)) break;
    $_SESSION['impersonate']=$_SESSION['user']->id;
    $_SESSION['portal_legacy']=$database->user->data->portal_legacy;
    $database->user->session();
//	$_SESSION['user']=$database->user->data;
    $database->profile->load();
    if ($_POST['reset_cookie']) $menu->cookie("");
    if ($database->campaign->active($database->user->data->campaign_code)) {
        $_SESSION['campaign']=$database->user->data->campaign_code;
      } else {
        unset($_SESSION['campaign']);
    }
	print "<script>";
	print "window.open('/','_top');\n";
	print "</script>";
	die();
}
$menu->head();
$forms->message();
print $forms->open();
print $forms->hidden("action","update");
print "<p class=large><b>Select the user you wish to log in as:</b></p>";
print "<table class='standard noborder'>";
print "<tr>";
print "<td width='30%'>Visitor?</td>";
print "<td width='70%'>" . $forms->checkbox("visitor",1,(($database->user->data->id) ? 0 : 1)) . "</td>";
print "</tr>";
print "<tr>";
print "<td width='30%'>Impersonate User</td>";
print "<td width='70%'>" . $forms->text("ajax_user_id",$database->user->data->id,12) . " <b><span id=ajax_user_label></span></b></td>";
print "</tr>";
print "<tr>";
print "<td>User type</td>";
print "<td>" . $forms->select("ajax_user_type",$database->user->constant->type,$database->user->data->type,FALSE);
print "</td>";
print "<tr>";
print "<td>Status</td>";
print "<td>" . $forms->select("ajax_user_status",$database->user->constant->status,$database->user->data->status,FALSE) . "</td>";
print "</tr>";

print "</tr>";
print "<tr>";
print "<td>Catalog?</td>";
print "<td>" . $forms->checkbox("ajax_user_catalog",1,$database->user->data->catalog) . "</td>";
print "</tr>";
print "<tr>";
print "<td>CAD?</td>";
$text=array( $forms->checkbox("ajax_user_cad",1,$database->user->data->cad) );
if ($forms->error['cad']) $text[]=$forms->error_text['cad'];
print "<td>" . implode(" ",$text) . "</td>";
print "</tr>";
print "<tr>";
$text=array( "Pricing: " . $forms->select("ajax_user_price_type",$database->user->constant->price_type,$database->user->data->price_type,FALSE,array("onchange"=>"$('#price_pct').val('');")) );
if ($forms->error_text['ajax_user_price_type']) $text[]=$forms->error_text['ajax_price_type'];
print "<td>" . implode("<br>",$text) . "</td>";
$text=array($forms->text("ajax_user_price_pct",$database->user->data->price_pct * 100,6,6,"decimal:2") . "%");
if ($forms->error_text['ajax_user_price_pct']) $text[]="<b>" . $forms->error_text['ajax_user_price_pct'] . "</b>";
print "<td>" . implode(" ",$text) . "</td>";
print "</tr>";
print "<tr>";
print "<td>ECommerce?</td>";
print "<td>" . $forms->checkbox("ajax_user_ecommerce",1,$database->user->data->ecommerce) . "</td>";
print "</tr>";
print "<tr>";
print "<td>Industry</td>";
print "<td>" . $database->industry->select($database->user->data->industry_id,array("field"=>"ajax_user_industry_id")) . "</td>";
print "</tr>";
print "<tr>";
print "<tr>";
print "<td>Portal</td>";
print "<td>" . $database->portal->select($database->user->data->portal_id,array("field"=>"ajax_user_portal_id", "active"=>FALSE)) . "</td>";
print "</tr>";
print "<tr>";
print "<td>Legacy?</td>";
print "<td>" . $forms->checkbox("portal_legacy",1,$database->user->data->portal_legacy) . "</td>";
print "</tr>";
print "<tr>";
print "<td>Campaign</td>";
print "<td>" . $database->campaign->select($database->user->data->campaign_code) . "</td>";
print "</tr>";

print "<tr>";
print "<td>Currency</td>";
print "<td>" . $database->currency->select($database->user->data->currency_code,array("field"=>"ajax_user_currency_code","blank"=>FALSE)) . "</td>";
print "</tr>";
print "<tr>";
print "<td>Langauge</td>";
print "<td>" . $database->language->select($database->user->data->language_code,array("field"=>"ajax_user_language_code","blank"=>FALSE)) . "</td>";
print "</tr>";
print "<tr>";
print "<td>" . $forms->checkbox("reset_cookie",1,0) . " Reset cookie?</td>";
print "<td>" . $_COOKIE['harcourt'] . "</td>";
print "</tr>";;
print "</table>";
print "<p>" . $forms->submit("Go!") . "</p>";
print $forms->close();
$menu->copyright();
?>