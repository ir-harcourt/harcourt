<?php
require_once "scs_header.php";
$forms->title("BOT Impersonate");
$forms->report['action']=$_POST['action'];
switch (TRUE) {
  case (isset($_SESSION['impersonate'])):
    $forms->message[]="Impersonating Stopped";
	$database->user->read($_SESSION['impersonate']);
	unset($_SESSION['impersonate']);
    $_SESSION['user']=$database->user->data;
	$menu->page();
	break;
  case (!$database->user->access("Administrator",FALSE)):
	fn_url_redirect($menu->page->home);
    break;
  case (!$forms->report['action']):
 	break;
  default:
	$database->bot->read($_POST['bot_id']);
    if (!$database->bot->meta->rows) {
    	$forms->error('bot_id');
        break;
    }
	$_SESSION['impersonate']=$_SESSION['user']->id;
    $database->bot->session($database->bot->data,TRUE);
}
$menu->head();
$forms->message();
print $forms->open();
print $forms->hidden("action","update");
$data=array();
$database->bot->query("select * from bot order by name,code");
while ($database->bot->fetch = $database->bot->fetch_array() ) {
    $database->bot->fetch();
	if (!array_key_exists($database->bot->data->status,$data)) $data[$database->bot->data->status]=array();
    $data[$database->bot->data->status][$database->bot->data->id]=$database->bot->data->name . " (" . $database->bot->data->code . ")";
}
$database->bot->free_result();
$text=array();
$text[]=$forms->optgroup("bot_id",$data,"");
$text[]=$forms->submit("Go!");
print "<p class=standad>Impersonate BOT: " . implode("<br>",$text) . "</p>\n";
print $forms->close();
$menu->copyright();
?>