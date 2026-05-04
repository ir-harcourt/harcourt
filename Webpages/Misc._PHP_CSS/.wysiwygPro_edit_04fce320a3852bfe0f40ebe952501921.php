<?php ob_start() ?>
<?php
if ($_GET['randomId'] != "3zx_aZDcVsBSyM_09DWzTtS00SYeHFb8rGd_p_nap050OFNV6h6E6SsAbBJCsGJro7HgqGPpX4y39BHqm6ImeOUOzVyaTCuPjqzbo4HZomHKAHGckHpFz1UzSkDl6X0IavMOPDRKbwXFolhqmGUi244SmR5TmvBXd8RwzfQGd9fHRKs5TqcQh1ODueMbmMOOHWk8tJEQ3aiFetwbGnQlnwfZ65HAU_t17Ie80ZaqLmBuMjZ6JqfvFhbkpyKJ5QOb") {
    echo "Access Denied";
    exit();
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Editing Contact Page UK.htm</title>
<meta http-equiv="Content-Type" content="text/html; charset=us-ascii">
<style type="text/css">body {background-color:threedface; border: 0px 0px; padding: 0px 0px; margin: 0px 0px}</style>
</head>
<body>
<div align="center">

<div id="saveform" style="display:none;">
<form METHOD="POST" name=mform action="https://host126.hosting.register.com:2083/frontend/x3/filemanager/savehtmlfile.html">
    <input type="hidden" name="charset" value="us-ascii">
    <input type="hidden" name="baseurl" value="http://harcourtind.com/">
    <input type="hidden" name="basedir" value="/home/p142rp4m/public_html/">
    <input type="hidden" name="udir" value="/home/p142rp4m/public_html">
    <input type="hidden" name="ufile" value="Contact Page UK.htm">
    <input type="hidden" name="dir" value="%2fhome%2fp142rp4m%2fpublic_html">
    <input type="hidden" name="file" value="Contact%20Page%20UK.htm">
    <input type="hidden" name="doubledecode" value="1">
<textarea name=page rows=1 cols=1></textarea></form>
</div>
<div id="abortform" style="display:none;">
<form METHOD="POST" name="abortform" action="https://host126.hosting.register.com:2083/frontend/x3/filemanager/aborthtmlfile.html">
    <input type="hidden" name="charset" value="us-ascii">
    <input type="hidden" name="baseurl" value="http://harcourtind.com/">
    <input type="hidden" name="basedir" value="/home/p142rp4m/public_html/">
    <input type="hidden" name="dir" value="%2fhome%2fp142rp4m%2fpublic_html">
        <input type="hidden" name="file" value="Contact%20Page%20UK.htm">
    <input type="hidden" name="udir" value="/home/p142rp4m/public_html">
    <input type="hidden" name="ufile" value="Contact Page UK.htm">

        </form>
</div>
<script language="javascript">
<!--//

function setHtmlFilters(editor) {
// Design view filter
editor.addHTMLFilter('design', function (editor, html) {
        return html.replace(/\<meta\s+http\-equiv\="Content\-Type"[^\>]+\>/gi, '<meta http-equiv="Content-Type" content="text/html; charset=us-ascii" />');
});

// Source view filter
editor.addHTMLFilter('source', function (editor, html) {
        return html.replace(/\<meta\s+http\-equiv\="Content\-Type"[^\>]+\>/gi, '<meta http-equiv="Content-Type" content="text/html; charset=us-ascii" />');
});
}

// this function updates the code in the textarea and then closes this window
function do_save() {
    document.mform.page.value = WPro.editors[0].getValue();
	document.mform.submit();
}
function do_abort() {
	document.abortform.submit();
}
//-->
</script>
<?php
// make sure these includes point correctly:
include_once ('/usr/local/cpanel/base/3rdparty/wysiwygPro/wysiwygPro.class.php');

// create a new instance of the wysiwygPro class:
$editor = new wysiwygPro();

$editor->registerButton('save', 'Save',
        'do_save();', '##buttonURL##save.gif', 22, 22,
        'savehandler'); 
$editor->addRegisteredButton('save', 'before:print' );
$editor->addJSButtonStateHandler ('savehandler', 'function (EDITOR,srcElement,cid,inTable,inA,range){ 
        return "wproReady"; 
        }'); 


$editor->registerButton('cancel', 'Cancel',
        'do_abort();', '##buttonURL##close.gif', 22, 22,
        'cancelhandler'); 
$editor->addRegisteredButton('cancel', 'before:print' );
$editor->addJSButtonStateHandler ('cancelhandler', 'function (EDITOR,srcElement,cid,inTable,inA,range){ 
        return "wproReady"; 
        }'); 
$editor->theme = 'blue'; 
$editor->addJSEditorEvent('load', 'function(editor){editor.fullWindow();setHtmlFilters(editor);}');

$editor->baseURL = "http://harcourtind.com/";

$editor->loadValueFromFile('/home/p142rp4m/public_html/Contact Page UK.htm');

$editor->registerSeparator('savecan');

// add a spacer:
$editor->addRegisteredButton('savecan', 'after:cancel');

//$editor->set_charset('iso-8859-1');
$editor->mediaDir = '/home/p142rp4m/public_html/';
$editor->mediaURL = 'http://harcourtind.com/';
$editor->imageDir = '/home/p142rp4m/public_html/';
$editor->imageURL = 'http://harcourtind.com/';
$editor->documentDir = '/home/p142rp4m/public_html/';
$editor->documentURL = 'http://harcourtind.com/';
$editor->emoticonDir = '/home/p142rp4m/public_html/.smileys/';
$editor->emoticonURL = 'http://harcourtind.com/.smileys/';
$editor->loadPlugin('serverPreview'); 
$editor->plugins['serverPreview']->URL = 'http://harcourtind.com/.wysiwygPro_preview_04fce320a3852bfe0f40ebe952501921.php?randomId=3zx_aZDcVsBSyM_09DWzTtS00SYeHFb8rGd_p_nap050OFNV6h6E6SsAbBJCsGJro7HgqGPpX4y39BHqm6ImeOUOzVyaTCuPjqzbo4HZomHKAHGckHpFz1UzSkDl6X0IavMOPDRKbwXFolhqmGUi244SmR5TmvBXd8RwzfQGd9fHRKs5TqcQh1ODueMbmMOOHWk8tJEQ3aiFetwbGnQlnwfZ65HAU_t17Ie80ZaqLmBuMjZ6JqfvFhbkpyKJ5QOb';
// print the editor to the browser:
$editor->htmlCharset = 'us-ascii';
$editor->urlFormat = 'relative';
$editor->display('100%','450');

?>
</div>
<script>

</script>

</body>
</html>
<?php ob_end_flush() ?>
