<?php
if (isset($_SESSION['scscpq_wp'])) {
	session_start();
	unset($_SESSION['scscpq_wp']);
    header("Location: " . $_SERVER['PHP_SELF']);
}
require_once "scs_header.php";
$forms->title("Harcourt");
$forms->html->meta("description",$description);
$menu->head();
?>
<script>
content=new Array();
content.push("<style>");
content.push(".scscpq_loader {display: inline-block; border: 10px solid #f3f3f3; border-radius: 50%; border-top: 10px solid #DC2536; width: 40px; height: 40px; -webkit-animation: spin 2s linear infinite; animation: spin 2s linear infinite; }");
content.push(".scscpq_loader_text { display: inline-block; vertical-align: top; margin-left: 10px; margin-top: 18px; }");
content.push("@-webkit-keyframes spin { 0% { -webkit-transform: rotate(0deg); } 100% { -webkit-transform: rotate(360deg); } }");
content.push("@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }");
content.push("</style>");
content.push("<div style='margin-top: 50px;text-align: center;'><div class=scscpq_loader></div><div class=scscpq_loader_text>Loading ...</div></div>");
jQuery(document).ready(function() {
	jQuery('#scscpq_page').html(content.join('\n'));
});
</script>
<?php
print "<div id=scscpq_page></div>";
$menu->copyright();
?>