<?php
require_once "scs_header.php";
$results=array();
switch (TRUE) {
  case (!isset($_SERVER['HTTP_X_REQUESTED_WITH'])):
  case (!preg_match("/^xmlhttprequest$/i",$_SERVER['HTTP_X_REQUESTED_WITH'])):
    die("Direct request");
  case ($_GET['action']=="catalog"):
  case ($_GET['action']=="products"):
	$results['welcome_company_name']=$_SESSION['user']->company_name;
	$data=array();
    if (isset($_SESSION['email_form'])) {
        $action=$_SESSION['email_form'];
      } else {
        $action="/catalog.php";
    }
    $data[]=$forms->open(array("url"=>$action),array("target"=>"_top"));
    $data[]=$forms->hidden("action","email");
	$data[]="<div class='entry' id='firstentry'>";
	$data[]="Email Address: <input name='email' type='text' /></div>";

	$img_options=array();
    $img_options['id']="Image1";
	$img_options['width']=140;
	$img_options['height']=31;
    $img_options['onmouseover']="MM_swapImage('Image1','','images/continuebuttonover.gif',1)";
    $img_options['onmouseout']="MM_swapImgRestore();";

	$forms_options=array();
    $forms_options['submit']=TRUE;
	$data[]="<div class='submitbutton'>";
	$data[]=trim($forms->img("/scs_images/continuebutton.gif",$img_options,$forms_options) );
    $data[]="</div>";
	$data[]=trim($forms->close());
	$results['userformwelcome']=implode("",$data);

    $data=array();
    $data[]=$forms->open(array("url"=>$action),array("target"=>"_top"));
    $data[]=$forms->hidden("action","email");

    $data[]="<div id='text-entry'><input type='text' name='email' id='email' /></div>";
    $data[]="<div id='email-address'>E-mail Address: </div>";
    $data[]="<div id='continue'><img src='/welcome-pages/continuebutton.gif' width='140' height='31' id='Image1' onmouseover='MM_swapImage('Image1','','/welcome-pages/continuebuttonover.gif',1)' onmouseout='MM_swapImgRestore()' onclick='document.scs_form.submit();' border='0'/></div>";
    $data[]="<img src='/welcome-pages/formbackground2.gif' width='393' height='133' />";
	$data[]=trim($forms->close());
	$results['form-holder']=implode("",$data);
	break;
}
print json_encode($results);
?>