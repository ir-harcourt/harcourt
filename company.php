<?php
require_once "scs_header.php";
$database->user->access("Customer");
$forms->title("Harcourt | Engineering Productivity Solutions | Company");
$forms->html->meta("description","Meet Harcourt Engineering Productivity Solutions for Engineers in Aerospace & Heavy Industry.");
$menu->head(array("scs_div"=>FALSE));
$menu->tab_viewer();
$menu->copyright();
?>