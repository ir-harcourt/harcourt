<?php
include "/Webpages/Header_Footer/scs_header.php";
session_unset();
session_destroy();
fn_url_redirect($menu->page['home']);
?>