<?php
/*
http://localhost:8085/campaign.php?campaign=active
http://192.168.1.100:8085/campaign.php?campaign=active

http://localhost:8085/campaign.PHP?campaign=REMOTE
*/
require_once "scs_header.php";
require_once "classes/subcategory.php";

switch (TRUE) {
  case (isset($_REQUEST['campaign'])):
  	$forms->report['campaign']=$_REQUEST['campaign'];
    break;
  case (isset($_SESSION['campaign'])):
  	$forms->report['campaign']=$_SESSION['campaign'];
    break;
  default:
  	$forms->report['campaign']="";
}
$forms->report['campaign_active']=$database->campaign->active($forms->report['campaign']);
if ( ($database->campaign->data->type == "Invite") && (!isset($_SESSION['campaign'])) ) $_SESSION['campaign']=$database->campaign->data->code;
switch (TRUE) {
  case (!isset($_SESSION['user'])):
  case ($_SESSION['user']->status == "Reject"):
  case (!$database->campaign->meta->rows):
  case ($database->campaign->data->type == "Remote"):
  case ( ($database->campaign->data->type == "Invite") && (!isset($_SESSION['campaign'])) ):
  case ( ($database->campaign->data->type == "Invite") && ($_SESSION['campaign'] != $database->campaign->data->code) ):
	fn_url_redirect($menu->page->home);
}
$forms->report['action']=$_POST['action'];
$forms->report['cookie']=$_COOKIE['harcourt'];
switch ($forms->report['action']) {
  case "register":
	if ($_SESSION['user']->id) break;
	$database->user->data=new user_data_class();
	$database->user->data->first_name=fn_text($_POST['first_name']);
    if (!strlen($database->user->data->first_name)) $forms->error('first_name');
	$database->user->data->last_name=fn_text($_POST['last_name']);
    if (!strlen($database->user->data->last_name)) $forms->error('last_name');
	$database->user->data->company_name=fn_text($_POST['company_name']);
    if (!strlen($database->user->data->company_name)) $forms->error('company_name');
	$database->user->data->name=$database->user->data->first_name . " " . $database->user->data->last_name;
    $email=new email_class($_POST['email'],TRUE);
	$database->user->data->email=$email->address;
    if ($email->error) $forms->error('email');
	if ( (sizeof($forms->error)) || ($_SESSION['impersonate']) ) break;
    $database->user->update(FALSE);
	if ($database->user->meta->error) {
    	$forms->error('email');
    	break;
    }
	$_SESSION['user']=$database->user->data;
    $menu->cookie($database->user->data->email);
    $forms->report['cookie']=$database->user->data->email;
	$options=array();
    $options['user_id']=$database->user->data->id;
    $options['email']=$database->user->data->email;
	$options['comment']=array();
	$options['comment'][]="Company name: " . $database->user->data->company_name;
	$options['comment'][]="Name: " . $database->user->data->name;
	$database->log->update("user:signup",$options);
	break;
  case "email":
	if (!$_SESSION['user']->id) break;
    $email=new email_class($_POST['email'],TRUE);
	$forms->report['email']=$email->address;
    if ($email->error) $forms->error('email');
    if (sizeof($forms->error)) break;
	$forms->report['cookie']=$forms->report['email'];
	$menu->cookie($forms->report['email']);
	$options=array();
    $options['email']=$forms->report['email'];
	$database->log->update("user:email",$options);
    break;
}
$forms->title($database->campaign->data->name);
$forms->html->meta("description",$database->campaign->data->description);
$forms->html_load_script("css",$database->campaign->data->css,"20171001");
$options=array();
$options['products_div']="campaign_div";
$menu->head($options);

print $forms->open();
print $forms->hidden("campaign",$forms->report['campaign']);
campaign_output("Public",$database->campaign->div('content'));
switch (TRUE) {
  case (!$_SESSION['user']->id):
	print "<div class='campaign_form_div'>";
    print "<div class='campaign_form_content'>\n";
    print $forms->hidden("action","register");
    print "<div class=campaign_form_title_div>";
    print "<div style=\"font-size: 40px;font-weight: 600;\">New User?</div>";
  	if (sizeof($forms->error)) print "<div style=\"font-size: 20px; font-weight: normal;font-style: italic\">Correct " . sizeof($forms->error) . " error(s)</div>";
    print "</div>";
	print "<div>First Name</div>";
	print "<div>" . $forms->text("first_name",$database->user->data->first_name,50,50,"",array("class"=>"campaign_content_input")) . "</div>";
	print "<div>Last Name</div>";
	print "<div>" . $forms->text("last_name",$database->user->data->last_name,50,50,"",array("class"=>"campaign_content_input")) . "</div>";
	print "<div>Company</div>";
	print "<div>" . $forms->text("company_name",$database->user->data->company_name,60,60,"",array("class"=>"campaign_content_input")) . "</div>";
	print "<div>Email Address</div>";
	print "<div>" . $forms->text("email",$database->user->data->email,60,60,"",array("class"=>"campaign_content_input")) . "</div>";
    print "<div>" . $forms->submit("Submit",array("class"=>"red-button campaign_form_button")) . "</div>";
	print "<div class=campaign_form_disclaimer>Please complete this form to become an approved Harcourt &reg; user. Once you have completed the form you will be contacted further.<br><br>Thank you!</div>";
    print "</div> <!-- .campaign_form_content -->";
    print "</div> <!-- .campaign_form_div -->";
  	break;
}
switch (TRUE) {
  case (!$_SESSION['user']->id):
  case ($_SESSION['user']->status == "Denied"):
  	break;
  default:
	campaign_output("Semi-Private",$database->campaign->div('content'));
	switch (TRUE) {
      case (!$forms->report['cookie']):
	    print "<div class='campaign_form_div'>";
	    print "<div class='campaign_form_content'>\n";
	    print $forms->hidden("action","email");
	    $text=array();
	    $text[]="<div style=\"font-family: 'Rajdhani',sans-serif !important; font-weight: 600 !important; font-size: 40px; padding-top: 80px;\">Login</div>";
	    if (sizeof($forms->error)) $text[]="<div style='font-size: 66%'>Correct " . sizeof($forms->error) . " error(s)</div>";
	    print "<div>" . implode("",$text) . "</div>";
        print "<div class=campaign_email_welcome>Welcome " . $_SESSION['user']->company_name . "</div>";
        print "<div style='margin-top: 15px; margin-bottom; 15px; font-size: 15px;'>To access your products portal, please enter your email address<br>for verification.</div>";
	    print "<div><br>Email Address</div>";
	    print "<div>" . $forms->text("email",$forms->report['email'],50,50,"",array("class"=>"campaign_content_input","style"=>'width: 220px;')) . " " . $forms->submit("Submit",array("class"=>"red-button campaign_email_button")) . "</div>";
        print "<div class='campaign_email_disclaimer'>Disclaimer: This should be a one-time verification only. Your email address will be kept strictly confidential and will not be disclosed to a third party or used outside the terms of our Privacy Policy.</div>";
	    print "</div> <!-- .campaign_form_content -->";
	    print "</div> <!-- .campaign_form_div -->";
	    break;
	}
}
switch (TRUE) {
  case (!$_SESSION['user']->id):
  case ($_SESSION['user']->status != "Active"):
  case (!$forms->report['cookie']):
  	break;
  default:
	campaign_output("Private",$database->campaign->div('content'));
    if (!sizeof($database->campaign->data->subcategory)) break;
	$query_where=array();
	$query_where[]=new query_where("category.active","=",1);
	$query_where[]=new query_where("subcategory.status","=","Active");
	$query_where[]=new query_where("inventory.active","=",1);
	$query_where[]=new query_where("inventory.subcategory_id","in",$database->campaign->data->subcategory);
	$query=array();
	$query[]="select";
	$query[]="category.id as 'category.id',";
	$query[]="category.name as 'category.name',";
	$query[]="subcategory.*";
	$query[]="from inventory";
	$query[]="left join subcategory on subcategory.id=inventory.subcategory_id";
	$query[]="left join category on category.id=subcategory.category_id";
	$query[]=trim($database->where($query_where));
	$query[]="group by subcategory.id";
	$query[]="order by category.sort_order,category.name,subcategory.sort_order,subcategory.name";
	$database->subcategory->query($query);
    if (!$database->subcategory->meta->rows) break;
	$img_query=array();
	$img_query['width']=$database->registry->local->thumbnail;
	$img_query['height']=$database->registry->local->thumbnail;
	$url_query=array("action"=>"subcategory","category_id"=>0,"subcategory_id"=>0);
    print "<div class=" . $database->campaign->div('content') . ">";
	print "<div class=catalog_subcategory_list>";
    print "<ul>";
    while ($database->subcategory->fetch = $database->subcategory->fetch_array() ) {
        $database->subcategory->fetch();
        $url_query['category_id']=$database->subcategory->fetch['category.id'];
        $url_query['subcategory_id']=$database->subcategory->data->id;
        $text=array();
        $text[]=$forms->img($database->subcategory->data->thumbnail_url,$img_query);
        $text[]="<br>" . $database->subcategory->data->name;
        print "<li>" . fn_href(implode("",$text),$menu->page->products,$url_query,$href_query) . "</li>";
    }
	print "</ul>";
    print "</div> <!-- .catalog_subcategory_list -->";
    print "</div> <!-- ." . $database->campaign->div('content') . " -->";
    $database->subcategory->free_result();
}
print $forms->close();
$menu->copyright();
function campaign_output($access,$div) {
global $database, $forms, $menu;
	$query_where=array();
	$query_where[]=new query_where("record_type","=","campaign");
	$query_where[]=new query_where("record_id","=",$database->campaign->data->id);
	$query_where[]=new query_where("record_item","like","{$access}%");
	$query=array("select * from content");
	$query[]=trim($database->where($query_where));
	$database->content->query($query);
	while ($database->content->fetch = $database->content->fetch_array() ) {
	    $database->content->fetch();
        $text=array();
		if (!$database->campaign->data->title_omit) $text[]="<h1>" . $database->content->data->name . "</h1>";
        if (strlen($database->content->data->description)) $text[]="<div>" . $database->content->data->description . "</div>";
        if (strlen($database->content->data->content)) $text[]="<div>" . $database->content->data->content . "</div>";
		print "<div class={$div}>" . implode("<br>",$text) . "</div>";
	}
	$database->content->free_result();
}
?>