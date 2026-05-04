<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Feedback</title>
<style type="text/css">
#thank-you {
	font-family: Calibri, Arial;
	font-size: 36px;
	font-weight: bold;
	color: #999;
	padding-top: 100px;
}
</style>
</head>

<body>
<?php

function DoStripSlashes($fieldValue)  { 
 if ( get_magic_quotes_gpc() ) { 
  if (is_array($fieldValue) ) { 
   return array_map('DoStripSlashes', $fieldValue); 
  } else { 
   return stripslashes($fieldValue); 
  } 
 } else { 
  return $fieldValue; 
 } 
}

$fname = DoStripSlashes( $_POST['fname'] );
$lname = DoStripSlashes( $_POST['lname'] );
$email = DoStripSlashes( $_POST['email'] );
$feedback = DoStripSlashes( $_POST['feedback'] );

$fname = strip_tags($fname);
$lname = strip_tags($lname);
$email = strip_tags($email);
$feedback = strip_tags($feedback);

$to = 'aaron.mccallum@harcourtind.com';
$subject = 'Feedback Form';
$message = "First Name: $fname\n" . "Last Name: $lname\n" . "Email: $email\n" . "Feedback: $feedback\n" ;
$headers = 'From: aaron.mccallum@harcourtind.com' . 'reply-to: aaron.mccallum@harcourtind.com';

mail($to, $subject, $message, $headers);

?>
</body>
</html>