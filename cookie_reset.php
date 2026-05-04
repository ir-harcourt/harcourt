<?php
session_start();
session_destroy();
setcookie("harcourt","",strtotime("-1 minute"),"/",".harcourt.co");
session_write_close();
?>
<!DOCTYPE html>
<html>
<head>
<title>Harcourt Cookie Reset</title>
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<meta name="description" content="Harcourt Cookie Reset">
<link rel="stylesheet" type="text/css" href="//fonts.googleapis.com/css?family=Rajdhani:400,600,700|Open+Sans:400italic,600italic,100,200,300,400,600,700|Oswald:400,700">
<link rel="stylesheet" type="text/css" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css">
<link rel="stylesheet" type="text/css" href="/scs_scripts/scs.css">
<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
<script type="text/javascript" src="/scs_scripts/scscpq.js?ver=1.03"></script>
<script type="text/javascript" src="/scs_scripts/scscpq_wp.js?ver=1.03"></script>
</head>
<body>
<script>
/*
if (!sessionStorage['scscpq_cookie']) {
	sessionStorage['scscpq_cookie']="11/30/2020";
	window.location.reload(true);
}
*/
</script>
<p>Your cookies and cache have been reset</p>
<p><a href='/'>Click here to return</a></p>
</body>
</html>