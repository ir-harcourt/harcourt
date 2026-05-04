<?php
require_once "scs_header.php";
$database->user->access("Customer");
$forms->title("Harcourt | Engineering Productivity Solutions | Quality");
$forms->html->meta("description","The demands of the aerospace and heavy industries are rigorous and strict. It goes without saying that tools must be precise, tough and manufactured to close tolerances. Learn more about Harcourt Quality here.");
$menu->head(array("scs_div"=>FALSE));
$menu->tab_viewer();
$menu->copyright();
?>